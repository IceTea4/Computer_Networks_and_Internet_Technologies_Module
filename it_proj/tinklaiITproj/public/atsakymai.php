<?php
// Start session
session_start();

// Set timezone to UTC
date_default_timezone_set('UTC');

// Include timezone helper
require_once 'timezone.php';

// Check if user is logged in
if (!isset($_COOKIE['user_role']) || empty($_COOKIE['user_role'])) {
    header('Location: prisijungimas.php');
    exit;
}

$userRole = $_COOKIE['user_role'];
$userId = $_COOKIE['user_id'] ?? null;

// Get exam ID
$egzaminoId = isset($_GET['egzamino_id']) ? $_GET['egzamino_id'] : '';

if (empty($egzaminoId)) {
    header('Location: egzaminai.php');
    exit;
}

// Include database configuration
require_once 'db.php';

$error = '';
$success = '';
$egzaminas = null;
$klausimai = [];
$endTime = null;
$existingAnswers = [];

try {

    // Handle POST request to save answers or finish exam (only for vartotojas)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userRole === 'vartotojas') {
        $answers = $_POST['answers'] ?? [];
        $finishExam = isset($_POST['finish_exam']);

        if (!empty($answers) || $finishExam) {
            $pdo->beginTransaction();

            try {
                // Delete existing answers for this user and this exam's questions
                $deleteSql = "DELETE ea FROM egzamino_atsakymas ea
                              INNER JOIN egzamino_klausimas ek ON ea.egzamino_klausimo_id = ek.id
                              WHERE ea.vartotojo_id = :user_id AND ek.egzamino_id = :exam_id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute([
                    ':user_id' => hex2bin($userId),
                    ':exam_id' => hex2bin($egzaminoId)
                ]);

                // Insert new answers
                if (!empty($answers)) {
                    $insertSql = "INSERT INTO egzamino_atsakymas (id, vartotojo_id, egzamino_klausimo_id, atsakymas)
                                  VALUES (:id, :user_id, :egzamino_klausimo_id, :answer)";
                    $insertStmt = $pdo->prepare($insertSql);

                    foreach ($answers as $egzaminoKlausimoId => $answer) {
                        $insertStmt->execute([
                            ':id' => random_bytes(16),
                            ':user_id' => hex2bin($userId),
                            ':egzamino_klausimo_id' => hex2bin($egzaminoKlausimoId),
                            ':answer' => $answer
                        ]);
                    }
                }

                // If finishing exam, calculate results and save to egzamino_rezultatas
                if ($finishExam) {
                    // Get total and earned points from SQL
                    $resultSql = "SELECT
                                    SUM(k.verte) as total_points,
                                    SUM(CASE WHEN ea.atsakymas = k.atsakymas THEN k.verte ELSE 0 END) as earned_points
                                  FROM egzamino_klausimas ek
                                  INNER JOIN klausimas k ON ek.klausimo_id = k.id
                                  LEFT JOIN egzamino_atsakymas ea ON ea.egzamino_klausimo_id = ek.id
                                      AND ea.vartotojo_id = :user_id
                                  WHERE ek.egzamino_id = :exam_id";
                    $resultStmt = $pdo->prepare($resultSql);
                    $resultStmt->execute([
                        ':user_id' => hex2bin($userId),
                        ':exam_id' => hex2bin($egzaminoId)
                    ]);

                    $result = $resultStmt->fetch(PDO::FETCH_ASSOC);
                    $totalPoints = $result['total_points'] ?? 0;
                    $earnedPoints = $result['earned_points'] ?? 0;

                    // Calculate percentage in PHP
                    $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;

                    // Save result to egzamino_rezultatas
                    $insertResultSql = "INSERT INTO egzamino_rezultatas (id, vartotojo_id, egzamino_id, verte, perlaikomas, data)
                                        VALUES (:id, :user_id, :exam_id, :verte, :perlaikomas, NOW())";
                    $insertResultStmt = $pdo->prepare($insertResultSql);
                    $insertResultStmt->execute([
                        ':id' => random_bytes(16),
                        ':user_id' => hex2bin($userId),
                        ':exam_id' => hex2bin($egzaminoId),
                        ':verte' => $percentage,
                        ':perlaikomas' => 0
                    ]);

                    $pdo->commit();
                    header('Location: egzaminai.php?msg=exam_finished&score=' . $percentage);
                    exit;
                }

                $pdo->commit();
                header('Location: egzaminai.php?msg=answers_saved');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Klaida išsaugant atsakymus: ' . $e->getMessage();
            }
        }
    }

    // Fetch exam details
    $examSql = "SELECT id, pavadinimas, data, trukme FROM egzaminas WHERE id = :id";
    $examStmt = $pdo->prepare($examSql);
    $examStmt->execute([':id' => hex2bin($egzaminoId)]);
    $egzaminas = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$egzaminas) {
        $error = 'Egzaminas nerastas';
    } else {
        // Calculate end time
        $endTime = strtotime($egzaminas['data']) + ($egzaminas['trukme'] * 60);

        // For vartotojas role, check if they can still take the exam
        if ($userRole === 'vartotojas') {
            $currentTime = time();

            // Check if exam has ended
            if ($currentTime > $endTime) {
                $error = 'Egzamino laikas baigėsi';
            } else {
                // Check if user already has a result
                $resultCheckSql = "SELECT 1 FROM egzamino_rezultatas WHERE vartotojo_id = :user_id AND egzamino_id = :exam_id";
                $resultCheckStmt = $pdo->prepare($resultCheckSql);
                $resultCheckStmt->execute([
                    ':user_id' => hex2bin($userId),
                    ':exam_id' => hex2bin($egzaminoId)
                ]);

                if ($resultCheckStmt->fetch()) {
                    $error = 'Jūs jau atlikote šį egzaminą';
                }
            }
        }

        // Fetch questions for this exam, sorted by tema
        if (empty($error)) {
            $questionsSql = "SELECT
                                k.id,
                                k.klausimas,
                                k.atsakymai,
                                k.tema,
                                k.verte,
                                ek.id as egzamino_klausimo_id
                            FROM klausimas k
                            INNER JOIN egzamino_klausimas ek ON k.id = ek.klausimo_id
                            WHERE ek.egzamino_id = :exam_id
                            ORDER BY k.tema, k.klausimas";
            $questionsStmt = $pdo->prepare($questionsSql);
            $questionsStmt->execute([':exam_id' => hex2bin($egzaminoId)]);
            $klausimai = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch existing answers for this user and exam
            if (!empty($userId)) {
                $answersSql = "SELECT ea.egzamino_klausimo_id, ea.atsakymas
                               FROM egzamino_atsakymas ea
                               INNER JOIN egzamino_klausimas ek ON ea.egzamino_klausimo_id = ek.id
                               WHERE ea.vartotojo_id = :user_id AND ek.egzamino_id = :exam_id";
                $answersStmt = $pdo->prepare($answersSql);
                $answersStmt->execute([
                    ':user_id' => hex2bin($userId),
                    ':exam_id' => hex2bin($egzaminoId)
                ]);

                while ($row = $answersStmt->fetch(PDO::FETCH_ASSOC)) {
                    $existingAnswers[bin2hex($row['egzamino_klausimo_id'])] = $row['atsakymas'];
                }
            }
        }
    }

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $egzaminas ? htmlspecialchars($egzaminas['pavadinimas']) : 'Egzaminas'; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
            <a href="egzaminai.php" class="btn btn-primary mr-10">Grįžti į egzaminų sąrašą</a>
            <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
        </div>
    </div>

    <?php if ($egzaminas): ?>
        <h1><?php echo htmlspecialchars($egzaminas['pavadinimas']); ?></h1>

        <?php if ($userRole === 'vartotojas' && empty($error)): ?>
            <div class="exam-timer" id="exam-timer" style="text-align: center; margin: 20px 0; font-size: 24px; font-weight: bold; color: #d32f2f;">
                Likęs laikas: <span id="time-remaining"></span>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h1>Egzaminas</h1>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <p><a href="egzaminai.php" class="btn btn-primary">Grįžti į egzaminų sąrašą</a></p>
    <?php else: ?>
        <div class="exam-info" style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <p><strong>Data ir laikas:</strong> <?php echo htmlspecialchars(utcToLocal($egzaminas['data'])); ?></p>
            <p><strong>Trukmė:</strong> <?php echo htmlspecialchars($egzaminas['trukme']); ?> minutės</p>
            <p><strong>Klausimų skaičius:</strong> <?php echo count($klausimai); ?></p>
        </div>

        <?php if (empty($klausimai)): ?>
            <div class="no-data">Šiame egzamine nėra klausimų</div>
        <?php else: ?>
            <form method="POST" action="">
                <?php
                // Group questions by tema
                $klausimaiByTema = [];
                foreach ($klausimai as $klausimas) {
                    $klausimaiByTema[$klausimas['tema']][] = $klausimas;
                }
                ?>

                <?php foreach ($klausimaiByTema as $tema => $temoKlausimai): ?>
                    <div class="tema-section" style="margin-bottom: 30px;">
                        <h2 style="color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
                            <?php echo htmlspecialchars($tema); ?>
                        </h2>

                        <?php foreach ($temoKlausimai as $index => $klausimas): ?>
                            <?php
                            $egzaminoKlausimoIdHex = bin2hex($klausimas['egzamino_klausimo_id']);
                            $selectedAnswer = isset($existingAnswers[$egzaminoKlausimoIdHex]) ? $existingAnswers[$egzaminoKlausimoIdHex] : '';
                            ?>
                            <div class="question-item" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: white;">
                                <div style="margin-bottom: 10px;">
                                    <strong><?php echo htmlspecialchars($klausimas['klausimas']); ?></strong>
                                    <span style="margin-left: 10px; color: #666;">(<?php echo htmlspecialchars($klausimas['verte']); ?> tšk.)</span>
                                </div>
                                <div style="color: #333;">
                                    <?php
                                    // Parse newline-separated answers
                                    $atsakymai = array_filter(array_map('trim', explode("\n", $klausimas['atsakymai'])));
                                    if ($atsakymai && is_array($atsakymai)) {
                                        echo '<div style="padding-left: 0;">';
                                        foreach ($atsakymai as $answerIndex => $atsakymas) {
                                            $radioId = 'q_' . $egzaminoKlausimoIdHex . '_a_' . $answerIndex;
                                            $isChecked = ($selectedAnswer === $atsakymas) ? 'checked' : '';
                                            $disabled = ($userRole === 'vartotojas') ? '' : '';

                                            echo '<div style="padding: 8px 0;">';
                                            echo '<label style="cursor: pointer; display: flex; align-items: center;">';
                                            echo '<input type="radio" name="answers[' . htmlspecialchars($egzaminoKlausimoIdHex) . ']" ';
                                            echo 'value="' . htmlspecialchars($atsakymas) . '" ';
                                            echo 'id="' . htmlspecialchars($radioId) . '" ';
                                            echo $isChecked . ' ' . $disabled . ' ';
                                            echo 'style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;">';
                                            echo '<span>' . htmlspecialchars($atsakymas) . '</span>';
                                            echo '</label>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($userRole === 'vartotojas'): ?>
                    <div style="margin: 30px 0; text-align: center; display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" class="btn btn-success" style="padding: 12px 30px; font-size: 16px;">
                            Išsaugoti atsakymus
                        </button>
                        <button type="submit" name="finish_exam" value="1" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;" onclick="return confirm('Ar tikrai norite baigti egzaminą? Atsakymai bus išsaugoti ir rezultatas bus apskaičiuotas.');">
                            Baigti egzaminą
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($userRole === 'vartotojas' && empty($error) && $endTime): ?>
        <script>
            // Countdown timer
            const endTime = <?php echo $endTime; ?> * 1000; // Convert to milliseconds

            function updateTimer() {
                const now = Date.now();
                const timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    document.getElementById('time-remaining').textContent = 'Laikas baigėsi!';
                    document.getElementById('exam-timer').style.color = '#f44336';
                    // Could redirect or disable form here
                    return;
                }

                const minutes = Math.floor(timeLeft / 60000);
                const seconds = Math.floor((timeLeft % 60000) / 1000);

                document.getElementById('time-remaining').textContent =
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                // Change color when less than 5 minutes left
                if (timeLeft < 300000) {
                    document.getElementById('exam-timer').style.color = '#f44336';
                }

                setTimeout(updateTimer, 1000);
            }

            updateTimer();
        </script>
    <?php endif; ?>
</body>
</html>
