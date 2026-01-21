<?php
// Database configuration
$host = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_NAME') ?: 'aistis_jakutonis';
$username = getenv('DB_USER') ?: 'stud';
$password = getenv('DB_PASSWORD') ?: 'stud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die('Duomenų bazės prisijungimo klaida: ' . $e->getMessage());
}