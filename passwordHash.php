<?php
$hashedPassword = password_hash('mypassword', PASSWORD_DEFAULT);
echo $hashedPassword;
?>