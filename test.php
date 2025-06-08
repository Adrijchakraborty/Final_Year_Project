<?php
$password = "123456";  // Password entered by user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo $hashedPassword;
?>
