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
$klausimai = [];
$temos = [];
$totalPages = 1;
$currentPage = 1;
$itemsPerPage = 10;
$editMode = false;
$editId = null;
$existingExam = null;

// Determine if we're in edit mode
if (isset($_GET['edit_id'])) {
    // Fresh edit request - set up edit mode
    $editId = $_GET['edit_id'];
    $editMode = true;

    // Mark that we need to load exam data
    if (!isset($_SESSION['current_exam_edit_id']) || $_SESSION['current_exam_edit_id'] !== $editId) {
        $_SESSION['current_exam_edit_id'] = $editId;
        $_SESSION['exam_data_loaded'] = false; // Flag to load exam data
        unset($_SESSION['new_exam']);
    }
} elseif (isset($_SESSION['current_exam_edit_id'])) {
    // Continue existing edit session
    $editId = $_SESSION['current_exam_edit_id'];
    $editMode = true;
} else {
    // New exam mode
    if (!isset($_SESSION['new_exam'])) {
        $_SESSION['selected_klausimai'] = [];
        unset($_SESSION['current_exam_edit_id']);
        unset($_SESSION['exam_data_loaded']);
        $_SESSION['new_exam'] = true;
    }
}

// Initialize session variables
if (!isset($_SESSION['selected_klausimai'])) {
    $_SESSION['selected_klausimai'] = [];
}

if (!isset($_SESSION['egzaminas_state'])) {
    $_SESSION['egzaminas_state'] = [
        'tema' => '',
        'page' => 1
    ];
}

// Get filter and pagination from GET or session
if (isset($_GET['tema'])) {
    $_SESSION['egzaminas_state']['tema'] = trim($_GET['tema']);
    // Reset to page 1 when filter changes
    $_SESSION['egzaminas_state']['page'] = 1;
}
if (isset($_GET['page'])) {
    $_SESSION['egzaminas_state']['page'] = max(1, intval($_GET['page']));
}

$selectedTema = $_SESSION['egzaminas_state']['tema'];
$currentPage = $_SESSION['egzaminas_state']['page'];

try {

    // If editing, load existing exam data
    if ($editMode && $editId) {
        $examSql = "SELECT * FROM egzaminas WHERE id = :id";
        $examStmt = $pdo->prepare($examSql);
        $examStmt->execute([':id' => hex2bin($editId)]);
        $existingExam = $examStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingExam) {
            // Load existing questions into session only on first entry (when flag is false)
            if (isset($_SESSION['exam_data_loaded']) && $_SESSION['exam_data_loaded'] === false) {
                $questionsSql = "SELECT klausimo_id FROM egzamino_klausimas WHERE egzamino_id = :exam_id";
                $questionsStmt = $pdo->prepare($questionsSql);
                $questionsStmt->execute([':exam_id' => hex2bin($editId)]);
                $existingQuestions = $questionsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Convert to hex and store in session
                $_SESSION['selected_klausimai'] = array_map('bin2hex', $existingQuestions);
                $_SESSION['exam_data_loaded'] = true; // Mark as loaded
            }
        } else {
            $error = 'Egzaminas nerastas';
        }
    }

    // Handle adding a question to selected list
    if (isset($_POST['add_question']) && !empty($_POST['question_id'])) {
        $questionId = $_POST['question_id'];

        // Check if not already added
        if (!in_array($questionId, $_SESSION['selected_klausimai'])) {
            $_SESSION['selected_klausimai'][] = $questionId;
            $message = 'Klausimas pridėtas į egzaminą';
        }

        header('Location: egzaminas.php');
        exit;
    }

    // Handle removing a question from selected list
    if (isset($_POST['remove_question']) && !empty($_POST['question_id'])) {
        $questionId = $_POST['question_id'];

        $key = array_search($questionId, $_SESSION['selected_klausimai']);
        if ($key !== false) {
            unset($_SESSION['selected_klausimai'][$key]);
            $_SESSION['selected_klausimai'] = array_values($_SESSION['selected_klausimai']); // Re-index
            $message = 'Klausimas pašalintas iš egzamino';
        }

        header('Location: egzaminas.php');
        exit;
    }

    // Handle adding random questions by tema
    if (isset($_POST['add_random'])) {
        $randomTemos = isset($_POST['random_temos']) ? $_POST['random_temos'] : [];
        $randomCount = isset($_POST['random_count']) ? intval($_POST['random_count']) : 0;

        if (!empty($randomTemos) && $randomCount > 0) {
            // Build named placeholders for tema
            $placeholders = [];
            for ($i = 0; $i < count($randomTemos); $i++) {
                $placeholders[] = ':tema' . $i;
            }
            $placeholdersStr = implode(',', $placeholders);

            // Build SQL with exclusion of already selected questions
            $sql = "SELECT id FROM klausimas WHERE tema IN ($placeholdersStr)";

            // Exclude already selected questions
            if (!empty($_SESSION['selected_klausimai'])) {
                $excludePlaceholders = [];
                for ($i = 0; $i < count($_SESSION['selected_klausimai']); $i++) {
                    $excludePlaceholders[] = ':exclude' . $i;
                }
                $excludeStr = implode(',', $excludePlaceholders);
                $sql .= " AND id NOT IN ($excludeStr)";
            }

            $sql .= " ORDER BY RAND() LIMIT :limit";
            $stmt = $pdo->prepare($sql);

            // Bind tema parameters
            foreach ($randomTemos as $index => $tema) {
                $stmt->bindValue(':tema' . $index, $tema, PDO::PARAM_STR);
            }

            // Bind exclude parameters
            if (!empty($_SESSION['selected_klausimai'])) {
                foreach ($_SESSION['selected_klausimai'] as $index => $hexId) {
                    $stmt->bindValue(':exclude' . $index, hex2bin($hexId), PDO::PARAM_STR);
                }
            }

            // Bind limit parameter as integer
            $stmt->bindValue(':limit', $randomCount, PDO::PARAM_INT);

            $stmt->execute();

            $randomQuestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $addedCount = 0;

            foreach ($randomQuestions as $qid) {
                $hexId = bin2hex($qid);
                $_SESSION['selected_klausimai'][] = $hexId;
                $addedCount++;
            }

            $message = 'Pridėta ' . $addedCount . ' atsitiktinių klausimų';
        }

        header('Location: egzaminas.php');
        exit;
    }

    // Handle creating/updating the exam
    if (isset($_POST['create_exam'])) {
        $examTitle = isset($_POST['exam_title']) ? trim($_POST['exam_title']) : '';
        $bandomasis = isset($_POST['bandomasis']) ? 1 : 0;
        $perlaikomoEgzaminoId = null;
        $editIdPost = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;

        // Get retake exam ID if provided (only for non-bandomasis exams)
        if (!$bandomasis && !empty($_POST['retake_exam_id'])) {
            $perlaikomoEgzaminoId = hex2bin($_POST['retake_exam_id']);
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

        if (empty($_SESSION['selected_klausimai'])) {
            $error = 'Pasirinkite bent vieną klausimą';
        } elseif (empty($examTitle)) {
            $error = 'Įveskite egzamino pavadinimą';
        } elseif (!$bandomasis && empty($examDateUtc)) {
            $error = 'Pasirinkite egzamino datą';
        } elseif (!$bandomasis && $examDuration <= 0) {
            $error = 'Egzamino trukmė turi būti teigiamas skaičius';
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
                    if ($editIdPost) {
                        // Update existing exam
                        $examId = hex2bin($editIdPost);

                        $updateExamSql = "UPDATE egzaminas
                                          SET pavadinimas = :pavadinimas, data = :data, trukme = :trukme,
                                              bandomasis = :bandomasis, perlaikomo_egzamino_id = :perlaikomo_egzamino_id
                                          WHERE id = :id";
                        $examStmt = $pdo->prepare($updateExamSql);
                        $examStmt->execute([
                            ':id' => $examId,
                            ':pavadinimas' => $examTitle,
                            ':data' => $examDateUtc,
                            ':trukme' => $examDuration,
                            ':bandomasis' => $bandomasis,
                            ':perlaikomo_egzamino_id' => $perlaikomoEgzaminoId
                        ]);

                        // Delete existing question associations
                        $deleteQuestionsSql = "DELETE FROM egzamino_klausimas WHERE egzamino_id = :exam_id";
                        $deleteStmt = $pdo->prepare($deleteQuestionsSql);
                        $deleteStmt->execute([':exam_id' => $examId]);

                    } else {
                        // Generate UUID for new exam
                        $examId = random_bytes(16);

                        // Insert new exam
                        $insertExamSql = "INSERT INTO egzaminas (id, pavadinimas, data, trukme, bandomasis, perlaikomo_egzamino_id)
                                          VALUES (:id, :pavadinimas, :data, :trukme, :bandomasis, :perlaikomo_egzamino_id)";
                        $examStmt = $pdo->prepare($insertExamSql);
                        $examStmt->execute([
                            ':id' => $examId,
                            ':pavadinimas' => $examTitle,
                            ':data' => $examDateUtc,
                            ':trukme' => $examDuration,
                            ':bandomasis' => $bandomasis,
                            ':perlaikomo_egzamino_id' => $perlaikomoEgzaminoId
                        ]);
                    }

                    // Insert exam questions (for both create and update)
                    foreach ($_SESSION['selected_klausimai'] as $questionId) {
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

                    // Clear session variables
                    $_SESSION['selected_klausimai'] = [];
                    unset($_SESSION['current_exam_edit_id']);
                    unset($_SESSION['exam_data_loaded']);
                    unset($_SESSION['new_exam']);

                    $msgType = $editIdPost ? 'updated' : 'created';
                    header('Location: egzaminai.php?msg=' . $msgType);
                    exit;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Klaida išsaugant egzaminą: ' . $e->getMessage();
                }
            }
        }
    }

    // Handle clearing selection
    if (isset($_POST['clear_selection'])) {
        $_SESSION['selected_klausimai'] = [];
        unset($_SESSION['current_exam_edit_id']);
        unset($_SESSION['exam_data_loaded']);
        unset($_SESSION['new_exam']);
        $message = 'Pasirinkimas išvalytas';
        header('Location: egzaminas.php');
        exit;
    }

    // Fetch distinct temos for filter
    $stmt = $pdo->query("SELECT DISTINCT tema FROM klausimas ORDER BY tema");
    $temos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch ended exams that are not bandomasis for retake dropdown
    // Exclude exams that are themselves retakes (have perlaikomo_egzamino_id set)
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

    // Build query with tema filter
    $whereClause = '';
    $params = [];

    if (!empty($selectedTema)) {
        $whereClause = 'WHERE tema = :tema';
        $params[':tema'] = $selectedTema;
    }

    // Count total records for pagination
    $countSql = "SELECT COUNT(*) FROM klausimas $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // Calculate pagination
    $totalPages = max(1, ceil($totalRecords / $itemsPerPage));
    $currentPage = min($currentPage, $totalPages); // Adjust if page exceeds total
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch available questions with pagination
    $sql = "SELECT id, klausimas, tema, verte, atsakymai FROM klausimas $whereClause ORDER BY tema, klausimas LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Bind filter params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind pagination params
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $klausimai = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch selected questions details
    $selectedKlausimaiDetails = [];
    if (!empty($_SESSION['selected_klausimai'])) {
        $placeholders = str_repeat('?,', count($_SESSION['selected_klausimai']) - 1) . '?';
        $sql = "SELECT id, klausimas, tema, verte FROM klausimas WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $hexIds = array_map(function($id) { return hex2bin($id); }, $_SESSION['selected_klausimai']);
        $stmt->execute($hexIds);
        $selectedKlausimaiDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}

// Calculate total value of selected questions
$totalValue = 0;
foreach ($selectedKlausimaiDetails as $q) {
    $totalValue += $q['verte'];
}

// Helper function to build URL with preserved state
function buildUrl($page = null, $tema = null) {
    global $currentPage, $selectedTema;

    $params = [];

    if ($page !== null) {
        $params['page'] = $page;
    } elseif ($currentPage > 1) {
        $params['page'] = $currentPage;
    }

    if ($tema !== null) {
        if (!empty($tema)) {
            $params['tema'] = $tema;
        }
    } elseif (!empty($selectedTema)) {
        $params['tema'] = $selectedTema;
    }

    return 'egzaminas.php' . (empty($params) ? '' : '?' . http_build_query($params));
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naujas egzaminas</title>
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

    <h1><?php echo $editMode ? 'Redaguoti egzaminą' : 'Naujas egzaminas'; ?></h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="random-selection">
        <h3>Pridėti atsitiktinius klausimus</h3>
        <form method="POST" action="egzaminas.php">
            <div class="mb-20">
                <strong>Pasirinkite temas:</strong>
                <div class="tema-list">
                    <?php foreach ($temos as $tema): ?>
                        <label class="tema-checkbox">
                            <input type="checkbox" name="random_temos[]" value="<?php echo htmlspecialchars($tema); ?>">
                            <?php echo htmlspecialchars($tema); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="random-controls">
                <label for="random_count">Klausimų skaičius:</label>
                <input type="number" id="random_count" name="random_count" min="1" max="50" value="5" class="input-sm">
                <button type="submit" name="add_random" class="btn btn-primary">Pridėti atsitiktinius</button>
            </div>
        </form>
    </div>

    <div class="container">
        <div class="panel">
            <h2>Galimi klausimai</h2>

            <div class="controls mb-20">
                <label for="tema-filter">Tema:</label>
                <select id="tema-filter" onchange="filterByTema(this.value)">
                    <option value="">Visos temos</option>
                    <?php foreach ($temos as $tema): ?>
                        <option value="<?php echo htmlspecialchars($tema); ?>" <?php echo $selectedTema === $tema ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tema); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="question-list">
                <?php if (empty($klausimai)): ?>
                    <div class="empty-state">Klausimų nerasta</div>
                <?php else: ?>
                    <?php foreach ($klausimai as $k): ?>
                        <?php $hexId = bin2hex($k['id']); ?>
                        <?php $isSelected = in_array($hexId, $_SESSION['selected_klausimai']); ?>
                        <div class="question-item">
                            <div class="question-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($k['klausimas']); ?></strong>
                                </div>
                                <div>
                                    <?php if (!$isSelected): ?>
                                        <form method="POST" action="egzaminas.php" class="form-inline">
                                            <input type="hidden" name="question_id" value="<?php echo $hexId; ?>">
                                            <button type="submit" name="add_question" class="btn btn-primary btn-sm">Pridėti</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="color-success fw-bold">✓</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="question-meta">
                                <span class="tema-badge"><?php echo htmlspecialchars($k['tema']); ?></span>
                                <span class="value-badge">Vertė: <?php echo htmlspecialchars($k['verte']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?php echo buildUrl(1); ?>">«</a>
                        <a href="<?php echo buildUrl($currentPage - 1); ?>">‹</a>
                    <?php else: ?>
                        <span class="disabled">«</span>
                        <span class="disabled">‹</span>
                    <?php endif; ?>

                    <?php
                    // Show page numbers (with ellipsis for large page counts)
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);

                    if ($startPage > 1) {
                        echo '<span>...</span>';
                    }

                    for ($i = $startPage; $i <= $endPage; $i++):
                        if ($i == $currentPage):
                    ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo buildUrl($i); ?>"><?php echo $i; ?></a>
                    <?php
                        endif;
                    endfor;

                    if ($endPage < $totalPages) {
                        echo '<span>...</span>';
                    }
                    ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?php echo buildUrl($currentPage + 1); ?>">›</a>
                        <a href="<?php echo buildUrl($totalPages); ?>">»</a>
                    <?php else: ?>
                        <span class="disabled">›</span>
                        <span class="disabled">»</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Pasirinkti klausimai (<?php echo count($_SESSION['selected_klausimai']); ?>)</h2>

            <?php if (!empty($selectedKlausimaiDetails)): ?>
                <div class="summary">
                    <h3>Statistika</h3>
                    <p><strong>Klausimų skaičius:</strong> <?php echo count($selectedKlausimaiDetails); ?></p>
                    <p><strong>Bendra vertė:</strong> <?php echo $totalValue; ?></p>
                </div>

                <div class="mb-20">
                    <form method="POST" action="egzaminas.php">
                        <?php if ($editMode && $editId): ?>
                            <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editId); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="exam_title"><strong>Egzamino pavadinimas:</strong></label>
                            <input type="text" id="exam_title" name="exam_title" required maxlength="100" value="<?php echo htmlspecialchars($existingExam['pavadinimas'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" id="bandomasis" name="bandomasis" style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;" onchange="toggleExamTimeFields()" <?php echo ($existingExam && $existingExam['bandomasis']) ? 'checked' : ''; ?>>
                                <strong>Bandomasis egzaminas</strong>
                            </label>
                        </div>
                        <div class="form-group" id="retake-field">
                            <label for="retake_exam_id"><strong>Perlaikomas egzaminas (pasirenkama):</strong></label>
                            <select id="retake_exam_id" name="retake_exam_id">
                                <option value="">Nepasirinkta</option>
                                <?php foreach ($endedExams as $exam): ?>
                                    <?php $selected = ($existingExam && $existingExam['perlaikomo_egzamino_id'] && bin2hex($existingExam['perlaikomo_egzamino_id']) === bin2hex($exam['id'])) ? 'selected' : ''; ?>
                                    <option value="<?php echo bin2hex($exam['id']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($exam['pavadinimas']); ?>
                                        (<?php echo utcToLocal($exam['data'], 'Y-m-d H:i'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="exam-time-fields" style="display: flex; gap: 20px; align-items: flex-start;">
                            <div style="flex: 1;">
                                <label for="exam_date"><strong>Egzamino data ir laikas (jūsų laiko juosta):</strong></label>
                                <input type="datetime-local" id="exam_date" name="exam_date" required
                                       min="<?php echo utcToLocal(date('Y-m-d H:i:s', strtotime('+1 minute')), 'Y-m-d\TH:i'); ?>"
                                       value="<?php echo $existingExam && $existingExam['data'] ? utcToLocal($existingExam['data'], 'Y-m-d\TH:i') : ''; ?>">
                            </div>
                            <div style="width: 200px;">
                                <label for="exam_duration"><strong>Trukmė (minutėmis):</strong></label>
                                <input type="number" id="exam_duration" name="exam_duration" required
                                       min="1" max="600" value="<?php echo $existingExam && $existingExam['trukme'] ? htmlspecialchars($existingExam['trukme']) : '60'; ?>">
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" name="create_exam" class="btn btn-primary btn-lg mr-10"><?php echo $editMode ? 'Atnaujinti egzaminą' : 'Sukurti egzaminą'; ?></button>
                            <button type="submit" name="clear_selection" formnovalidate class="btn btn-danger btn-lg">Išvalyti</button>
                            <?php if ($editMode): ?>
                                <a href="egzaminai.php" class="btn btn-secondary btn-lg" style="margin-left: 10px;">Atšaukti</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="question-list">
                <?php if (empty($selectedKlausimaiDetails)): ?>
                    <div class="empty-state">Nepasirinkta klausimų</div>
                <?php else: ?>
                    <?php foreach ($selectedKlausimaiDetails as $k): ?>
                        <?php $hexId = bin2hex($k['id']); ?>
                        <div class="question-item">
                            <div class="question-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($k['klausimas']); ?></strong>
                                </div>
                                <div>
                                    <form method="POST" action="egzaminas.php" class="form-inline">
                                        <input type="hidden" name="question_id" value="<?php echo $hexId; ?>">
                                        <button type="submit" name="remove_question" class="btn btn-danger btn-sm">Pašalinti</button>
                                    </form>
                                </div>
                            </div>
                            <div class="question-meta">
                                <span class="tema-badge"><?php echo htmlspecialchars($k['tema']); ?></span>
                                <span class="value-badge">Vertė: <?php echo htmlspecialchars($k['verte']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterByTema(tema) {
            const params = new URLSearchParams();
            // Always set tema parameter (empty string clears filter)
            params.set('tema', tema);
            params.set('page', '1');
            window.location.href = 'egzaminas.php?' + params.toString();
        }

        function toggleExamTimeFields() {
            const bandomasisCheckbox = document.getElementById('bandomasis');
            const examTimeFields = document.getElementById('exam-time-fields');
            const retakeField = document.getElementById('retake-field');
            const examDateInput = document.getElementById('exam_date');
            const examDurationInput = document.getElementById('exam_duration');
            const retakeSelect = document.getElementById('retake_exam_id');

            if (bandomasisCheckbox.checked) {
                // Hide and disable time fields and retake dropdown
                examTimeFields.style.display = 'none';
                retakeField.style.display = 'none';
                examDateInput.required = false;
                examDateInput.disabled = true;
                examDurationInput.required = false;
                examDurationInput.disabled = true;
                retakeSelect.disabled = true;
                retakeSelect.value = ''; // Clear selection
            } else {
                // Show and enable time fields and retake dropdown
                examTimeFields.style.display = 'flex';
                retakeField.style.display = 'block';
                examDateInput.required = true;
                examDateInput.disabled = false;
                examDurationInput.required = true;
                examDurationInput.disabled = false;
                retakeSelect.disabled = false;
            }
        }
    </script>
</body>
</html>
