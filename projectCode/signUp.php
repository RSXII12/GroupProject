<?php
session_start();

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

// Connect to MySQL database 
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];
    $inputEmail = $_POST['email'];
    $inputAddress = $_POST['address'];
    $inputPostcode = $_POST['postcode'];
    $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);


    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE name = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Username in use!'); window.location.href='sellerSignUp.html';</script>";
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO iBayMembers (password, name, email, address, postcode, rating) VALUES (?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssss", $hashedPassword, $inputUsername, $inputEmail, $inputAddress, $inputPostcode);
$stmt->execute();
    }
    $stmt->close();
}
$conn->close();
?>