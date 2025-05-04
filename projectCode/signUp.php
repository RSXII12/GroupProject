<?php
session_start();

$servername = "sci-project.lboro.ac.uk";
$dbUsername = "295group6";
$dbPassword = "wHiuTatMrdizq3JfNeAH";
$dbName = "295group6";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    // Log and show generic error to avoid exposing details
    error_log("Connection failed: " . $conn->connect_error);
    die("Internal server error. Please try again later.");
}

function isValidPostcode($postcode) {
    return preg_match("/^[A-Z0-9 ]{5,8}$/i", $postcode);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Trim and fetch input safely
    $inputUsername = trim($_POST['username'] ?? '');
    $inputPassword = trim($_POST['password'] ?? '');
    $inputEmail = trim($_POST['email'] ?? '');
    $inputAddress = trim($_POST['address'] ?? '');
    $inputPostcode = trim($_POST['postcode'] ?? '');

    // Basic validation
    if (
        strlen($inputUsername) < 4 ||
        strlen($inputPassword) < 4 ||
        strlen($inputAddress) < 5 ||
        !isValidPostcode($inputPostcode)
    ) {
        echo "<script>alert('Invalid input data.'); window.location.href='sellerSignUp.html';</script>";
        exit();
    }

    // Hash password safely
    $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);
    $userId = hash("sha256", $inputUsername . $inputAddress . $inputEmail);

    // Use prepared statement to check for existing email
    $stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Internal server error.");
    }

    $stmt->bind_param("s", $inputEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo "<script>alert('Email already in use.'); window.location.href='sellerSignUp.html';</script>";
        exit();
    }
    $stmt->close();

    // Insert new user securely
    $stmt = $conn->prepare("
        INSERT INTO iBayMembers (userId, password, name, email, address, postcode)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Internal server error.");
    }

    $stmt->bind_param("ssssss", $userId, $hashedPassword, $inputUsername, $inputEmail, $inputAddress, $inputPostcode);

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Registration successful!'); window.location.href='sellerLogin.html';</script>";
    } else {
        error_log("Insert error: " . $stmt->error);
        echo "<script>alert('Could not register user. Please try again.'); window.location.href='sellerSignUp.html';</script>";
    }
}

$conn->close();
?>