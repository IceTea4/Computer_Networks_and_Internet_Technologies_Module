<?php
// Security check - only administratorius can access this page
if (!isset($_COOKIE['user_role']) || empty($_COOKIE['user_role'])) {
    header('Location: prisijungimas.php');
    exit;
}

$userRole = $_COOKIE['user_role'];

if ($userRole !== 'administratorius') {
    header('Location: egzaminai.php');
    exit;
}

// Include database configuration
require_once 'db.php';

$error = '';
$message = '';
$vartotojai = [];
$totalPages = 1;
$currentPage = 1;
$itemsPerPage = 10;
$selectedRole = isset($_GET['role']) ? trim($_GET['role']) : '';

try {

    // Handle delete action
    if (isset($_GET['delete']) && !empty($_GET['delete'])) {
        $deleteId = $_GET['delete'];

        try {
            // Check if user is trying to delete an administrator
            $checkSql = "SELECT role FROM vartotojas WHERE id = :id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':id' => hex2bin($deleteId)]);
            $userRole = $checkStmt->fetchColumn();

            if ($userRole === 'administratorius') {
                $error = 'Negalima ištrinti administratoriaus';
            } else {
                // Delete the user
                $deleteSql = "DELETE FROM vartotojas WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute([':id' => hex2bin($deleteId)]);

                if ($deleteStmt->rowCount() > 0) {
                    $message = 'Vartotojas sėkmingai ištrintas';
                } else {
                    $error = 'Vartotojas nerastas';
                }
            }

            // Redirect to remove delete parameter from URL
            $redirectParams = [];
            if (!empty($selectedRole)) {
                $redirectParams['role'] = $selectedRole;
            }
            if (isset($_GET['page']) && intval($_GET['page']) > 1) {
                $redirectParams['page'] = intval($_GET['page']);
            }
            if ($message) {
                $redirectParams['msg'] = 'deleted';
            }
            if ($error) {
                $redirectParams['error'] = 'cannot_delete_admin';
            }

            $redirectUrl = 'vartotojai.php';
            if (!empty($redirectParams)) {
                $redirectUrl .= '?' . http_build_query($redirectParams);
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (PDOException $e) {
            $error = 'Klaida trinant vartotoją: ' . $e->getMessage();
        }
    }

    // Handle role change action
    if (isset($_POST['change_role']) && !empty($_POST['user_id']) && !empty($_POST['new_role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['new_role'];

        try {
            // Check if user is trying to change an administrator's role
            $checkSql = "SELECT role FROM vartotojas WHERE id = :id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':id' => hex2bin($userId)]);
            $currentRole = $checkStmt->fetchColumn();

            if ($currentRole === 'administratorius') {
                $error = 'Negalima keisti administratoriaus rolės';
            } elseif ($newRole === 'administratorius') {
                $error = 'Negalima paskirti administratoriaus rolės';
            } elseif (!in_array($newRole, ['destytojas', 'vartotojas'])) {
                $error = 'Neteisinga rolė';
            } else {
                // Update the user's role
                $updateSql = "UPDATE vartotojas SET role = :role WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':role' => $newRole,
                    ':id' => hex2bin($userId)
                ]);

                if ($updateStmt->rowCount() > 0) {
                    $message = 'Vartotojo rolė sėkmingai pakeista';
                } else {
                    $error = 'Nepavyko pakeisti rolės';
                }
            }

            // Redirect to remove POST data
            $redirectParams = [];
            if (!empty($selectedRole)) {
                $redirectParams['role'] = $selectedRole;
            }
            if (isset($_GET['page']) && intval($_GET['page']) > 1) {
                $redirectParams['page'] = intval($_GET['page']);
            }
            if ($message) {
                $redirectParams['msg'] = 'role_changed';
            }
            if ($error) {
                $redirectParams['error'] = 'role_change_failed';
            }

            $redirectUrl = 'vartotojai.php';
            if (!empty($redirectParams)) {
                $redirectUrl .= '?' . http_build_query($redirectParams);
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (PDOException $e) {
            $error = 'Klaida keičiant rolę: ' . $e->getMessage();
        }
    }

    // Check for success/error messages from redirects
    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === 'deleted') {
            $message = 'Vartotojas sėkmingai ištrintas';
        } elseif ($_GET['msg'] === 'role_changed') {
            $message = 'Vartotojo rolė sėkmingai pakeista';
        }
    }
    if (isset($_GET['error'])) {
        if ($_GET['error'] === 'cannot_delete_admin') {
            $error = 'Negalima ištrinti administratoriaus';
        } elseif ($_GET['error'] === 'role_change_failed') {
            $error = 'Nepavyko pakeisti rolės';
        }
    }

    // Build query with filter - exclude administrators
    $whereClause = "WHERE role != 'administratorius'";
    $params = [];

    if (!empty($selectedRole) && in_array($selectedRole, ['destytojas', 'vartotojas'])) {
        $whereClause .= " AND role = :role";
        $params[':role'] = $selectedRole;
    }

    // Count total records
    $countSql = "SELECT COUNT(*) FROM vartotojas $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // Calculate pagination
    $totalPages = max(1, ceil($totalRecords / $itemsPerPage));
    $currentPage = isset($_GET['page']) ? max(1, min(intval($_GET['page']), $totalPages)) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch paginated records
    $sql = "SELECT id, vardas, role
            FROM vartotojas
            $whereClause
            ORDER BY vardas
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind filter params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind pagination params
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $vartotojai = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}

// Helper function to build URL with preserved filters
function buildUrl($page = null, $role = null) {
    global $currentPage, $selectedRole;

    $params = [];

    if ($page !== null) {
        $params['page'] = $page;
    } elseif ($currentPage > 1) {
        $params['page'] = $currentPage;
    }

    if ($role !== null) {
        if (!empty($role)) {
            $params['role'] = $role;
        }
    } elseif (!empty($selectedRole)) {
        $params['role'] = $selectedRole;
    }

    return empty($params) ? '' : '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vartotojai</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
            <a href="egzaminai.php" class="btn btn-primary mr-10">Egzaminai</a>
            <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
        </div>
    </div>

    <h1>Vartotojai</h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="controls">
        <div class="filter-group">
            <label for="role-filter">Rolė:</label>
            <select id="role-filter" onchange="filterByRole(this.value)">
                <option value="">Visos rolės</option>
                <option value="destytojas" <?php echo $selectedRole === 'destytojas' ? 'selected' : ''; ?>>Dėstytojas</option>
                <option value="vartotojas" <?php echo $selectedRole === 'vartotojas' ? 'selected' : ''; ?>>Vartotojas</option>
            </select>
        </div>
    </div>

    <?php if (empty($vartotojai)): ?>
        <div class="no-data">
            <?php if (!empty($selectedRole)): ?>
                Nerasta vartotojų su role "<?php echo htmlspecialchars($selectedRole); ?>"
            <?php else: ?>
                Nerasta vartotojų
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Vardas</th>
                    <th>Rolė</th>
                    <th class="actions">Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vartotojai as $v): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($v['vardas']); ?></td>
                        <td>
                            <form method="POST" action="vartotojai.php<?php echo buildUrl(); ?>" class="role-form">
                                <input type="hidden" name="user_id" value="<?php echo bin2hex($v['id']); ?>">
                                <select name="new_role" required>
                                    <option value="destytojas" <?php echo $v['role'] === 'destytojas' ? 'selected' : ''; ?>>Dėstytojas</option>
                                    <option value="vartotojas" <?php echo $v['role'] === 'vartotojas' ? 'selected' : ''; ?>>Vartotojas</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-primary">Keisti</button>
                            </form>
                        </td>
                        <td class="actions">
                            <button class="btn btn-danger" onclick="confirmDelete('<?php echo bin2hex($v['id']); ?>', '<?php echo htmlspecialchars($v['vardas']); ?>')">Trinti</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="vartotojai.php<?php echo buildUrl(1); ?>">« Pirmas</a>
                    <a href="vartotojai.php<?php echo buildUrl($currentPage - 1); ?>">‹ Ankstesnis</a>
                <?php else: ?>
                    <span class="disabled">« Pirmas</span>
                    <span class="disabled">‹ Ankstesnis</span>
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
                    <a href="vartotojai.php<?php echo buildUrl($i); ?>"><?php echo $i; ?></a>
                <?php
                    endif;
                endfor;

                if ($endPage < $totalPages) {
                    echo '<span>...</span>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="vartotojai.php<?php echo buildUrl($currentPage + 1); ?>">Kitas ›</a>
                    <a href="vartotojai.php<?php echo buildUrl($totalPages); ?>">Paskutinis »</a>
                <?php else: ?>
                    <span class="disabled">Kitas ›</span>
                    <span class="disabled">Paskutinis »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        function filterByRole(role) {
            const params = new URLSearchParams();
            if (role) {
                params.set('role', role);
            }
            window.location.href = 'vartotojai.php' + (params.toString() ? '?' + params.toString() : '');
        }

        function confirmDelete(id, vardas) {
            if (confirm('Ar tikrai norite ištrinti vartotoją "' + vardas + '"?')) {
                const params = new URLSearchParams(window.location.search);
                params.set('delete', id);
                window.location.href = 'vartotojai.php?' + params.toString();
            }
        }
    </script>
</body>
</html>
