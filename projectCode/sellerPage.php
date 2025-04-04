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

    // Retrieve form data
    $listingName = $_POST['listing-name'];
    $listingDepartment = $_POST['department'];
    $listingDescription = $_POST['description'];
    $listingPrice = $_POST['price'];
    $listingPostage = $_POST['postage-fee'];
    $listingDeadline = strtotime($_POST['deadline']);  // Convert deadline to Unix timestamp
    $listingStart = date("Y-m-d H:i:s", time());  // Get current time in DATETIME format
    // Generate a unique listing ID
    $listingId = md5(uniqid(rand(), true));  
    $userId = $_SESSION['user_id'];  // Retrieve user_id from session

    // Convert the Unix timestamp for deadline to MySQL DATETIME format
    $listingDeadlineFormatted = date("Y-m-d H:i:s", $listingDeadline); 


    // Prepare and bind the SQL query
    $stmt = $conn->prepare("INSERT INTO iBayItems (itemId, userId, title, category, description, price, postage, start, finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters:
    $stmt->bind_param("sssssssss", $listingId, $userId, $listingName, $listingDepartment, $listingDescription, $listingPrice, $listingPostage, $listingStart, $listingDeadlineFormatted);

    
    $stmt->execute();  // Execute the query

    // Close the statement
    $stmt->close();

}
    

// Close the database connection
$conn->close();
?>