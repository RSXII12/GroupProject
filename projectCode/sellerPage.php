<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: sellerLogin.html");
    exit();
}

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn->begin_transaction(); // Start transaction

    try {
        $listingName = $_POST['listing-name'];
        $listingDepartment = $_POST['department'];
        $listingDescription = $_POST['description'];
        $listingPrice = $_POST['price'];
        $listingPostage = $_POST['postage-fee'];
        $listingDeadline = strtotime($_POST['deadline']);
        $listingStart = date("Y-m-d H:i:s", time());
        $listingPhotos = $_FILES['photo-input'];
        $listingId = md5(uniqid(rand(), true));  
        $userId = $_SESSION['userId'];  
        $listingDeadlineFormatted = date("Y-m-d H:i:s", $listingDeadline); 

        // Insert into iBayItems
        $stmt = $conn->prepare("INSERT INTO iBayItems (itemId, userId, title, category, description, price, postage, start, finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $listingId, $userId, $listingName, $listingDepartment, $listingDescription, $listingPrice, $listingPostage, $listingStart, $listingDeadlineFormatted);
        $stmt->execute();
        $stmt->close();

        // Insert images if any
        if (!empty($listingPhotos['name'][0])) {
            for ($i = 0; $i < count($listingPhotos['name']); $i++) {
                $tmpName = $listingPhotos['tmp_name'][$i];
                $fileName = $listingPhotos['name'][$i];
                $fileType = $listingPhotos['type'][$i];
                $fileSize = $listingPhotos['size'][$i];
                $fileError = $listingPhotos['error'][$i];

                if ($fileError === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
                    $imageData = file_get_contents($tmpName);
                    $imageId = bin2hex(random_bytes(16));
                    $number = $i + 1;

                    $imgStmt = $conn->prepare("INSERT INTO iBayImages (imageId, image, itemType, imageSize, itemId, number) VALUES (?, ?, ?, ?, ?, ?)");
                    $imgStmt->bind_param("sssdss", $imageId, $imageData, $listingDepartment, $fileSize, $listingId, $number);
                    $imgStmt->send_long_data(1, $imageData);
                    $imgStmt->execute();
                    $imgStmt->close();
                } else {
                    throw new Exception("Image upload failed for file: " . $fileName);
                }
            }
        }

        $conn->commit(); // Commit transaction
        header("Location: buyerPage.php");

    } catch (Exception $e) {
        $conn->rollback(); // Rollback all queries on error
        error_log("Transaction failed: " . $e->getMessage());
        echo "An error occurred while processing your listing. Please try again.";
        header("Location: sellerPage.html");
    }
}

$conn->close();
?>