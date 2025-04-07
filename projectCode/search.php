<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

header('Content-Type: application/json');
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Initialize query components
$whereConditions = [];
$params = [];
$types = "";
$currentTimestamp = time();

// Check if search text is provided
if (isset($_GET['searchText']) && $_GET['searchText'] !== '') {
    $searchWildcard = "%" . $_GET['searchText'] . "%";
    $whereConditions[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

// Check if price range is provided
if (isset($_GET['minPrice']) && isset($_GET['maxPrice'])) {
    $minPrice = floatval($_GET['minPrice']);
    $maxPrice = floatval($_GET['maxPrice']);
    $whereConditions[] = "i.price BETWEEN ? AND ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
    $types .= "dd";
}

// Check if time remaining is provided
if (isset($_GET['timeRemaining']) && $_GET['timeRemaining'] !== '') {
    $timeRemainingHours = intval($_GET['timeRemaining']);
    $maxFinish = $currentTimestamp + ($timeRemainingHours * 3600);
    $whereConditions[] = "i.finish <= ?";
    $params[] = $maxFinish;
    $types .= "i";
}

// Build the SQL query
$sql = "
    SELECT 
        i.itemId, i.title, i.category, i.description, i.price, i.postage, i.start, i.finish,
        img.image
    FROM iBayItems i
    LEFT JOIN iBayImages img ON i.itemId = img.itemId AND img.number = 1
";

// Add WHERE clause if there are conditions
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY i.finish ASC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

// Only bind parameters if there are any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $row['time_remaining'] = round(($row['finish'] - $currentTimestamp) / 3600);

    // Convert image blob to base64 (if you're storing actual image binary)
    if (!empty($row['image'])) {
        $row['image'] = 'data:image/jpeg;base64,' . base64_encode($row['image']);
    } else {
        $row['image'] = 'placeholder.jpg'; // Or a fallback
    }

    $items[] = $row;
}

echo json_encode($items);
?>