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
$userId = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';

// Get exam ID
$egzaminoId = isset($_GET['egzamino_id']) ? $_GET['egzamino_id'] : '';

if (empty($egzaminoId)) {
    header('Location: egzaminai.php');
    exit;
}

// Include database configuration
require_once 'db.php';

$error = '';
$egzaminas = null;
$klausimai = [];

try {

    // Handle finish preview for vartotojas (saves 100% result)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_preview']) && $userRole === 'vartotojas') {
        // Check if result already exists
        $checkSql = "SELECT id FROM egzamino_rezultatas WHERE egzamino_id = :exam_id AND vartotojo_id = :user_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':exam_id' => hex2bin($egzaminoId),
            ':user_id' => hex2bin($userId)
        ]);

        if (!$checkStmt->fetch()) {
            // Save result with 100%
            $insertResultSql = "INSERT INTO egzamino_rezultatas (id, vartotojo_id, egzamino_id, verte, perlaikomas, data)
                                VALUES (:id, :user_id, :exam_id, :verte, :perlaikomas, NOW())";
            $insertResultStmt = $pdo->prepare($insertResultSql);
            $insertResultStmt->execute([
                ':id' => random_bytes(16),
                ':user_id' => hex2bin($userId),
                ':exam_id' => hex2bin($egzaminoId),
                ':verte' => 100,
                ':perlaikomas' => 0
            ]);
        }

        header('Location: egzaminai.php');
        exit;
    }

    // Fetch exam details
    $examSql = "SELECT id, pavadinimas, data, trukme FROM egzaminas WHERE id = :id";
    $examStmt = $pdo->prepare($examSql);
    $examStmt->execute([':id' => hex2bin($egzaminoId)]);
    $egzaminas = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$egzaminas) {
        $error = 'Egzaminas nerastas';
    } else {
        // Fetch questions for this exam, sorted by tema
        $questionsSql = "SELECT
                            k.id,
                            k.klausimas,
                            k.atsakymai,
                            k.tema,
                            k.verte,
                            k.atsakymas as correct_answer,
                            ek.id as egzamino_klausimo_id
                        FROM klausimas k
                        INNER JOIN egzamino_klausimas ek ON k.id = ek.klausimo_id
                        WHERE ek.egzamino_id = :exam_id
                        ORDER BY k.tema, k.klausimas";
        $questionsStmt = $pdo->prepare($questionsSql);
        $questionsStmt->execute([':exam_id' => hex2bin($egzaminoId)]);
        $klausimai = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title><?php echo $egzaminas ? htmlspecialchars($egzaminas['pavadinimas']) . ' - Peržiūra' : 'Egzaminas - Peržiūra'; ?></title>
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
        <h1><?php echo htmlspecialchars($egzaminas['pavadinimas']); ?> <span style="color: #666; font-size: 0.7em;">(Peržiūros režimas)</span></h1>
    <?php else: ?>
        <h1>Egzaminas - Peržiūra</h1>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <p><a href="egzaminai.php" class="btn btn-primary">Grįžti į egzaminų sąrašą</a></p>
    <?php else: ?>
        <div class="exam-info" style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <p><strong>Klausimų skaičius:</strong> <?php echo count($klausimai); ?></p>
        </div>

        <?php if (empty($klausimai)): ?>
            <div class="no-data">Šiame egzamine nėra klausimų</div>
        <?php else: ?>
            <form id="exam-form" method="POST" action="">
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
                            $correctAnswer = $klausimas['correct_answer'];
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
                                            $isCorrect = ($atsakymas === $correctAnswer);

                                            echo '<div class="answer-option" data-correct="' . ($isCorrect ? 'true' : 'false') . '" style="padding: 8px 0;">';
                                            echo '<label style="cursor: pointer; display: flex; align-items: center;">';
                                            echo '<input type="radio" name="answers[' . htmlspecialchars($egzaminoKlausimoIdHex) . ']" ';
                                            echo 'value="' . htmlspecialchars($atsakymas) . '" ';
                                            echo 'id="' . htmlspecialchars($radioId) . '" ';
                                            echo 'data-correct="' . ($isCorrect ? 'true' : 'false') . '" ';
                                            echo 'style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;">';
                                            echo '<span class="answer-text">' . htmlspecialchars($atsakymas) . '</span>';
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

                <div style="margin: 30px 0; text-align: center;">
                    <button type="button" id="finish-btn" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;">
                        Baigti
                    </button>
                    <?php if ($userRole === 'vartotojas'): ?>
                        <button type="submit" id="finish-preview-btn" name="finish_preview" value="1" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; display: none;">
                            Baigti peržiūrą
                        </button>
                    <?php else: ?>
                        <a href="egzaminai.php" id="finish-preview-btn" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; display: none; text-decoration: none;">
                            Baigti peržiūrą
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        document.getElementById('finish-btn')?.addEventListener('click', function() {
            // Get all answer options
            const answerOptions = document.querySelectorAll('.answer-option');

            answerOptions.forEach(option => {
                const isCorrect = option.getAttribute('data-correct') === 'true';
                const radio = option.querySelector('input[type="radio"]');
                const isSelected = radio.checked;
                const label = option.querySelector('label');
                const answerText = option.querySelector('.answer-text');

                // Reset styles
                label.style.padding = '8px';
                label.style.borderRadius = '4px';
                label.style.transition = 'all 0.3s ease';

                if (isCorrect) {
                    // Correct answer - show in green
                    label.style.backgroundColor = '#e8f5e9';
                    answerText.style.color = '#2e7d32';
                    answerText.style.fontWeight = 'bold';

                    // Add checkmark if not already present
                    if (!answerText.textContent.startsWith('✓ ')) {
                        answerText.textContent = '✓ ' + answerText.textContent;
                    }
                } else if (isSelected) {
                    // Incorrect answer that was selected - show in red
                    label.style.backgroundColor = '#ffebee';
                    answerText.style.color = '#c62828';
                    answerText.style.fontWeight = 'bold';

                    // Add X mark if not already present
                    if (!answerText.textContent.startsWith('✗ ')) {
                        answerText.textContent = '✗ ' + answerText.textContent;
                    }
                }
            });

            // Hide the Baigti button
            this.style.display = 'none';

            // Show the Baigti peržiūrą button
            document.getElementById('finish-preview-btn').style.display = 'inline-block';

            // Disable all radio buttons
            document.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.disabled = true;
            });
        });
    </script>
</body>
</html>
