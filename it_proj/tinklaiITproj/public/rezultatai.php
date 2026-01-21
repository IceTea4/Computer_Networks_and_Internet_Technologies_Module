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
$examInfo = null;
$results = [];
$scoreRule = 'best'; // Default, will be overridden by database value

// Get exam ID from query parameter
$examId = isset($_GET['egzamino_id']) ? $_GET['egzamino_id'] : null;

if (empty($examId)) {
    header('Location: egzaminai.php');
    exit;
}

try {

    // Fetch exam information including rezultatu_taisykle
    $examSql = "SELECT id, pavadinimas, data, trukme, bandomasis, perlaikomo_egzamino_id, rezultatu_taisykle
                FROM egzaminas
                WHERE id = :exam_id";
    $examStmt = $pdo->prepare($examSql);
    $examStmt->execute([':exam_id' => hex2bin($examId)]);
    $examInfo = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$examInfo) {
        $error = 'Egzaminas nerastas';
    } else {
        // Use the rezultatu_taisykle from database, default to 'best' if not set
        $scoreRule = $examInfo['rezultatu_taisykle'] ?: 'best';

        // Validate score rule
        if (!in_array($scoreRule, ['best', 'last', 'average'])) {
            $scoreRule = 'best';
        }
        // Build simple query to get results from original exam + all its retakes
        $resultsSql = "
            WITH exam_list AS (
                -- Get all exams in the chain with sequential numbers
                SELECT
                    e.id,
                    e.data,
                    ROW_NUMBER() OVER (ORDER BY e.data ASC) as attempt_number
                FROM egzaminas e
                WHERE e.id = :exam_id OR e.perlaikomo_egzamino_id = :exam_id
            ),
            last_result as (
                select
                    er.*,
                    row_number() over(partition by er.vartotojo_id order by er.data desc) as attempt_number
                from exam_list el
                join egzamino_rezultatas er on er.egzamino_id = el.id
            ) select
                v.id,
                v.vardas,
                max(er.verte) as maximum,
                max(case when er.attempt_number=1 then er.verte end) as last,
                round(avg(er.verte)) as average
            from last_result er
            left join vartotojas v on v.id = er.vartotojo_id
            group by v.id, v.vardas
            order by vardas";

        $resultsStmt = $pdo->prepare($resultsSql);
        $resultsStmt->execute([
            ':exam_id' => hex2bin($examId)
        ]);
        $results = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = 'Duomenų bazės klaida: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egzamino rezultatai</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="nav-bar">
        <span class="nav-title">Žinių testavimo sistema, Aistis Jakutonis</span>
        <div class="nav-right">
            <span class="user-role">Rolė: <?php echo htmlspecialchars($userRole); ?></span>
            <a href="egzaminai.php" class="btn btn-primary mr-10">Egzaminai</a>
            <?php if ($userRole === 'destytojas'): ?>
                <a href="klausimai.php" class="btn btn-primary mr-10">Klausimai</a>
            <?php endif; ?>
            <?php if ($userRole === 'administratorius'): ?>
                <a href="vartotojai.php" class="btn btn-primary mr-10">Vartotojai</a>
            <?php endif; ?>
            <a href="atsijungimas.php" class="btn btn-danger">Atsijungti</a>
        </div>
    </div>

    <h1>Egzamino rezultatai</h1>

    <?php if ($message): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($examInfo): ?>
        <div class="exam-info">
            <h2><?php echo htmlspecialchars($examInfo['pavadinimas']); ?></h2>
            <p><strong>Data:</strong> <?php echo $examInfo['data'] ? htmlspecialchars(utcToLocal($examInfo['data'])) : '-'; ?></p>
            <p><strong>Trukmė:</strong> <?php echo $examInfo['trukme'] ? htmlspecialchars($examInfo['trukme']) . ' min' : '-'; ?></p>
            <p><strong>Rezultatų taisyklė:</strong>
                <?php
                    if ($scoreRule === 'best') {
                        echo 'Geriausias';
                    } elseif ($scoreRule === 'last') {
                        echo 'Paskutinis';
                    } elseif ($scoreRule === 'average') {
                        echo 'Vidurkis';
                    }
                ?>
            </p>
        </div>

        <?php if (empty($results)): ?>
            <div class="no-data">Rezultatų nerasta</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Vartotojo vardas</th>
                        <th style="width: 120px;">Rezultatas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $index = 1;
                    foreach ($results as $r):
                        if ($scoreRule == 'best') {
                            $score = $r['maximum'];
                        } else if ($scoreRule == 'last') {
                            $score = $r['last'];
                        } else {
                            $score = $r['average'];
                        }

                        $scoreClass = '';
                        if ($score >= 45) {
                            $scoreClass = 'score-high';
                        } else {
                            $scoreClass = 'score-low';
                        }
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $index++; ?></td>
                            <td><?php echo htmlspecialchars($r['vardas']); ?></td>
                            <td style="text-align: center;">
                                <span class="<?php echo $scoreClass; ?>">
                                    <?php echo isset($score) ? htmlspecialchars($score) : ''; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
