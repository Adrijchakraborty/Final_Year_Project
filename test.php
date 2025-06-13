<?php
$password = "anishpatel";  // Password entered by user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo $hashedPassword;
?>
