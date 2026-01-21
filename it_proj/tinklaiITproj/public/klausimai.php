<?php
// Start session first
session_start();

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

// Initialize session state for this page
if (!isset($_SESSION['klausimai_state'])) {
    $_SESSION['klausimai_state'] = [
        'tema' => '',
        'page' => 1
    ];
}

// Get filter and pagination from GET or session
if (isset($_GET['tema'])) {
    $_SESSION['klausimai_state']['tema'] = trim($_GET['tema']);
}
if (isset($_GET['page'])) {
    $_SESSION['klausimai_state']['page'] = max(1, intval($_GET['page']));
}

// Check if filters were cleared
if (isset($_GET['clear_filters'])) {
    $_SESSION['klausimai_state']['tema'] = '';
    $_SESSION['klausimai_state']['page'] = 1;
}

$selectedTema = $_SESSION['klausimai_state']['tema'];
$currentPage = $_SESSION['klausimai_state']['page'];

try {

    // Handle delete action
    if (isset($_GET['delete']) && !empty($_GET['delete'])) {
        $deleteId = $_GET['delete'];

        try {
            // Delete the question
            $deleteSql = "delete from klausimas where id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':id' => hex2bin($deleteId)]);

            if ($deleteStmt->rowCount() > 0) {
                $message = 'Klausimas sėkmingai ištrintas';
            } else {
                $error = 'Klausimas nerastas';
            }

            // Redirect to remove delete parameter from URL
            $redirectParams = [];
            if (!empty($selectedTema)) {
                $redirectParams['tema'] = $selectedTema;
            }
            if (isset($_GET['page']) && intval($_GET['page']) > 1) {
                $redirectParams['page'] = intval($_GET['page']);
            }
            if ($message) {
                $redirectParams['msg'] = 'deleted';
            }

            $redirectUrl = 'klausimai.php';
            if (!empty($redirectParams)) {
                $redirectUrl .= '?' . http_build_query($redirectParams);
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (PDOException $e) {
            $error = 'Klaida trinant klausimą: ' . $e->getMessage();
        }
    }

    // Check for success message
    if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
        $message = 'Klausimas sėkmingai ištrintas';
    }

    // Fetch distinct temos for filter dropdown
    $stmt = $pdo->query("select distinct tema from klausimas order by tema");
    $temos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Build query with optional filter
    $whereClause = '';
    $params = [];

    if (!empty($selectedTema)) {
        $whereClause = 'where tema = :tema';
        $params[':tema'] = $selectedTema;
    }

    // Count total records
    $countSql = "select count(*) from klausimas $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // Calculate pagination
    $totalPages = max(1, ceil($totalRecords / $itemsPerPage));
    $currentPage = min($currentPage, $totalPages); // Adjust if page exceeds total
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch paginated records
    $sql = "select id, klausimas, tema, verte, atsakymas
            from klausimas
            $whereClause
            order by tema, klausimas
            limit :limit offset :offset";

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

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}

// Helper function to build URL with preserved filters
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

    return '?' . http_build_query($params);
}

// Helper function to build URL parameters for appending (without ?)
function buildUrlParams() {
    global $currentPage, $selectedTema;

    $params = [];

    if ($currentPage > 1) {
        $params['page'] = $currentPage;
    }

    if (!empty($selectedTema)) {
        $params['tema'] = $selectedTema;
    }

    if (empty($params)) {
        return '';
    }

    return '&' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klausimai</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
            <a href="egzaminai.php" class="btn btn-primary mr-10">Egzaminai</a>
            <?php if ($userRole === 'administratorius'): ?>
                <a href="vartotojai.php" class="btn btn-primary mr-10">Vartotojai</a>
            <?php endif; ?>
            <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
        </div>
    </div>

    <h1>Klausimai</h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="controls">
        <div class="filter-group">
            <label for="tema-filter">Tema:</label>
            <select id="tema-filter" onchange="filterByTema(this.value)">
                <option value="">Visos temos</option>
                <?php foreach ($temos as $tema): ?>
                    <option value="<?php echo htmlspecialchars($tema); ?>"
                            <?php echo $selectedTema === $tema ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tema); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="klausimas.php<?php echo buildUrl(); ?>" class="btn btn-primary">+ Naujas klausimas</a>
    </div>

    <?php if (empty($klausimai)): ?>
        <div class="no-data">
            <?php if (!empty($selectedTema)): ?>
                Nerasta klausimų su tema "<?php echo htmlspecialchars($selectedTema); ?>"
            <?php else: ?>
                Nerasta klausimų
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Klausimas</th>
                    <th>Tema</th>
                    <th>Vertė</th>
                    <th>Teisingas atsakymas</th>
                    <th class="actions">Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($klausimai as $k): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($k['klausimas']); ?></td>
                        <td><?php echo htmlspecialchars($k['tema']); ?></td>
                        <td><?php echo htmlspecialchars($k['verte']); ?></td>
                        <td><?php echo htmlspecialchars($k['atsakymas']); ?></td>
                        <td class="actions">
                            <a href="klausimas.php?id=<?php echo bin2hex($k['id']); ?><?php echo buildUrlParams(); ?>" class="btn btn-primary">Redaguoti</a>
                            <button class="btn btn-danger" onclick="confirmDelete('<?php echo bin2hex($k['id']); ?>')">Trinti</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

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
    <?php endif; ?>

    <script>
        function filterByTema(tema) {
            const params = new URLSearchParams();
            if (tema) {
                params.set('tema', tema);
            } else {
                params.set('clear_filters', '1');
            }
            // Reset to page 1 when changing filter
            params.set('page', '1');
            window.location.href = 'klausimai.php?' + params.toString();
        }

        function confirmDelete(id) {
            if (confirm('Ar tikrai norite ištrinti šį klausimą?')) {
                const params = new URLSearchParams(window.location.search);
                params.set('delete', id);
                window.location.href = '?' + params.toString();
            }
        }
    </script>
</body>
</html>