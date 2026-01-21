<?php
// Salt for password hashing from environment variable (must match registracija.php)
define('PASSWORD_SALT', getenv('PASSWORD_SALT') ?: 'jY8#mK2$vP9@nQ5!');

$error = '';

// Function to hash password with SHA-256 (same as registration)
function hashPassword($vardas, $slaptazodis, $salt) {
    $combined = $vardas . $slaptazodis . $salt;
    return base64_encode(hash('sha256', $combined, true));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vardas = isset($_POST['vardas']) ? trim($_POST['vardas']) : '';
    $slaptazodis = isset($_POST['slaptazodis']) ? $_POST['slaptazodis'] : '';

    // Validation
    if (empty($vardas)) {
        $error = 'Vardas yra privalomas';
    } elseif (empty($slaptazodis)) {
        $error = 'Slaptažodis yra privalomas';
    } else {
        // Include database configuration
        require_once 'db.php';

        try {

            // Hash the provided password
            $hashedPassword = hashPassword($vardas, $slaptazodis, PASSWORD_SALT);

            // Check if user exists with this username and password
            $sql = "SELECT id, vardas, role FROM vartotojas WHERE vardas = :vardas AND slaptazodis = :slaptazodis";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':vardas' => $vardas,
                ':slaptazodis' => $hashedPassword
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Login successful - store role and user_id in cookies
                // Cookie expires in 24 hours (86400 seconds)
                $cookieOptions = [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => false, // Set to true in production with HTTPS
                    'samesite' => 'Strict'
                ];

                setcookie('user_role', $user['role'], $cookieOptions);
                setcookie('user_id', bin2hex($user['id']), $cookieOptions);

                header('Location: egzaminai.php');
                exit;
            } else {
                $error = 'Neteisingas vardas arba slaptažodis';
            }

        } catch (PDOException $e) {
            $error = 'Duomenų bazės klaida: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prisijungimas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h1>Prisijungimas</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="prisijungimas.php">
            <div class="form-group">
                <label for="vardas">Vardas:</label>
                <input type="text" id="vardas" name="vardas" value="<?php echo htmlspecialchars($vardas ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="slaptazodis">Slaptažodis:</label>
                <input type="password" id="slaptazodis" name="slaptazodis" required>
            </div>

            <button type="submit" class="btn-submit">Prisijungti</button>
        </form>

        <div class="links">
            <p>Neturite paskyros? <a href="registracija.php">Registruokitės čia</a></p>
            <p><a href="egzaminai.php">Svečio prisijungimas</a></p>
        </div>
    </div>
</body>
</html>
