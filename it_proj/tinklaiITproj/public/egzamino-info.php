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

// Check if wizard session exists
if (!isset($_SESSION['exam_wizard'])) {
    header('Location: egzamino-temos.php');
    exit;
}

// Include database configuration
require_once 'db.php';

$error = '';
$message = '';
$endedExams = [];
$totalQuestions = count($_SESSION['exam_wizard']['selected_klausimai']);
$totalValue = 0;
$editMode = isset($_SESSION['exam_wizard']['edit_id']);
$editId = $editMode ? $_SESSION['exam_wizard']['edit_id'] : null;

// Update wizard step
$_SESSION['exam_wizard']['step'] = 3;

// Get form values from session (if returning from next step)
$examData = $_SESSION['exam_wizard']['exam_data'] ?? [];
$examTitle = $examData['exam_title'] ?? '';
$bandomasis = $examData['bandomasis'] ?? false;
$rezultatuTaisykle = $examData['rezultatu_taisykle'] ?? '';
$retakeExamId = $examData['retake_exam_id'] ?? '';
$examDate = $examData['exam_date'] ?? '';
$examDuration = $examData['exam_duration'] ?? 60;

try {
    // Handle back button - save form data to session before going back
    if (isset($_POST['back'])) {
        // Store current form values in session
        $_SESSION['exam_wizard']['exam_data'] = [
            'exam_title' => $_POST['exam_title'] ?? '',
            'bandomasis' => isset($_POST['bandomasis']),
            'rezultatu_taisykle' => $_POST['rezultatu_taisykle'] ?? '',
            'retake_exam_id' => $_POST['retake_exam_id'] ?? '',
            'exam_date' => $_POST['exam_date'] ?? '',
            'exam_duration' => $_POST['exam_duration'] ?? 60
        ];
        header('Location: egzamino-klausimai.php');
        exit;
    }

    // Handle cancel
    if (isset($_POST['cancel'])) {
        unset($_SESSION['exam_wizard']);
        header('Location: egzaminai.php');
        exit;
    }

    // Handle form submission
    if (isset($_POST['create_exam'])) {
        $examTitle = isset($_POST['exam_title']) ? trim($_POST['exam_title']) : '';
        $bandomasis = isset($_POST['bandomasis']) ? 1 : 0;
        $perlaikomoEgzaminoId = null;
        $rezultatuTaisykle = null;

        // Get retake exam ID and results rule if provided (only for non-bandomasis exams)
        if (!$bandomasis) {
            if (!empty($_POST['retake_exam_id'])) {
                $perlaikomoEgzaminoId = hex2bin($_POST['retake_exam_id']);
            }
            // Only get results rule if no retake exam is selected
            if (empty($perlaikomoEgzaminoId)) {
                $rezultatuTaisykle = isset($_POST['rezultatu_taisykle']) ? trim($_POST['rezultatu_taisykle']) : null;
            }
        }

        // Only get date/time if not bandomasis
        $examDateLocal = '';
        $examDateUtc = null;
        $examDuration = null;

        if (!$bandomasis) {
            $examDateLocal = isset($_POST['exam_date']) ? trim($_POST['exam_date']) : '';
            $examDateUtc = localToUtc($examDateLocal); // Convert to UTC for storage
            $examDuration = isset($_POST['exam_duration']) ? intval($_POST['exam_duration']) : 0;
        }

        if (empty($_SESSION['exam_wizard']['selected_klausimai'])) {
            $error = 'Pasirinkite bent vieną klausimą';
        } elseif (empty($examTitle)) {
            $error = 'Įveskite egzamino pavadinimą';
        } elseif (!$bandomasis && empty($examDateUtc)) {
            $error = 'Pasirinkite egzamino datą';
        } elseif (!$bandomasis && $examDuration <= 0) {
            $error = 'Egzamino trukmė turi būti teigiamas skaičius';
        } elseif (!$bandomasis && empty($perlaikomoEgzaminoId) && empty($rezultatuTaisykle)) {
            $error = 'Pasirinkite rezultatų taisyklę';
        } elseif (!$bandomasis && !empty($rezultatuTaisykle) && !in_array($rezultatuTaisykle, ['best', 'last', 'average'])) {
            $error = 'Neteisinga rezultatų taisyklė';
        } else {
            // Validate that date is in the future (only for non-bandomasis exams)
            if (!$bandomasis) {
                $selectedDateTime = strtotime($examDateUtc);
                $currentDateTime = time();

                if ($selectedDateTime <= $currentDateTime) {
                    $error = 'Egzamino data turi būti ateityje';
                }
            }

            if (empty($error)) {
                $pdo->beginTransaction();

                try {
                    if ($editMode && $editId) {
                        // Update existing exam
                        $examId = hex2bin($editId);

                        $updateExamSql = "UPDATE egzaminas
                                          SET pavadinimas = :pavadinimas, data = :data, trukme = :trukme,
                                              bandomasis = :bandomasis, perlaikomo_egzamino_id = :perlaikomo_egzamino_id,
                                              rezultatu_taisykle = :rezultatu_taisykle
                                          WHERE id = :id";
                        $examStmt = $pdo->prepare($updateExamSql);
                        $examStmt->execute([
                            ':id' => $examId,
                            ':pavadinimas' => $examTitle,
                            ':data' => $examDateUtc,
                            ':trukme' => $examDuration,
                            ':bandomasis' => $bandomasis,
                            ':perlaikomo_egzamino_id' => $perlaikomoEgzaminoId,
                            ':rezultatu_taisykle' => $rezultatuTaisykle
                        ]);

                        // Delete existing question associations
                        $deleteQuestionsSql = "DELETE FROM egzamino_klausimas WHERE egzamino_id = :exam_id";
                        $deleteStmt = $pdo->prepare($deleteQuestionsSql);
                        $deleteStmt->execute([':exam_id' => $examId]);

                    } else {
                        // Generate UUID for new exam
                        $examId = random_bytes(16);

                        // Insert new exam
                        $insertExamSql = "INSERT INTO egzaminas (id, pavadinimas, data, trukme, bandomasis, perlaikomo_egzamino_id, rezultatu_taisykle)
                                          VALUES (:id, :pavadinimas, :data, :trukme, :bandomasis, :perlaikomo_egzamino_id, :rezultatu_taisykle)";
                        $examStmt = $pdo->prepare($insertExamSql);
                        $examStmt->execute([
                            ':id' => $examId,
                            ':pavadinimas' => $examTitle,
                            ':data' => $examDateUtc,
                            ':trukme' => $examDuration,
                            ':bandomasis' => $bandomasis,
                            ':perlaikomo_egzamino_id' => $perlaikomoEgzaminoId,
                            ':rezultatu_taisykle' => $rezultatuTaisykle
                        ]);
                    }

                    // Insert exam questions (for both create and update)
                    foreach ($_SESSION['exam_wizard']['selected_klausimai'] as $questionId) {
                        $ekId = random_bytes(16); // Generate UUID for egzamino_klausimas
                        $insertQuestionSql = "INSERT INTO egzamino_klausimas (id, egzamino_id, klausimo_id)
                                              VALUES (:id, :egzamino_id, :klausimo_id)";
                        $questionStmt = $pdo->prepare($insertQuestionSql);
                        $questionStmt->execute([
                            ':id' => $ekId,
                            ':egzamino_id' => $examId,
                            ':klausimo_id' => hex2bin($questionId)
                        ]);
                    }

                    $pdo->commit();

                    // Clear wizard session
                    unset($_SESSION['exam_wizard']);

                    $msgType = $editMode ? 'updated' : 'created';
                    header('Location: egzaminai.php?msg=' . $msgType);
                    exit;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Klaida išsaugant egzaminą: ' . $e->getMessage();
                }
            }
        }
    }

    // Calculate total value of selected questions
    if (!empty($_SESSION['exam_wizard']['selected_klausimai'])) {
        $placeholders = str_repeat('?,', count($_SESSION['exam_wizard']['selected_klausimai']) - 1) . '?';
        $sql = "SELECT SUM(verte) as total FROM klausimas WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $hexIds = array_map(function($id) { return hex2bin($id); }, $_SESSION['exam_wizard']['selected_klausimai']);
        $stmt->execute($hexIds);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalValue = $result['total'] ?? 0;
    }

    // Fetch ended exams for retake dropdown
    $endedExamsSql = "SELECT id, pavadinimas, data, trukme
                      FROM egzaminas
                      WHERE bandomasis = 0
                      AND data IS NOT NULL
                      AND trukme IS NOT NULL
                      AND perlaikomo_egzamino_id IS NULL
                      AND DATE_ADD(data, INTERVAL trukme MINUTE) < NOW()
                      ORDER BY data DESC";
    $endedExamsStmt = $pdo->query($endedExamsSql);
    $endedExams = $endedExamsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naujas egzaminas - Informacija</title>
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

    <h1><?php echo $editMode ? 'Redaguoti egzaminą - Informacija' : 'Naujas egzaminas - Informacija'; ?></h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Wizard progress -->
    <div class="wizard-progress" style="display: flex; justify-content: space-between; align-items: center; margin: 20px auto; padding: 20px; background: #f5f5f5; border-radius: 5px; max-width: 800px;">
        <div style="flex: 1;">
            <span style="color: #999;">1. Temos</span>
            <span style="margin: 0 10px;">→</span>
            <span style="color: #999;">2. Klausimai</span>
            <span style="margin: 0 10px;">→</span>
            <span style="font-weight: bold; color: #2196F3;">3. Egzaminas</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="create_exam" form="exam-info-form" class="btn btn-primary"><?php echo $editMode ? 'Atnaujinti egzaminą' : 'Sukurti egzaminą'; ?></button>
            <button type="submit" name="back" form="exam-info-form" formnovalidate class="btn btn-secondary">← Atgal</button>
            <button type="submit" name="cancel" form="exam-info-form" formnovalidate class="btn btn-secondary">Atšaukti</button>
        </div>
    </div>

    <div class="container" style="max-width: 800px; margin: 0 auto;">
        <div class="panel">
            <!-- Summary of selected questions -->
            <div class="summary" style="margin-bottom: 30px;">
                <h3>Pasirinktų klausimų suvestinė</h3>
                <p><strong>Klausimų skaičius:</strong> <?php echo $totalQuestions; ?></p>
                <p><strong>Bendra vertė:</strong> <?php echo $totalValue; ?></p>
            </div>

            <h2>Egzamino informacija</h2>

            <form method="POST" action="egzamino-info.php" id="exam-info-form">
                <div class="form-group">
                    <label for="exam_title"><strong>Egzamino pavadinimas:</strong></label>
                    <input type="text" id="exam_title" name="exam_title" required maxlength="100" value="<?php echo htmlspecialchars($examTitle); ?>">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="bandomasis" name="bandomasis" style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;" onchange="toggleExamTimeFields()" <?php echo $bandomasis ? 'checked' : ''; ?>>
                        <strong>Bandomasis egzaminas</strong>
                    </label>
                </div>

                <div class="form-group" id="retake-field" style="<?php echo $bandomasis ? 'display: none;' : ''; ?>">
                    <label for="retake_exam_id"><strong>Perlaikomas egzaminas (pasirenkama):</strong></label>
                    <select id="retake_exam_id" name="retake_exam_id" <?php echo $bandomasis ? 'disabled' : ''; ?> onchange="toggleResultsRuleField()">
                        <option value="">Nepasirinkta</option>
                        <?php foreach ($endedExams as $exam): ?>
                            <?php $selected = (bin2hex($exam['id']) === $retakeExamId) ? 'selected' : ''; ?>
                            <option value="<?php echo bin2hex($exam['id']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($exam['pavadinimas']); ?>
                                (<?php echo utcToLocal($exam['data'], 'Y-m-d H:i'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="results-rule-field" style="<?php echo $bandomasis ? 'display: none;' : ''; ?>">
                    <label for="rezultatu_taisykle"><strong>Rezultatų taisyklė:</strong></label>
                    <select id="rezultatu_taisykle" name="rezultatu_taisykle" <?php echo $bandomasis ? '' : 'required'; ?> <?php echo $bandomasis ? 'disabled' : ''; ?>>
                        <option value="">Pasirinkite taisyklę</option>
                        <option value="best" <?php echo ($rezultatuTaisykle === 'best') ? 'selected' : ''; ?>>Geriausias</option>
                        <option value="last" <?php echo ($rezultatuTaisykle === 'last') ? 'selected' : ''; ?>>Paskutinis</option>
                        <option value="average" <?php echo ($rezultatuTaisykle === 'average') ? 'selected' : ''; ?>>Vidurkis</option>
                    </select>
                </div>

                <div class="form-group" id="exam-time-fields" style="display: <?php echo $bandomasis ? 'none' : 'flex'; ?>; gap: 20px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label for="exam_date"><strong>Egzamino data ir laikas (jūsų laiko juosta):</strong></label>
                        <input type="datetime-local" id="exam_date" name="exam_date" <?php echo $bandomasis ? '' : 'required'; ?>
                               min="<?php echo utcToLocal(date('Y-m-d H:i:s', strtotime('+1 minute')), 'Y-m-d\TH:i'); ?>"
                               value="<?php echo htmlspecialchars($examDate); ?>"
                               <?php echo $bandomasis ? 'disabled' : ''; ?>>
                    </div>
                    <div style="width: 200px;">
                        <label for="exam_duration"><strong>Trukmė (minutėmis):</strong></label>
                        <input type="number" id="exam_duration" name="exam_duration" <?php echo $bandomasis ? '' : 'required'; ?>
                               min="1" max="600" value="<?php echo htmlspecialchars($examDuration); ?>"
                               <?php echo $bandomasis ? 'disabled' : ''; ?>>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        function toggleExamTimeFields() {
            const bandomasisCheckbox = document.getElementById('bandomasis');
            const examTimeFields = document.getElementById('exam-time-fields');
            const resultsRuleField = document.getElementById('results-rule-field');
            const retakeField = document.getElementById('retake-field');
            const examDateInput = document.getElementById('exam_date');
            const examDurationInput = document.getElementById('exam_duration');
            const resultsRuleSelect = document.getElementById('rezultatu_taisykle');
            const retakeSelect = document.getElementById('retake_exam_id');

            if (bandomasisCheckbox.checked) {
                // Hide and disable time fields, results rule, and retake dropdown
                examTimeFields.style.display = 'none';
                resultsRuleField.style.display = 'none';
                retakeField.style.display = 'none';
                examDateInput.required = false;
                examDateInput.disabled = true;
                examDurationInput.required = false;
                examDurationInput.disabled = true;
                resultsRuleSelect.required = false;
                resultsRuleSelect.disabled = true;
                resultsRuleSelect.value = '';
                retakeSelect.disabled = true;
                retakeSelect.value = '';
            } else {
                // Show and enable time fields and retake dropdown
                examTimeFields.style.display = 'flex';
                retakeField.style.display = 'block';
                examDateInput.required = true;
                examDateInput.disabled = false;
                examDurationInput.required = true;
                examDurationInput.disabled = false;
                retakeSelect.disabled = false;

                // Show/hide results rule based on retake selection
                toggleResultsRuleField();
            }
        }

        function toggleResultsRuleField() {
            const retakeSelect = document.getElementById('retake_exam_id');
            const resultsRuleField = document.getElementById('results-rule-field');
            const resultsRuleSelect = document.getElementById('rezultatu_taisykle');
            const bandomasisCheckbox = document.getElementById('bandomasis');

            // Only show results rule if not bandomasis and no retake exam selected
            if (!bandomasisCheckbox.checked && !retakeSelect.value) {
                resultsRuleField.style.display = 'block';
                resultsRuleSelect.required = true;
                resultsRuleSelect.disabled = false;
            } else {
                resultsRuleField.style.display = 'none';
                resultsRuleSelect.required = false;
                resultsRuleSelect.disabled = true;
                resultsRuleSelect.value = '';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleResultsRuleField();
        });
    </script>
</body>
</html>
