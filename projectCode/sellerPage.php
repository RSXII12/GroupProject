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

// Function to validate file type
function isValidImageType($type) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($type, $allowedTypes);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $listingName = htmlspecialchars(trim($_POST['listing-name']));
    $listingDepartment = htmlspecialchars(trim($_POST['department']));
    $listingDescription = htmlspecialchars(trim($_POST['description']));
    $listingPrice = floatval($_POST['price']);
    $listingPostage = htmlspecialchars(trim($_POST['postage-fee']));
    
    // Format the deadline properly for MySQL
    $deadlineDateTime = new DateTime($_POST['deadline']);
    $listingDeadline = $deadlineDateTime->format('Y-m-d H:i:s');
    
    $userId = $_SESSION['user_id']; // Retrieve user_id from session
    
    // Set current timestamp for start time
    $currentTime = date("Y-m-d H:i:s");
    
    // Validate required fields
    if (empty($listingName) || empty($listingDepartment) || empty($listingDescription) || 
        $listingPrice <= 0 || empty($listingDeadline)) {
        die("Error: All fields are required and price must be greater than zero.");
    }
    
    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // Prepare and bind the SQL query for item insertion
        $stmt = $conn->prepare("INSERT INTO iBayItems (userId, title, category, description, price, postage, start, finish) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters (including explicit start time):
        $bindResult = $stmt->bind_param("ssssdsss", $userId, $listingName, $listingDepartment, $listingDescription, $listingPrice, $listingPostage, $currentTime, $listingDeadline);
        
        if (!$bindResult) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }
        
        $executeResult = $stmt->execute(); // Execute the query
        
        if (!$executeResult) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Get the auto-incremented itemId
        $itemId = $conn->insert_id;
        
        if (!$itemId) {
            throw new Exception("Failed to get insert ID");
        }
        
        $stmt->close();
        
        // Process and store image files
        $imageCount = 0;
        if (isset($_FILES['photo-input']) && !empty($_FILES['photo-input']['name'][0])) {
            // Check if we have more than 2 files
            if (count($_FILES['photo-input']['name']) > 2) {
                throw new Exception("Maximum of 2 images allowed");
            }
            
            // Loop through each uploaded file (max 2)
            foreach ($_FILES['photo-input']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photo-input']['error'][$key] === 0) {
                    // Get file information
                    $fileType = $_FILES['photo-input']['type'][$key];
                    $fileSize = $_FILES['photo-input']['size'][$key];
                    
                    // Validate file type
                    if (!isValidImageType($fileType)) {
                        throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
                    }
                    
                    // Validate file size (limit to 5MB)
                    if ($fileSize > 5 * 1024 * 1024) {
                        throw new Exception("File size too large. Maximum size is 5MB.");
                    }
                    
                    // Read file content
                    $fileContent = file_get_contents($tmp_name);
                    if ($fileContent === false) {
                        throw new Exception("Failed to read uploaded file");
                    }
                    
                    // Prepare statement for image insertion
                    $imgStmt = $conn->prepare("INSERT INTO iBayImages (image, mimeType, imageSize, itemId) VALUES (?, ?, ?, ?)");
                    
                    if (!$imgStmt) {
                        throw new Exception("Prepare failed for image insert: " . $conn->error);
                    }
                    
                    // Bind parameters including the binary image data
                    $null = null;
                    $bindResult = $imgStmt->bind_param("bsii", $null, $fileType, $fileSize, $itemId);
                    
                    if (!$bindResult) {
                        throw new Exception("Binding parameters failed for image: " . $imgStmt->error);
                    }
                    
                    // Send BLOB data separately
                    $imgStmt->send_long_data(0, $fileContent);
                    
                    $executeResult = $imgStmt->execute();
                    
                    if (!$executeResult) {
                        throw new Exception("Execute failed for image: " . $imgStmt->error);
                    }
                    
                    $imgStmt->close();
                    $imageCount++;
                } else {
                    throw new Exception("File upload error: " . $_FILES['photo-input']['error'][$key]);
                }
            }
        }
        
        // Commit transaction if all operations succeeded
        $conn->commit();
        
        // Redirect to a success page or refresh
        header("Location: sellerPage.html");
        exit();
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

// Close the database connection
$conn->close();