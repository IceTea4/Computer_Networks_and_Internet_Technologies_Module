<?php
// Start session for storing last theme
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

$message = '';
$error = '';
$temos = [];
$editMode = false;
$existingQuestion = null;

// Check if we're editing an existing question
$editId = isset($_GET['id']) ? $_GET['id'] : null;

// Get return parameters for preserving filter state
$returnTema = isset($_GET['tema']) ? trim($_GET['tema']) : '';
$returnPage = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Helper function to build URL with preserved filters
function buildUrl() {
    global $returnTema, $returnPage;

    $params = [];

    if (!empty($returnTema)) {
        $params['tema'] = $returnTema;
    }

    if ($returnPage > 1) {
        $params['page'] = $returnPage;
    }

    return empty($params) ? '' : '?' . http_build_query($params);
}

// If editing, fetch existing question data
if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM klausimas WHERE id = :id");
        $stmt->execute([':id' => hex2bin($editId)]);
        $existingQuestion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingQuestion) {
            $editMode = true;
        } else {
            $error = 'Klausimas nerastas';
        }
    } catch (PDOException $e) {
        $error = 'Duomenų bazės klaida: ' . $e->getMessage();
    }
}

// Fetch existing temos for dropdown
try {
    $stmt = $pdo->query("select distinct tema from klausimas order by tema");
    $temos = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silently fail - user can still type tema manually
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        // Validate input
        $klausimas = trim($_POST['klausimas'] ?? '');
        $atsakymai = trim($_POST['atsakymai'] ?? '');
        $tema = trim($_POST['tema'] ?? '');
        $verte = intval($_POST['verte'] ?? 0);
        $atsakymas = trim($_POST['atsakymas'] ?? '');

        if (empty($klausimas) || empty($atsakymai) || empty($tema) || empty($atsakymas)) {
            $error = 'Visi laukai yra privalomi';
        } elseif ($verte <= 0) {
            $error = 'Vertė turi būti teigiamas skaičius';
        } else {
            // Validate that atsakymas is one of the atsakymai lines
            $atsakymaiLines = array_filter(array_map('trim', explode("\n", $atsakymai)));
            if (!in_array($atsakymas, $atsakymaiLines)) {
                $error = 'Teisingas atsakymas turi būti vienas iš atsakymų sąrašo';
            }
        }

        if (empty($error)) {
            // Save tema to session for next question
            $_SESSION['last_question_tema'] = $tema;
            $_SESSION['last_question'] = $klausimas;
            $_SESSION['last_value'] = $verte;

            // Check if we're editing or creating
            $editIdPost = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;

            if ($editIdPost) {
                // Update existing question
                $sql = "UPDATE klausimas
                        SET klausimas = :klausimas, atsakymai = :atsakymai, tema = :tema,
                            verte = :verte, atsakymas = :atsakymas
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => hex2bin($editIdPost),
                    ':klausimas' => $klausimas,
                    ':atsakymai' => $atsakymai,
                    ':tema' => $tema,
                    ':verte' => $verte,
                    ':atsakymas' => $atsakymas
                ]);
            } else {
                // Generate UUID (binary 16) for new question
                $uuid = random_bytes(16);

                // Insert into database
                $sql = "INSERT INTO klausimas (id, klausimas, atsakymai, tema, verte, atsakymas)
                        VALUES (:id, :klausimas, :atsakymai, :tema, :verte, :atsakymas)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $uuid,
                    ':klausimas' => $klausimas,
                    ':atsakymai' => $atsakymai,
                    ':tema' => $tema,
                    ':verte' => $verte,
                    ':atsakymas' => $atsakymas
                ]);
            }

            // Redirect back to list with preserved filters
            $redirectParams = [];
            if (!empty($returnTema)) {
                $redirectParams['tema'] = $returnTema;
            }
            if ($returnPage > 1) {
                $redirectParams['page'] = $returnPage;
            }

            $redirectUrl = 'klausimai.php';
            if (!empty($redirectParams)) {
                $redirectUrl .= '?' . http_build_query($redirectParams);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Duomenų bazės klaida: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Redaguoti klausimą' : 'Naujas klausimas'; ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            max-width: 1200px;
            margin: 50px auto;
        }

        .content-wrapper {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .form-container {
            flex: 1;
            min-width: 0;
        }

        .example-container {
            flex: 0 0 350px;
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            position: sticky;
            top: 20px;
        }

        .example-container h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }

        .example-section {
            margin-bottom: 20px;
        }

        .example-section h4 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .example-content {
            background: white;
            padding: 12px;
            border-radius: 4px;
            border-left: 3px solid #4a90e2;
            font-size: 13px;
            line-height: 1.6;
        }

        .example-content code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        @media (max-width: 900px) {
            .content-wrapper {
                flex-direction: column;
            }

            .example-container {
                flex: 1;
                width: 100%;
                position: static;
            }
        }
    </style>
</head>
<body>
    <h1><?php echo $editMode ? 'Redaguoti klausimą' : 'Sukurti naują klausimą'; ?></h1>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="content-wrapper">
        <div class="form-container">
            <form method="POST">
                <?php if ($editMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editId); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="tema">Tema:</label>
                    <input type="text" id="tema" name="tema" list="temos" required value="<?php echo htmlspecialchars($_POST['tema'] ?? $existingQuestion['tema'] ?? $_SESSION['last_question_tema'] ?? ''); ?>">
                    <datalist id="temos">
                        <?php foreach ($temos as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="klausimas">Klausimas:</label>
                    <textarea id="klausimas" name="klausimas" required><?php echo htmlspecialchars($_POST['klausimas'] ?? $existingQuestion['klausimas'] ?? $_SESSION['last_question']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="atsakymai">Atsakymai (kiekvienas naujoje eilutėje):</label>
                    <textarea id="atsakymai" name="atsakymai" required><?php echo htmlspecialchars($_POST['atsakymai'] ?? $existingQuestion['atsakymai'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="atsakymas">Teisingas atsakymas:</label>
                    <select id="atsakymas" name="atsakymas" required>
                        <option value="">Pasirinkite atsakymą...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="verte">Vertė (taškai):</label>
                    <input type="number" id="verte" name="verte" required min="1" value="<?php echo htmlspecialchars($_POST['verte'] ?? $existingQuestion['verte'] ?? $_SESSION['last_value']); ?>">
                </div>

                <button type="submit"><?php echo $editMode ? 'Atnaujinti' : 'Išsaugoti'; ?></button>
                <a href="klausimai.php<?php echo buildUrl(); ?>" class="btn btn-secondary" style="margin-left: 10px;">Atšaukti</a>
            </form>
        </div>

        <div class="example-container">
            <h3>Pavyzdys</h3>

            <div class="example-section">
                <h4>Tema:</h4>
                <div class="example-content">
                    Matematika
                </div>
            </div>

            <div class="example-section">
                <h4>Klausimas:</h4>
                <div class="example-content">
                    Kiek bus 2 + 2?
                </div>
            </div>

            <div class="example-section">
                <h4>Atsakymai:</h4>
                <div class="example-content">
                    3<br>
                    4<br>
                    5<br>
                    22
                </div>
            </div>

            <div class="example-section">
                <h4>Teisingas atsakymas:</h4>
                <div class="example-content">
                    4
                </div>
            </div>

            <div class="example-section">
                <h4>Vertė:</h4>
                <div class="example-content">
                    1
                </div>
            </div>
        </div>
    </div>

    <script>
        const atsakymaiTextarea = document.getElementById('atsakymai');
        const atsakymasSelect = document.getElementById('atsakymas');
        const correctAnswer = <?php echo json_encode($_POST['atsakymas'] ?? $existingQuestion['atsakymas'] ?? ''); ?>;

        function updateAtsakymasDropdown() {
            const atsakymaiText = atsakymaiTextarea.value;
            const lines = atsakymaiText
                .split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);

            // Store current selection
            const currentValue = atsakymasSelect.value;

            // Clear existing options except the first one
            atsakymasSelect.innerHTML = '<option value="">Pasirinkite atsakymą...</option>';

            // Add new options
            lines.forEach(line => {
                const option = document.createElement('option');
                option.value = line;
                option.textContent = line;
                atsakymasSelect.appendChild(option);
            });

            // Restore selection if it still exists
            if (currentValue && lines.includes(currentValue)) {
                atsakymasSelect.value = currentValue;
            } else if (correctAnswer && lines.includes(correctAnswer)) {
                // Set correct answer from PHP when editing
                atsakymasSelect.value = correctAnswer;
            }
        }

        // Update dropdown when textarea changes
        atsakymaiTextarea.addEventListener('input', updateAtsakymasDropdown);
        atsakymaiTextarea.addEventListener('change', updateAtsakymasDropdown);

        // Initial population
        updateAtsakymasDropdown();
    </script>
</body>
</html>
