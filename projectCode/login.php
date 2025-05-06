<?php
session_start();

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Internal server error. Please try again later.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputEmail = trim($_POST['email'] ?? '');
    $inputPassword = trim($_POST['password'] ?? '');

    if ($inputEmail === '' || $inputPassword === '') {
        echo "<script>alert('Email and password are required.'); window.location.href='login.html';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT userId, password, name FROM iBayMembers WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Internal server error.");
    }

    $stmt->bind_param("s", $inputEmail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hashedPassword, $name);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($inputPassword, $hashedPassword)) {
            $_SESSION['userId'] = $userId;
            $_SESSION['username'] = $name; // Optional: store name for greeting
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