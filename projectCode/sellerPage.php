<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: login.html");
    exit();
}
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $listingName = $_POST('listing-name')
    $listingDepartment = $_POST('department')
    $listingDescription = $_POST('description')
    $listingPrice = $_POST('price')
    $listingPostage = $_POST('postage-fee')
    $listingDeadline = strtotime($_POST('deadline'))
    $listingStart = time()
    $listingId = password_hash($listingName +$listingStart, PASSWORD_DEFAULT)
    $uderId = $_SESSION['user_id']


    $stmt = $conn->prepare("INSERT INTO iBayListings (itemId, userId, title, category, description, price, postage,start,finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");
    $stmt->bind_param("sssssssss", $listingId, $uderId, $listingName, $listingDepartment, $listingDescription,  $listingPrice, $listingPostage, $listingStart, $listingDeadline);
    $stmt->execute();
    $stmt->store_result();
    $stmt->close();
    
     


    

$conn->close();

?>
