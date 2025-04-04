<?php
session_start();
$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: login.html");
    exit();
}

// Welcome message
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $listingSellerId = $_SESSION['user_id'];
    $listingName = $_POST['name'];
    $listingDepartment = $_POST['department'];
    $listingDescription = $_POST['listingDescription'];
    $listingPrice = $_POST['price'];
    $listingPostage = $_POST['postage-fee'];
    $listingDeadline = $_POST['deadline'];
    $listingPhoto1 = $_FILES['photo-input']['name'][0];
    $listingId = password_hash($listingName, 'sha256');
    if(!empty($_FILES['photo-input']['name'][1])) {
        $listingPhoto2 = $_FILES['photo-input']['name'][1];
    } else {
        $listingPhoto2 = NULL;
    }
}
?>
