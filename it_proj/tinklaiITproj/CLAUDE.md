# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Lithuanian exam management system built with PHP 8.1, MySQL 8.0, and Docker. The application manages questions, exams, and users with role-based access control.

## Development Commands

### Start the application
```bash
docker-compose up -d
```

The application runs at `http://localhost:8000`

### Stop the application
```bash
docker-compose down
```

### Recreate database with fresh schema and test data
```bash
docker-compose down -v
docker-compose up -d
```

MySQL automatically executes all `.sql` files in `initdb/` directory on first startup in alphanumeric order.

### Access MySQL directly
```bash
docker exec -it mysql mysql -uroot -prootpassword aistis_jakutonis
```

### Access PHP container for debugging
```bash
docker exec -it php bash
# Inside container, test password hashing:
php -r "echo base64_encode(hash('sha256', 'admin' . 'Admin123!' . 'jY8#mK2\$vP9@nQ5!', true));"
```

### View logs
```bash
docker-compose logs -f php
docker-compose logs -f mysql
```

## Architecture

### Database Schema

The system uses **binary(16) UUIDs** for all primary keys (stored as binary, converted with `hex2bin()`/`bin2hex()`).

**Core tables:**
- `klausimas` - Questions with tema (theme), atsakymai (answers JSON), atsakymas (correct answer), verte (points)
- `egzaminas` - Exams with data (timestamp) and optional perlaikomo_egzamino_id (retake reference)
- `egzamino_klausimas` - Junction table linking exams to questions
- `vartotojas` - Users with SHA-256 hashed passwords and role enum
- `egzamino_atsakymas` - Student answers to exam questions (references egzamino_klausimas.id)
- `egzamino_rezultatas` - Exam results with total verte and perlaikomas flag

**Key relationships:**
- Exams can reference a previous exam for retakes (`perlaikomo_egzamino_id`)
- Questions are selected into exams via `egzamino_klausimas` junction table
- Student answers reference the junction table (`egzamino_klausimo_id` → `egzamino_klausimas.id`)
- Deletion of exam cascades to `egzamino_klausimas` and `egzamino_atsakymas` but preserves `klausimas` records

### Authentication & Authorization

**Cookie-based authentication** - No sessions, role stored in httpOnly cookie:
```php
setcookie('user_role', $user['role'], [
    'expires' => time() + 86400,  // 24 hours
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Three roles** (enum in vartotojas.role):
- `administratorius` - Full access including user management
- `destytojas` - Can create/edit questions and exams
- `vartotojas` - Default role for new registrations

**Role checks** throughout PHP files:
```php
$userRole = isset($_COOKIE['user_role']) ? $_COOKIE['user_role'] : null;
if ($userRole && in_array($userRole, ['destytojas', 'administratorius'])) {
    // Privileged actions
}
```

### Password Security

**SHA-256 with salt** from environment variable:
```php
define('PASSWORD_SALT', getenv('PASSWORD_SALT') ?: 'jY8#mK2$vP9@nQ5!');

function hashPassword($vardas, $slaptazodis, $salt) {
    $combined = $vardas . $slaptazodis . $salt;
    return base64_encode(hash('sha256', $combined, true));
}
```

Password validation requires: 5+ chars, lowercase, uppercase, number, special character.

### Page Structure

**Public pages** (accessible without login):
- `egzaminai.php` - List all exams (paginated, date-range filtered, statistics)
- `klausimai.php` - Browse questions by tema
- `prisijungimas.php`, `registracija.php` - Auth pages

**Restricted pages** (role-based access):
- `egzaminas.php` - Create exam (destytojas/administratorius only) with session-based question selection
- `klausimas.php` - Create/edit question (destytojas/administratorius only)
- `vartotojai.php` - User management (administratorius only)
- `atsakymai.php` - Take exam and submit answers (vartotojas role can save answers or finish exam)

### Character Encoding

**Critical: UTF-8 (utf8mb4) everywhere** for Lithuanian characters (ą, č, ė, ę, į, š, ų, ū, ž):
- MySQL: `--character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci`
- Database: Created with `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
- PDO: `charset=utf8mb4` in connection string
- SQL files: Start with `SET NAMES utf8mb4; SET CHARACTER SET utf8mb4;`

### PDO Security Patterns

**Always use named parameters** (not positional `?`):
```php
$sql = "SELECT * FROM klausimas WHERE tema IN (:tema0, :tema1) LIMIT :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tema0', $tema0);
$stmt->bindValue(':limit', 10, PDO::PARAM_INT);
```

**Transactions for cascade operations:**
```php
$pdo->beginTransaction();
$pdo->prepare("DELETE FROM egzamino_klausimas WHERE egzamino_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM egzaminas WHERE id = :id")->execute([':id' => $id]);
$pdo->commit();
```

### Session Management in egzaminas.php

Question selection for new exam uses PHP sessions:
```php
session_start();
if (!isset($_SESSION['selected_klausimai'])) {
    $_SESSION['selected_klausimai'] = [];
}
```

Actions: add individual, add random (with duplicate prevention), remove, clear all, save exam.

**Duplicate prevention in random selection:**
```sql
SELECT id FROM klausimas
WHERE tema IN (:tema0, :tema1)
AND id NOT IN (/* already selected */)
ORDER BY RAND()
LIMIT :limit
```

### Pagination Pattern

Used in `egzaminai.php`, `klausimai.php`:
```php
$itemsPerPage = 10;
$totalPages = max(1, ceil($totalRecords / $itemsPerPage));
$currentPage = isset($_GET['page']) ? max(1, min(intval($_GET['page']), $totalPages)) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Helper function preserves filters across pages
function buildUrl($page = null) {
    global $currentPage, $dateFrom, $dateTo;
    $params = ['page' => $page ?? $currentPage];
    if (!empty($dateFrom)) $params['date_from'] = $dateFrom;
    if (!empty($dateTo)) $params['date_to'] = $dateTo;
    return empty($params) ? '' : '?' . http_build_query($params);
}
```

### Exam Taking in atsakymai.php

Students (vartotojas role) can take exams with two submission options:

**Save answers** (without finishing):
- Deletes existing answers for this exam
- Inserts new answers to `egzamino_atsakymas`
- Allows continuing later

**Finish exam** (`finish_exam` POST parameter):
1. Saves all answers (same as above)
2. Calculates results using SQL:
   ```sql
   SELECT
       SUM(k.verte) as total_points,
       SUM(CASE WHEN ea.atsakymas = k.atsakymas THEN k.verte ELSE 0 END) as earned_points
   FROM egzamino_klausimas ek
   INNER JOIN klausimas k ON ek.klausimo_id = k.id
   LEFT JOIN egzamino_atsakymas ea ON ea.egzamino_klausimo_id = ek.id
   WHERE ek.egzamino_id = :exam_id AND ea.vartotojo_id = :user_id
   ```
3. Calculates percentage in PHP: `round(($earnedPoints / $totalPoints) * 100)`
4. Inserts result into `egzamino_rezultatas` with `perlaikomas = false`
5. Redirects with score message

**Restrictions:**
- Cannot take exam after time expires
- Cannot retake if result already exists

### POST-Redirect-GET Pattern

After mutations (create/update/delete), redirect to prevent form resubmission:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle mutation
    header('Location: egzaminai.php?msg=created');
    exit;
}
```

## Environment Variables

Configured in `.env` file (loaded by docker-compose):
- `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`
- `PASSWORD_SALT` - Used for SHA-256 password hashing

Access in PHP: `getenv('DB_HOST')`, `getenv('PASSWORD_SALT')`

## Common Issues

**Admin login fails** - Regenerate password hash in PHP container:
```bash
docker exec -it php php -r "echo base64_encode(hash('sha256', 'admin' . 'YOUR_PASSWORD' . getenv('PASSWORD_SALT'), true));"
```

**Lithuanian characters broken** - Verify:
```sql
SHOW VARIABLES LIKE 'character_set%';
SHOW CREATE DATABASE aistis_jakutonis;
```

**MySQL connection refused during init** - Normal on first startup, MySQL takes ~10 seconds to initialize.

**Random selection returns fewer than requested** - Already fixed: SQL query excludes already-selected questions before LIMIT.