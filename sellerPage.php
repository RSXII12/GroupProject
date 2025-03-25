<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: login.html");
    exit();
}

// Welcome message
echo "Welcome, " . $_SESSION['username'] . "! You are logged in as a seller.";
?>
