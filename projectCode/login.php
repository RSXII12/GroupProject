<?php
session_start();

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

// Connect to MySQL database securely
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Internal server error. Please try again later.");
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUsername = trim($_POST['username'] ?? '');
    $inputPassword = trim($_POST['password'] ?? '');

    if ($inputUsername === '' || $inputPassword === '') {
        echo "<script>alert('Username and password are required.'); window.location.href='login.html';</script>";
        exit();
    }

    // Prepare SQL statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT userId, password FROM iBayMembers WHERE name = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Internal server error.");
    }

    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hashedPassword);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($inputPassword, $hashedPassword)) {
            // Start session and redirect
            $_SESSION['userId'] = $userId;
            $_SESSION['username'] = $inputUsername;
            header("Location: buyerPage.php");
            exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found.'); window.location.href='login.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>