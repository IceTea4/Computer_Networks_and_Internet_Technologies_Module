<?php
// Clear the cookies by setting them to expire in the past
$cookieOptions = [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'secure' => false, // Set to true in production with HTTPS
    'samesite' => 'Strict'
];

setcookie('user_role', '', $cookieOptions);
setcookie('user_id', '', $cookieOptions);

// Redirect to login page
header('Location: prisijungimas.php');
exit;
?>