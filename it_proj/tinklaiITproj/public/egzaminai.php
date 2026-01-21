<?php
// Start session first
session_start();

// Set timezone to UTC
date_default_timezone_set('UTC');

// Include timezone helper
require_once 'timezone.php';

// Egzaminai is accessible to everyone (no login required)
// Get user role if logged in
$userRole = isset($_COOKIE['user_role']) ? $_COOKIE['user_role'] : null;

// Include database configuration
require_once 'db.php';

$error = '';
$message = '';
$egzaminai = [];
$totalPages = 1;
$currentPage = 1;
$itemsPerPage = 10;

// Initialize session state for this page
if (!isset($_SESSION['egzaminai_state'])) {
    $_SESSION['egzaminai_state'] = [
        'date_from' => '',
        'date_to' => '',
        'page' => 1
    ];
}

// Get filter and pagination from GET or session
if (isset($_GET['date_from'])) {
    $_SESSION['egzaminai_state']['date_from'] = trim($_GET['date_from']);
}
if (isset($_GET['date_to'])) {
    $_SESSION['egzaminai_state']['date_to'] = trim($_GET['date_to']);
}
if (isset($_GET['page'])) {
    $_SESSION['egzaminai_state']['page'] = max(1, intval($_GET['page']));
}

// Check if filters were cleared
if (isset($_GET['clear_filters'])) {
    $_SESSION['egzaminai_state']['date_from'] = '';
    $_SESSION['egzaminai_state']['date_to'] = '';
    $_SESSION['egzaminai_state']['page'] = 1;
}

$dateFrom = $_SESSION['egzaminai_state']['date_from'];
$dateTo = $_SESSION['egzaminai_state']['date_to'];
$currentPage = $_SESSION['egzaminai_state']['page'];

try {

    // Handle delete action (only for destytojas and administratorius)
    if (isset($_GET['delete']) && !empty($_GET['delete']) && $userRole && in_array($userRole, ['destytojas', 'administratorius'])) {
        $deleteId = $_GET['delete'];

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Delete related egzamino_klausimas entries
            $deleteQuestionsSql = "DELETE FROM egzamino_klausimas WHERE egzamino_id = :id";
            $deleteQuestionsStmt = $pdo->prepare($deleteQuestionsSql);
            $deleteQuestionsStmt->execute([':id' => hex2bin($deleteId)]);

            // Delete the exam
            $deleteExamSql = "DELETE FROM egzaminas WHERE id = :id";
            $deleteExamStmt = $pdo->prepare($deleteExamSql);
            $deleteExamStmt->execute([':id' => hex2bin($deleteId)]);

            // Commit transaction
            $pdo->commit();

            if ($deleteExamStmt->rowCount() > 0) {
                $message = 'Egzaminas sėkmingai ištrintas';
            } else {
                $error = 'Egzaminas nerastas';
            }

            // Redirect to remove delete parameter from URL
            $redirectParams = [];
            if (!empty($dateFrom)) {
                $redirectParams['date_from'] = $dateFrom;
            }
            if (!empty($dateTo)) {
                $redirectParams['date_to'] = $dateTo;
            }
            if (isset($_GET['page']) && intval($_GET['page']) > 1) {
                $redirectParams['page'] = intval($_GET['page']);
            }
            if ($message) {
                $redirectParams['msg'] = 'deleted';
            }

            $redirectUrl = 'egzaminai.php';
            if (!empty($redirectParams)) {
                $redirectUrl .= '?' . http_build_query($redirectParams);
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Klaida trinant egzaminą: ' . $e->getMessage();
        }
    }

    // Check for success/error messages from redirects
    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === 'deleted') {
            $message = 'Egzaminas sėkmingai ištrintas';
        } elseif ($_GET['msg'] === 'created') {
            $message = 'Egzaminas sėkmingai sukurtas';
        } elseif ($_GET['msg'] === 'updated') {
            $message = 'Egzaminas sėkmingai atnaujintas';
        }
    }

    // Build query with date range filter
    $whereClause = "WHERE 1=1";
    $params = [];

    if (!empty($dateFrom)) {
        $whereClause .= " AND DATE(e.data) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $whereClause .= " AND DATE(e.data) <= :date_to";
        $params[':date_to'] = $dateTo;
    }

    // Count total records
    $countSql = "SELECT COUNT(*) FROM egzaminas e $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // Calculate pagination
    $totalPages = max(1, ceil($totalRecords / $itemsPerPage));
    $currentPage = min($currentPage, $totalPages); // Adjust if page exceeds total
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Determine if user can execute exam based on role
    $canExecuteSql = '';
    $canExecuteParams = [];
    $statusSql = '';

    if ($userRole === 'vartotojas' && !empty($_COOKIE['user_id'])) {
        // For vartotojas: check if within execution window and no existing result
        $canExecuteSql = ", (
            CASE
                WHEN UTC_TIMESTAMP() >= e.data
                AND UTC_TIMESTAMP() <= DATE_SUB(DATE_ADD(e.data, INTERVAL e.trukme MINUTE), INTERVAL 2 MINUTE)
                AND NOT EXISTS (
                    SELECT 1 FROM egzamino_rezultatas er
                    WHERE er.egzamino_id = e.id
                    AND er.vartotojo_id = :current_user_id
                )
                THEN 1
                ELSE 0
            END
        ) as can_execute";

        // Add exam status for vartotojas
        $statusSql = ", (
            SELECT er.verte
            FROM egzamino_rezultatas er
            WHERE er.egzamino_id = e.id
            AND er.vartotojo_id = :status_user_id
        ) as exam_score,
        (
            SELECT CASE
                WHEN EXISTS (
                    SELECT 1 FROM egzamino_rezultatas er
                    WHERE er.egzamino_id = e.id
                    AND er.vartotojo_id = :status_user_id2
                ) THEN 'finished'
                WHEN EXISTS (
                    SELECT 1 FROM egzamino_atsakymas ea
                    INNER JOIN egzamino_klausimas ek ON ea.egzamino_klausimo_id = ek.id
                    WHERE ek.egzamino_id = e.id
                    AND ea.vartotojo_id = :status_user_id3
                ) THEN 'in_progress'
                ELSE 'not_started'
            END
        ) as exam_status";

        $canExecuteParams[':current_user_id'] = $_COOKIE['user_id'];
        $canExecuteParams[':status_user_id'] = $_COOKIE['user_id'];
        $canExecuteParams[':status_user_id2'] = $_COOKIE['user_id'];
        $canExecuteParams[':status_user_id3'] = $_COOKIE['user_id'];
    } elseif (in_array($userRole, ['destytojas', 'administratorius'])) {
        // For destytojas/admin: always can execute
        $canExecuteSql = ", 1 as can_execute";
        $statusSql = ", NULL as exam_score, 'not_applicable' as exam_status";
    } else {
        // Not logged in or unknown role
        $canExecuteSql = ", 0 as can_execute";
        $statusSql = ", NULL as exam_score, 'not_applicable' as exam_status";
    }

    // Fetch paginated records with statistics
    $sql = "SELECT
                e.id,
                e.pavadinimas,
                e.data,
                e.trukme,
                e.bandomasis,
                e.perlaikomo_egzamino_id,
                COUNT(ek.klausimo_id) as klausimas_count,
                COALESCE(SUM(k.verte), 0) as total_verte,
                GROUP_CONCAT(DISTINCT k.tema ORDER BY k.tema SEPARATOR '\n') as temos,
                (
                    CASE
                        WHEN e.data IS NOT NULL AND e.trukme IS NOT NULL
                        AND DATE_ADD(e.data, INTERVAL e.trukme MINUTE) < NOW()
                        THEN 1
                        ELSE 0
                    END
                ) as is_ended
                $canExecuteSql
                $statusSql
            FROM egzaminas e
            LEFT JOIN egzamino_klausimas ek ON e.id = ek.egzamino_id
            LEFT JOIN klausimas k ON ek.klausimo_id = k.id
            $whereClause
            GROUP BY e.id, e.pavadinimas, e.data, e.trukme, e.bandomasis, e.perlaikomo_egzamino_id
            ORDER BY e.data DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind filter params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind can_execute params
    foreach ($canExecuteParams as $key => $value) {
        $stmt->bindValue($key, hex2bin($value), PDO::PARAM_STR);
    }

    // Bind pagination params
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $egzaminai = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}

// Helper function to build URL with preserved filters
function buildUrl($page = null) {
    global $currentPage, $dateFrom, $dateTo;

    $params = [];

    if ($page !== null) {
        $params['page'] = $page;
    } elseif ($currentPage > 1) {
        $params['page'] = $currentPage;
    }

    if (!empty($dateFrom)) {
        $params['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $params['date_to'] = $dateTo;
    }

    return empty($params) ? '' : '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egzaminai</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php initializeUserTimezone(); ?>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <?php if ($userRole): ?>
                <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
                <?php if ($userRole === 'destytojas'): ?>
                    <a href="klausimai.php" class="btn btn-primary">Klausimai</a>
                <?php endif; ?>
                <?php if ($userRole === 'administratorius'): ?>
                    <a href="vartotojai.php" class="btn btn-primary">Vartotojai</a>
                <?php endif; ?>
                <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
            <?php else: ?>
                <a href="prisijungimas.php" class="btn btn-primary">Prisijungimas</a>
                <a href="registracija.php" class="btn btn-primary">Registracija</a>
            <?php endif; ?>
        </div>
    </div>

    <h1>Egzaminai</h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="controls">
        <?php if ($userRole === 'destytojas'): ?>
            <a href="egzamino-temos.php" class="btn btn-primary">+ Naujas egzaminas</a>
        <?php endif; ?>

        <div class="filter-group">
            <label for="date-from">Nuo:</label>
            <input type="date" id="date-from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            <label for="date-to">Iki:</label>
            <input type="date" id="date-to" value="<?php echo htmlspecialchars($dateTo); ?>">
            <button onclick="applyDateFilter()" class="btn btn-primary btn-sm">Filtruoti</button>
            <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                <button onclick="clearDateFilter()" class="btn btn-secondary btn-sm">Išvalyti</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($egzaminai)): ?>
        <div class="no-data">
            <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                Nerasta egzaminų pasirinktame laikotarpyje
            <?php else: ?>
                Nerasta egzaminų
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <?php if ($userRole === 'vartotojas'): ?>
                        <th style="width: 40px;">Būsena</th>
                    <?php endif; ?>
                    <th>Pavadinimas</th>
                    <th>Data ir laikas</th>
                    <th>Trukmė (min)</th>
                    <th>Temos</th>
                    <th>Klausimų skaičius</th>
                    <th>Bendra vertė</th>
                    <?php if (in_array($userRole, ['destytojas', 'administratorius'])): ?>
                        <th class="actions">Rezultatai</th>
                    <?php endif; ?>
                    <th class="actions">Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($egzaminai as $e): ?>
                    <tr>
                        <?php if ($userRole === 'vartotojas'): ?>
                            <td style="text-align: center;">
                                <?php
                                $status = $e['exam_status'] ?? 'not_started';
                                $score = $e['exam_score'];
                                $checkmark = '✓';
                                $color = '#999'; // grey - not started

                                if ($status === 'finished') {
                                    if ($score >= 45) {
                                        $color = '#4caf50'; // green - passed
                                    } else {
                                        $color = '#f44336'; // red - failed
                                    }
                                } elseif ($status === 'in_progress') {
                                    $color = '#ff9800'; // orange - in progress
                                }
                                ?>
                                <span style="font-size: 24px; color: <?php echo $color; ?>;" title="<?php echo $status === 'finished' ? 'Baigta: ' . $score . '%' : ($status === 'in_progress' ? 'Pradėta' : 'Nepradėta'); ?>"><?php echo $checkmark; ?></span>
                                <?php if ($status === 'finished'): ?>
                                    <div style="font-size: 12px; color: <?php echo $color; ?>; font-weight: bold;"><?php echo $score; ?>%</div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($e['pavadinimas']); ?></td>
                        <td><?php echo $e['data'] ? htmlspecialchars(utcToLocal($e['data'])) : '-'; ?></td>
                        <td><?php echo $e['trukme'] ? htmlspecialchars($e['trukme']) : '-'; ?></td>
                        <td style="white-space: pre-line;"><?php echo htmlspecialchars($e['temos'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($e['klausimas_count']); ?></td>
                        <td><?php echo htmlspecialchars($e['total_verte']); ?></td>
                        <?php if ($userRole === 'destytojas'): ?>
                            <td class="actions">
                                <?php
                                // Show Rezultatai button for ended, non-bandomasis exams without perlaikomo_egzamino_id
                                $showRezultatai = $e['is_ended'] && !$e['bandomasis'] && empty($e['perlaikomo_egzamino_id']);
                                if ($showRezultatai):
                                ?>
                                    <a href="rezultatai.php?egzamino_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Rezultatai</a>
                                <?php endif; ?>
                            </td>
                        <?php elseif ($userRole === 'administratorius'): ?>
                            <td class="actions"></td>
                        <?php endif; ?>
                        <td class="actions">
                            <?php if ($userRole === 'vartotojas'): ?>
                                <?php if ($e['bandomasis']): ?>
                                    <a href="bandymai.php?egzamino_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Peržiūrėti</a>
                                <?php elseif ($e['can_execute']): ?>
                                    <a href="atsakymai.php?egzamino_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Atlikti</a>
                                <?php endif; ?>
                            <?php elseif ($userRole === 'destytojas'): ?>
                                <a href="egzamino-temos.php?edit_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Redaguoti</a>
                                <?php if ($e['can_execute']): ?>
                                    <a href="bandymai.php?egzamino_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Peržiūrėti</a>
                                <?php endif; ?>
                                <button class="btn btn-danger" onclick="confirmDelete('<?php echo bin2hex($e['id']); ?>', '<?php echo htmlspecialchars($e['pavadinimas']); ?>')">Trinti</button>
                            <?php elseif ($userRole === 'administratorius'): ?>
                                <?php if ($e['can_execute']): ?>
                                    <a href="bandymai.php?egzamino_id=<?php echo bin2hex($e['id']); ?>" class="btn btn-primary">Peržiūrėti</a>
                                <?php endif; ?>
                                <button class="btn btn-danger" onclick="confirmDelete('<?php echo bin2hex($e['id']); ?>', '<?php echo htmlspecialchars($e['pavadinimas']); ?>')">Trinti</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="egzaminai.php<?php echo buildUrl(1); ?>">«</a>
                    <a href="egzaminai.php<?php echo buildUrl($currentPage - 1); ?>">‹</a>
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
                    <a href="egzaminai.php<?php echo buildUrl($i); ?>"><?php echo $i; ?></a>
                <?php
                    endif;
                endfor;

                if ($endPage < $totalPages) {
                    echo '<span>...</span>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="egzaminai.php<?php echo buildUrl($currentPage + 1); ?>">›</a>
                    <a href="egzaminai.php<?php echo buildUrl($totalPages); ?>">»</a>
                <?php else: ?>
                    <span class="disabled">›</span>
                    <span class="disabled">»</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        function applyDateFilter() {
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const params = new URLSearchParams();
            if (dateFrom) {
                params.set('date_from', dateFrom);
            }
            if (dateTo) {
                params.set('date_to', dateTo);
            }
            window.location.href = 'egzaminai.php' + (params.toString() ? '?' + params.toString() : '');
        }

        function clearDateFilter() {
            window.location.href = 'egzaminai.php?clear_filters=1';
        }

        function confirmDelete(id, data) {
            if (confirm('Ar tikrai norite ištrinti egzaminą "' + data + '"?')) {
                const params = new URLSearchParams(window.location.search);
                params.set('delete', id);
                window.location.href = 'egzaminai.php?' + params.toString();
            }
        }
    </script>
</body>
</html>
