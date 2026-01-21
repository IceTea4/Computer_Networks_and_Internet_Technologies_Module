<?php
// Start session first
session_start();

// Set timezone to UTC
date_default_timezone_set('UTC');

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
$klausimai = [];
$temos = [];
$selectedKlausimaiDetails = [];
$totalValue = 0;
$totalPages = 1;
$currentPage = 1;
$itemsPerPage = 10;
$editMode = isset($_SESSION['exam_wizard']['edit_id']);

// Update wizard step
$_SESSION['exam_wizard']['step'] = 2;

// Initialize pagination state
if (!isset($_SESSION['exam_wizard']['pagination'])) {
    $_SESSION['exam_wizard']['pagination'] = [
        'tema' => '',
        'page' => 1
    ];
}

try {
    // Handle back button
    if (isset($_POST['back'])) {
        header('Location: egzamino-temos.php');
        exit;
    }

    // Handle cancel
    if (isset($_POST['cancel'])) {
        unset($_SESSION['exam_wizard']);
        header('Location: egzaminai.php');
        exit;
    }

    // Handle adding a question
    if (isset($_POST['add_question']) && !empty($_POST['question_id'])) {
        $questionId = $_POST['question_id'];
        if (!in_array($questionId, $_SESSION['exam_wizard']['selected_klausimai'])) {
            $_SESSION['exam_wizard']['selected_klausimai'][] = $questionId;
            $message = 'Klausimas pridėtas';
        }
    }

    // Handle removing a question
    if (isset($_POST['remove_question']) && !empty($_POST['question_id'])) {
        $questionId = $_POST['question_id'];
        $key = array_search($questionId, $_SESSION['exam_wizard']['selected_klausimai']);
        if ($key !== false) {
            unset($_SESSION['exam_wizard']['selected_klausimai'][$key]);
            $_SESSION['exam_wizard']['selected_klausimai'] = array_values($_SESSION['exam_wizard']['selected_klausimai']);
            $message = 'Klausimas pašalintas';
        }
    }

    // Handle next step
    if (isset($_POST['next_step'])) {
        $_SESSION['exam_wizard']['step'] = 3;
        header('Location: egzamino-info.php');
        exit;
    }

    // Handle filter and pagination
    if (isset($_GET['tema'])) {
        $_SESSION['exam_wizard']['pagination']['tema'] = trim($_GET['tema']);
        $_SESSION['exam_wizard']['pagination']['page'] = 1;
    }
    if (isset($_GET['page'])) {
        $_SESSION['exam_wizard']['pagination']['page'] = max(1, intval($_GET['page']));
    }

    $selectedTema = $_SESSION['exam_wizard']['pagination']['tema'];
    $currentPage = $_SESSION['exam_wizard']['pagination']['page'];

    // Fetch distinct temos for filter
    $stmt = $pdo->query("SELECT DISTINCT tema FROM klausimas ORDER BY tema");
    $temos = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch available questions with pagination
    $sql = "SELECT id, klausimas, tema, verte FROM klausimas $whereClause ORDER BY tema, klausimas LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $klausimai = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch selected questions details
    if (!empty($_SESSION['exam_wizard']['selected_klausimai'])) {
        $placeholders = str_repeat('?,', count($_SESSION['exam_wizard']['selected_klausimai']) - 1) . '?';
        $sql = "SELECT id, klausimas, tema, verte FROM klausimas WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);

        $hexIds = array_map(function($id) { return hex2bin($id); }, $_SESSION['exam_wizard']['selected_klausimai']);
        $stmt->execute($hexIds);
        $selectedKlausimaiDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total value
        foreach ($selectedKlausimaiDetails as $q) {
            $totalValue += $q['verte'];
        }
    }

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}

// Helper function to build URL
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

    return 'egzamino-klausimai.php' . (empty($params) ? '' : '?' . http_build_query($params));
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naujas egzaminas - Klausimai</title>
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

    <h1><?php echo $editMode ? 'Redaguoti egzaminą - Klausimai' : 'Naujas egzaminas - Klausimai'; ?></h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Wizard progress -->
    <div class="wizard-progress" style="display: flex; justify-content: space-between; align-items: center; margin: 20px auto; padding: 20px; background: #f5f5f5; border-radius: 5px; max-width: 1200px;">
        <div style="flex: 1;">
            <span style="color: #999;">1. Temos</span>
            <span style="margin: 0 10px;">→</span>
            <span style="font-weight: bold; color: #2196F3;">2. Klausimai</span>
            <span style="margin: 0 10px;">→</span>
            <span style="color: #999;">3. Egzaminas</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="next_step" form="klausimai-nav-form" class="btn btn-primary">Toliau →</button>
            <button type="submit" name="back" form="klausimai-nav-form" formnovalidate class="btn btn-secondary">← Atgal</button>
            <button type="submit" name="cancel" form="klausimai-nav-form" formnovalidate class="btn btn-secondary">Atšaukti</button>
        </div>
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
                        <?php $isSelected = in_array($hexId, $_SESSION['exam_wizard']['selected_klausimai']); ?>
                        <div class="question-item">
                            <div class="question-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($k['klausimas']); ?></strong>
                                </div>
                                <div>
                                    <?php if (!$isSelected): ?>
                                        <form method="POST" action="egzamino-klausimai.php" class="form-inline">
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
            <h2>Pasirinkti klausimai (<?php echo count($_SESSION['exam_wizard']['selected_klausimai']); ?>)</h2>

            <?php if (!empty($selectedKlausimaiDetails)): ?>
                <div class="summary">
                    <h3>Statistika</h3>
                    <p><strong>Klausimų skaičius:</strong> <?php echo count($selectedKlausimaiDetails); ?></p>
                    <p><strong>Bendra vertė:</strong> <?php echo $totalValue; ?></p>
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
                                    <form method="POST" action="egzamino-klausimai.php" class="form-inline">
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

    <!-- Hidden form for navigation buttons -->
    <form method="POST" action="egzamino-klausimai.php" id="klausimai-nav-form"></form>

    <script>
        function filterByTema(tema) {
            const params = new URLSearchParams();
            params.set('tema', tema);
            params.set('page', '1');
            window.location.href = 'egzamino-klausimai.php?' + params.toString();
        }
    </script>
</body>
</html>
