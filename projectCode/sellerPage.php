<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    // Redirect to login if not logged in
    header("Location: sellerLogin.html");
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
    $listingPhotos = $_FILES['photo-input'];
    // Generate a unique listing ID
    $listingId = md5(uniqid(rand(), true));  
    $userId = $_SESSION['userId'];  

    // Convert the Unix timestamp for deadline to MySQL DATETIME format
    $listingDeadlineFormatted = date("Y-m-d H:i:s", $listingDeadline); 


    // Prepare and bind the SQL query
    $stmt = $conn->prepare("INSERT INTO iBayItems (itemId,userId, title, category, description, price, postage, start, finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters:
    $stmt->bind_param("sssssssss",$listingId, $userId, $listingName, $listingDepartment, $listingDescription, $listingPrice, $listingPostage, $listingStart, $listingDeadlineFormatted);

    
    $stmt->execute();  // Execute the query

    // Close the statement
    $stmt->close();

    if (!empty($listingPhotos['name'][0])) {
        for ($i = 0; $i < count($listingPhotos['name']); $i++) {
            $tmpName = $listingPhotos['tmp_name'][$i];
            $fileName = $listingPhotos['name'][$i];
            $fileType = $listingPhotos['type'][$i];
            $fileSize = $listingPhotos['size'][$i];
            $fileError = $listingPhotos['error'][$i];

            if ($fileError === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                $imageData = file_get_contents($tmpName);
                $imageId = hash("sha256", $fileName);
                $number = $i+1;

                // Insert into iBayImages
                $imgStmt = $conn->prepare("INSERT INTO iBayImages (imageId, image, itemType, imageSize, itemId,number) VALUES (?, ?, ?, ?, ?, ?)");
                $imgStmt->bind_param("sssdss", $imageId, $imageData, $fileType, $fileSize, $listingId,$number);
                $imgStmt->send_long_data(1, $imageData); // send image blob
                $imgStmt->execute();
                $imgStmt->close();
            }
        }
    }
}

    

// Close the database connection
$conn->close();
?>