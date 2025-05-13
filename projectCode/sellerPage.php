<?php
// sellerPage.php
// Ensure PHP uses UK time (BST/GMT)
date_default_timezone_set('Europe/London');
session_start();
header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Grab & sanitize inputs
$name        = trim($_POST['listing-name'] ?? '');
$dept        = trim($_POST['department']   ?? '');
$desc        = trim($_POST['description']  ?? '');
$price       = trim($_POST['price']        ?? '');
$postage     = trim($_POST['postage-fee']  ?? '');
$deadlineStr = $_POST['deadline']          ?? '';
$files       = $_FILES['photo-input']      ?? [];
$userId      = $_SESSION['userId'];
$itemId      = md5(uniqid('', true));

$validDepts = ['Books','Clothing','Computing','DvDs','Electronics','Collectables','Home & Garden','Music','Outdoors','Toys','Sports Equipment'];

try {
    // Server-side validation
    if (strlen($name) < 4) {
        throw new Exception('Listing name must be at least 4 characters.');
    }
    if (!in_array($dept, $validDepts, true)) {
        throw new Exception('Please select a valid department.');
    }
    if (strlen($desc) > 1000) {
        throw new Exception('Description must not exceed 1000 characters.');
    }
    if (!is_numeric($price) || $price <= 0 || $price > 5000) {
        throw new Exception('Price must be greater than 0 and at most 5000.');
    }
    if (!is_numeric($postage) || $postage < 0) {
        throw new Exception('Postage fee must be zero or positive.');
    }

    // Parse and validate deadline in Europe/London timezone
    $dt = DateTimeImmutable::createFromFormat(
        'Y-m-d\TH:i',
        $deadlineStr,
        new DateTimeZone('Europe/London')
    );
    if (! $dt) {
        throw new Exception('Deadline must be in valid YYYY-MM-DDTHH:MM format.');
    }
    $deadlineTs = $dt->getTimestamp();
    if ($deadlineTs <= time()) {
        throw new Exception('Deadline must be a valid future date/time.');
    }

    // Require at least one image
    if (empty($files['name'][0])) {
        throw new Exception('You must upload at least one photo.');
    }
    // Validate image count
    $count = count($files['name']);
    if ($count < 1 || $count > 2) {
        throw new Exception('Please upload 1 or 2 photos.');
    }

    // Validate each uploaded file
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowed = ['image/jpeg','image/png','image/webp'];
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception("Error uploading {$files['name'][$i]}.");
        }
        if ($files['size'][$i] > $maxSize) {
            throw new Exception("{$files['name'][$i]} exceeds 5MB.");
        }
        $type = mime_content_type($files['tmp_name'][$i]) ?: '';
        if (!in_array($type, $allowed, true)) {
            throw new Exception("Unsupported file type for {$files['name'][$i]}.");
        }
    }

    // Prepare DB connection
    $conn = new mysqli(
        'sci-project.lboro.ac.uk',
        '295group6',
        'wHiuTatMrdizq3JfNeAH',
        '295group6'
    );
    if ($conn->connect_error) {
        error_log('DB connect failed: ' . $conn->connect_error);
        throw new Exception('Internal server error.');
    }

    // Force MySQL session to UK local time (BST/GMT)
    $ukOffset = date('P');
    if (! $conn->query("SET time_zone = '{$ukOffset}'")) {
        error_log("Failed to set MySQL time_zone: " . $conn->error);
    }

    $conn->begin_transaction();

    // Insert listing with explicit UK-local timestamps
    $start  = date('Y-m-d H:i:s');
    $finish = $dt->format('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        "INSERT INTO iBayItems
         (itemId,userId,title,category,description,price,postage,start,finish)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
        'sssssssss',
        $itemId,
        $userId,
        $name,
        $dept,
        $desc,
        $price,
        $postage,
        $start,
        $finish
    );
    $stmt->execute();
    $stmt->close();

    // Insert images
    for ($i = 0; $i < $count; $i++) {
        $data = file_get_contents($files['tmp_name'][$i]);
        $num  = $i + 1;
        $stmt = $conn->prepare(
            "INSERT INTO iBayImages
             (imageId,image,itemType,imageSize,itemId,number)
             VALUES (UUID(),?,?,?,?,?)"
        );
        $stmt->bind_param('bisss', $data, $dept, $files['size'][$i], $itemId, $num);
        $stmt->send_long_data(0, $data);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
    $conn->close();

} catch (Exception $e) {
    if (!empty($conn) && $conn->in_transaction) {
        $conn->rollback();
        $conn->close();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>