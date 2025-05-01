<?php
session_start();

// Database connection
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "WHiUtaTMrdizq3JfNeAH";
$dbname = "295group6";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remember to change so it isn't hard-coded
$userId = '0079504835d'; 

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];

    // Delete from iBayImages first
    $stmt = $conn->prepare("DELETE FROM iBayImages WHERE itemId = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // Delete from iBayItems
    $stmt = $conn->prepare("DELETE FROM iBayItems WHERE itemId = ? AND userId = ?");
    $stmt->bind_param("ss", $deleteId, $userId);
    $stmt->execute();
    $stmt->close();
}

// Fetch listings for this seller
$sql = "SELECT * FROM iBayItems WHERE userId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Listings</title>
    <link rel="stylesheet" href="listingPage.css">
</head>
<body>
    <div class="header">
        <div class="header-left"><a href="index.php"><img src="iBay-logo.png" alt="iBay Logo"></a></div>
        <div class="header-center">Your Listings</div>
        <div class="header-right"></div>
    </div>

    <div class="test-content">
        <p>This is your content area below the header!</p>
    </div>
</body>