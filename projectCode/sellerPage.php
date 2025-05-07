<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn->begin_transaction();

    try {
        $listingName = trim($_POST['listing-name'] ?? '');
        $listingDepartment = trim($_POST['department'] ?? '');
        $listingDescription = trim($_POST['description'] ?? '');
        $listingPrice = $_POST['price'] ?? '';
        $listingPostage = $_POST['postage-fee'] ?? '';
        $listingDeadlineRaw = $_POST['deadline'] ?? '';
        $listingDeadline = strtotime($listingDeadlineRaw);
        $listingStart = date("Y-m-d H:i:s");
        $listingDeadlineFormatted = date("Y-m-d H:i:s", $listingDeadline);
        $listingPhotos = $_FILES['photo-input'];
        $listingId = md5(uniqid(rand(), true));
        $userId = $_SESSION['userId'];

        // Basic validation
        if (strlen($listingName) < 4) throw new Exception("Listing name must be at least 4 characters.");
        if (empty($listingDepartment)) throw new Exception("Department is required.");
        if (!is_numeric($listingPrice) || $listingPrice <= 0) throw new Exception("Price must be a number greater than 0.");
        if (!is_numeric($listingPostage) || $listingPostage < 0) throw new Exception("Postage fee must be a non-negative number.");
        if (!$listingDeadline || $listingDeadline < time()) throw new Exception("Deadline must be a valid future date and time.");

        // Validate image uploads: max 2 files, max size 5MB each
        $maxFileSize = 5 * 1024 * 1024; // 5 MB
        if (!empty($listingPhotos['name'][0])) {
            if (count($listingPhotos['name']) > 2) {
                throw new Exception("You may upload a maximum of 2 images.");
            }

            for ($i = 0; $i < count($listingPhotos['name']); $i++) {
                $fileError = $listingPhotos['error'][$i];
                $fileSize = $listingPhotos['size'][$i];
                $fileName = $listingPhotos['name'][$i];

                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception("Error uploading image: $fileName");
                }

                if ($fileSize > $maxFileSize) {
                    throw new Exception("Image too large (max 5MB): $fileName");
                }
            }
        }

        // Insert listing
        $stmt = $conn->prepare("INSERT INTO iBayItems (itemId, userId, title, category, description, price, postage, start, finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $listingId, $userId, $listingName, $listingDepartment, $listingDescription, $listingPrice, $listingPostage, $listingStart, $listingDeadlineFormatted);
        $stmt->execute();
        $stmt->close();

        // Insert validated images
        if (!empty($listingPhotos['name'][0])) {
            for ($i = 0; $i < count($listingPhotos['name']); $i++) {
                $tmpName = $listingPhotos['tmp_name'][$i];
                $fileName = $listingPhotos['name'][$i];
                $fileSize = $listingPhotos['size'][$i];
                $fileError = $listingPhotos['error'][$i];

                if ($fileError === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {

                    // Check MIME type
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    $actualMimeType = mime_content_type($tmpName);
                    if (!in_array($actualMimeType, $allowedMimeTypes)) {
                        throw new Exception("Unsupported file type: $fileName ($actualMimeType)");
                    }
                
                    $imageData = file_get_contents($tmpName);
                    if ($imageData === false) {
                        throw new Exception("Failed to read image data for: $fileName");
                    }
                

                    $imageId = bin2hex(random_bytes(16));
                    $number = $i + 1;

                    $imgStmt = $conn->prepare("INSERT INTO iBayImages (imageId, image, itemType, imageSize, itemId, number) VALUES (?, ?, ?, ?, ?, ?)");
                    $imgStmt->bind_param("sssdss", $imageId, $imageData, $listingDepartment, $fileSize, $listingId, $number);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }

        $conn->commit();
        header("Location: sellerPage.html?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['listing_error'] = $e->getMessage();
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>"; // Optional debug output
        exit();
    }
}

$conn->close();
?>