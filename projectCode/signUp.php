<?php
session_start();

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];
    $inputEmail = $_POST['email'];
    $inputAddress = $_POST['address'];
    $inputPostcode = $_POST['postcode'];
    $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);

    
    $userId = hash("sha256", $inputUsername . $inputAddress . $inputEmail);

    // Check if username already exists - allows multiple users to have the same username (allows people to use their actual name)
    $stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE email = ?");
    $stmt->bind_param("s", $inputEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email in use!'); window.location.href='sellerSignUp.html';</script>";
        exit();
    } else {
        $stmt->close(); // Close the SELECT statement

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO iBayMembers (userId, password, name, email, address, postcode) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $userId, $hashedPassword, $inputUsername, $inputEmail, $inputAddress, $inputPostcode);
        $stmt->execute() or die("Insert error: " . $stmt->error);
        $stmt->close();

        echo "<script>alert('Registration successful!'); window.location.href='sellerLogin.html';</script>";
    }
}

$conn->close();
?>