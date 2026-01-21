<?php
// Start session first
session_start();

// Set timezone to UTC
date_default_timezone_set('UTC');

// Include timezone helper
require_once 'timezone.php';

// Security check - only destytojas can access this page
if (!isset($_COOKIE['user_role']) || empty($_COOKIE['user_role'])) {
    header('Location: prisijungimas.php');
    exit;
}

$userRole = $_COOKIE['user_role'];

if ($userRole !== 'destytojas') {
    header('Location: egzaminai.php');
    exit;
}

// Include database configuration
require_once 'db.php';

$error = '';
$message = '';
$temos = [];
$editMode = false;
$editId = null;

// Check if we're editing an existing exam (only on first GET entry)
if (isset($_GET['edit_id'])) {
    $editId = $_GET['edit_id'];
    $editMode = true;

    // Load exam data for editing (only if not already loaded)
    if (!isset($_SESSION['exam_wizard']) || !isset($_SESSION['exam_wizard']['edit_id']) || $_SESSION['exam_wizard']['edit_id'] !== $editId) {
        try {
            // Fetch exam details
            $examSql = "SELECT * FROM egzaminas WHERE id = :id";
            $examStmt = $pdo->prepare($examSql);
            $examStmt->execute([':id' => hex2bin($editId)]);
            $existingExam = $examStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingExam) {
                // Load existing questions
                $questionsSql = "SELECT klausimo_id FROM egzamino_klausimas WHERE egzamino_id = :exam_id";
                $questionsStmt = $pdo->prepare($questionsSql);
                $questionsStmt->execute([':exam_id' => hex2bin($editId)]);
                $existingQuestions = $questionsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Initialize wizard session with existing data
                $_SESSION['exam_wizard'] = [
                    'step' => 1,
                    'selected_klausimai' => array_map('bin2hex', $existingQuestions),
                    'exam_data' => [
                        'exam_title' => $existingExam['pavadinimas'],
                        'bandomasis' => $existingExam['bandomasis'],
                        'rezultatu_taisykle' => $existingExam['rezultatu_taisykle'] ?? '',
                        'retake_exam_id' => $existingExam['perlaikomo_egzamino_id'] ? bin2hex($existingExam['perlaikomo_egzamino_id']) : '',
                        'exam_date' => $existingExam['data'] ? utcToLocal($existingExam['data'], 'Y-m-d\TH:i') : '',
                        'exam_duration' => $existingExam['trukme'] ?? 60
                    ],
                    'edit_id' => $editId
                ];
            } else {
                $error = 'Egzaminas nerastas';
            }
        } catch (PDOException $e) {
            $error = 'Duomenų bazės klaida: ' . $e->getMessage();
        }
    }
} elseif (isset($_SESSION['exam_wizard']['edit_id'])) {
    // Continue existing edit session
    $editId = $_SESSION['exam_wizard']['edit_id'];
    $editMode = true;
} else {
    // New exam mode
    // Initialize wizard session data if not exists
    if (!isset($_SESSION['exam_wizard'])) {
        $_SESSION['exam_wizard'] = [
            'step' => 1,
            'selected_klausimai' => [],
            'exam_data' => []
        ];
    }
}

// Reset wizard to step 1
$_SESSION['exam_wizard']['step'] = 1;

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_step'])) {
        $temaCountMap = [];

        // Process submitted theme selections
        if (isset($_POST['tema_count']) && is_array($_POST['tema_count'])) {
            foreach ($_POST['tema_count'] as $tema => $count) {
                $count = intval($count);
                if ($count > 0) {
                    $temaCountMap[$tema] = $count;
                }
            }
        }

        // For each theme, randomly select questions and add to session
        if (!empty($temaCountMap)) {
            // Get currently selected questions to avoid duplicates
            $alreadySelected = isset($_SESSION['exam_wizard']['selected_klausimai'])
                ? $_SESSION['exam_wizard']['selected_klausimai']
                : [];

            foreach ($temaCountMap as $tema => $requestedCount) {
                // Build SQL to get random questions from this tema
                $sql = "SELECT id FROM klausimas WHERE tema = :tema";

                // Exclude already selected questions
                if (!empty($alreadySelected)) {
                    $excludePlaceholders = [];
                    for ($i = 0; $i < count($alreadySelected); $i++) {
                        $excludePlaceholders[] = ':exclude' . $i;
                    }
                    $excludeStr = implode(',', $excludePlaceholders);
                    $sql .= " AND id NOT IN ($excludeStr)";
                }

                $sql .= " ORDER BY RAND() LIMIT :limit";
                $stmt = $pdo->prepare($sql);

                // Bind tema parameter
                $stmt->bindValue(':tema', $tema, PDO::PARAM_STR);

                // Bind exclude parameters
                if (!empty($alreadySelected)) {
                    foreach ($alreadySelected as $index => $hexId) {
                        $stmt->bindValue(':exclude' . $index, hex2bin($hexId), PDO::PARAM_STR);
                    }
                }

                // Bind limit parameter
                $stmt->bindValue(':limit', $requestedCount, PDO::PARAM_INT);

                $stmt->execute();
                $randomQuestions = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Add to selected questions
                foreach ($randomQuestions as $qid) {
                    $hexId = bin2hex($qid);
                    $alreadySelected[] = $hexId;
                }
            }

            // Store updated selection in session
            $_SESSION['exam_wizard']['selected_klausimai'] = $alreadySelected;
        }

        $_SESSION['exam_wizard']['step'] = 2;

        // Redirect to next step
        header('Location: egzamino-klausimai.php');
        exit;
    }

    // Handle cancel
    if (isset($_POST['cancel'])) {
        unset($_SESSION['exam_wizard']);
        header('Location: egzaminai.php');
        exit;
    }

    // Fetch distinct temos from klausimas table with question counts
    $stmt = $pdo->query("SELECT tema, COUNT(*) as count FROM klausimas GROUP BY tema ORDER BY tema");
    $temosWithCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naujas egzaminas - Temos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <?php if ($userRole): ?>
                <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
                <a href="egzaminai.php" class="btn btn-primary mr-10">Egzaminai</a>
                <?php if (in_array($userRole, ['destytojas', 'administratorius'])): ?>
                    <a href="klausimai.php" class="btn btn-primary mr-10">Klausimai</a>
                <?php endif; ?>
                <?php if ($userRole === 'administratorius'): ?>
                    <a href="vartotojai.php" class="btn btn-primary mr-10">Vartotojai</a>
                <?php endif; ?>
                <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
            <?php else: ?>
                <a href="egzaminai.php" class="btn btn-primary mr-10">Egzaminai</a>
                <a href="prisijungimas.php" class="btn btn-primary mr-10">Prisijungimas</a>
                <a href="registracija.php" class="btn btn-primary">Registracija</a>
            <?php endif; ?>
        </div>
    </div>

    <h1><?php echo $editMode ? 'Redaguoti egzaminą - Temos' : 'Naujas egzaminas - Temos'; ?></h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Wizard progress -->
    <div class="wizard-progress" style="display: flex; justify-content: space-between; align-items: center; margin: 20px auto; padding: 20px; background: #f5f5f5; border-radius: 5px; max-width: 800px;">
        <div style="flex: 1;">
            <span style="font-weight: bold; color: #2196F3;">1. Temos</span>
            <span style="margin: 0 10px;">→</span>
            <span style="color: #999;">2. Klausimai</span>
            <span style="margin: 0 10px;">→</span>
            <span style="color: #999;">3. Egzaminas</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="next_step" form="temos-form" class="btn btn-primary">Toliau →</button>
            <button type="submit" name="cancel" form="temos-form" formnovalidate class="btn btn-secondary">Atšaukti</button>
        </div>
    </div>

    <div class="container" style="max-width: 800px; margin: 0 auto;">
        <div class="panel">
            <h2>Pasirinkite temas ir klausimų skaičių</h2>
            <p style="margin-bottom: 20px; color: #666;">
                Įveskite, kiek atsitiktinių klausimų norite pasirinkti iš kiekvienos temos. Palikite tuščią arba 0, jei nenorite pasirinkti klausimų iš temos.
            </p>

            <form method="POST" action="egzamino-temos.php" id="temos-form">
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Tema</th>
                            <th style="width: 200px;">Klausimų skaičius</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($temosWithCounts)): ?>
                            <tr>
                                <td colspan="2" class="empty-state">Temų nerasta</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($temosWithCounts as $temaData): ?>
                                <?php
                                    $tema = $temaData['tema'];
                                    $maxCount = $temaData['count'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tema); ?> (maks: <?php echo $maxCount; ?>)</td>
                                    <td>
                                        <input
                                            type="number"
                                            name="tema_count[<?php echo htmlspecialchars($tema); ?>]"
                                            min="0"
                                            max="<?php echo $maxCount; ?>"
                                            placeholder="0"
                                            style="width: 100%; padding: 8px;"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            </form>
        </div>
    </div>
</body>
</html>
