<?php
// Salt for password hashing from environment variable
define('PASSWORD_SALT', getenv('PASSWORD_SALT') ?: 'jY8#mK2$vP9@nQ5!');

$error = '';
$success = '';
$vardas = '';

// Function to validate password strength
function validatePassword($password) {
    $errors = [];

    // Minimum length check
    if (strlen($password) < 5) {
        $errors[] = 'Slaptažodis turi būti bent 5 simbolių ilgio';
    }

    // Lowercase letter check
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Slaptažodis turi turėti bent vieną mažąją raidę';
    }

    // Uppercase letter check
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Slaptažodis turi turėti bent vieną didžiąją raidę';
    }

    // Number check
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Slaptažodis turi turėti bent vieną skaičių';
    }

    // Special character check
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Slaptažodis turi turėti bent vieną specialųjį simbolį';
    }

    return $errors;
}

// Function to hash password with SHA-256
function hashPassword($vardas, $slaptazodis, $salt) {
    $combined = $vardas . $slaptazodis . $salt;
    return base64_encode(hash('sha256', $combined, true));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vardas = isset($_POST['vardas']) ? trim($_POST['vardas']) : '';
    $slaptazodis = isset($_POST['slaptazodis']) ? $_POST['slaptazodis'] : '';
    $slaptazodis_patvirtinimas = isset($_POST['slaptazodis_patvirtinimas']) ? $_POST['slaptazodis_patvirtinimas'] : '';

    // Validation
    if (empty($vardas)) {
        $error = 'Vardas yra privalomas';
    } elseif (empty($slaptazodis)) {
        $error = 'Slaptažodis yra privalomas';
    } elseif ($slaptazodis !== $slaptazodis_patvirtinimas) {
        $error = 'Slaptažodžiai nesutampa';
    } else {
        // Validate password strength
        $passwordErrors = validatePassword($slaptazodis);

        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            // Include database configuration
            require_once 'db.php';

            try {

                // Check if username already exists
                $checkSql = "SELECT COUNT(*) FROM vartotojas WHERE vardas = :vardas";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':vardas' => $vardas]);

                if ($checkStmt->fetchColumn() > 0) {
                    $error = 'Vartotojas su tokiu vardu jau egzistuoja';
                } else {
                    // Hash the password
                    $hashedPassword = hashPassword($vardas, $slaptazodis, PASSWORD_SALT);

                    // Generate UUID for new user
                    $uuid = random_bytes(16);

                    // Insert new user with default role 'vartotojas'
                    $insertSql = "INSERT INTO vartotojas (id, vardas, slaptazodis, role) VALUES (:id, :vardas, :slaptazodis, 'vartotojas')";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        ':id' => $uuid,
                        ':vardas' => $vardas,
                        ':slaptazodis' => $hashedPassword
                    ]);

                    $success = 'Registracija sėkminga! Dabar galite prisijungti.';
                    $vardas = ''; // Clear form
                }

            } catch (PDOException $e) {
                $error = 'Duomenų bazės klaida: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registracija</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h1>Registracija</h1>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="registracija.php">
            <div class="form-group">
                <label for="vardas">Vardas:</label>
                <input type="text" id="vardas" name="vardas" value="<?php echo htmlspecialchars($vardas); ?>" required>
            </div>

            <div class="form-group">
                <label for="slaptazodis">Slaptažodis:</label>
                <input type="password" id="slaptazodis" name="slaptazodis" required>
                <div class="password-requirements">
                    Slaptažodis turi atitikti šiuos reikalavimus:
                    <ul>
                        <li>Bent 5 simboliai</li>
                        <li>Bent viena mažoji raidė (a-z)</li>
                        <li>Bent viena didžioji raidė (A-Z)</li>
                        <li>Bent vienas skaičius (0-9)</li>
                        <li>Bent vienas specialus simbolis (!@#$%^&* ir kt.)</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="slaptazodis_patvirtinimas">Pakartokite slaptažodį:</label>
                <input type="password" id="slaptazodis_patvirtinimas" name="slaptazodis_patvirtinimas" required>
            </div>

            <button type="submit" class="btn-submit">Registruotis</button>
        </form>

        <div class="links">
            <p>Jau turite paskyrą? <a href="prisijungimas.php">Prisijunkite čia</a></p>
            <p><a href="egzaminai.php">Grįžti į pagrindinį puslapį</a></p>
        </div>
    </div>
</body>
</html>
