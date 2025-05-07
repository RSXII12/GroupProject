<?php

// DB connection config
$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

header('Content-Type: application/json');

// connect to DB
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$whereConditions = []; // conditions for WHERE clause
$params = [];          // values for bind_param
$types = "";           // type string for bind_param
$currentTimestamp = time(); // current time for filtering

// ---- Filters ----

// text search (title/description)
if (isset($_GET['searchText']) && $_GET['searchText'] !== '') {
    $searchWildcard = "%" . $_GET['searchText'] . "%";
    $whereConditions[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

// price filter
if (isset($_GET['minPrice']) && isset($_GET['maxPrice'])) {
    $minPrice = floatval($_GET['minPrice']);
    $maxPrice = floatval($_GET['maxPrice']);
    $whereConditions[] = "i.price BETWEEN ? AND ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
    $types .= "dd";
}

// time remaining (max finish time)
if (isset($_GET['timeRemaining']) && is_numeric($_GET['timeRemaining']) && intval($_GET['timeRemaining']) > 0) {
    $timeRemainingHours = intval($_GET['timeRemaining']);
    $maxFinish = $currentTimestamp + ($timeRemainingHours * 3600);
    $whereConditions[] = "UNIX_TIMESTAMP(i.finish) <= ?";
    $params[] = $maxFinish;
    $types .= "i";
}

// filter out expired items
$whereConditions[] = "UNIX_TIMESTAMP(i.finish) >= ?";
$params[] = $currentTimestamp;
$types .= "i";

// location match (postcode)
if (isset($_GET['location']) && $_GET['location'] !== '') {
    $locationWildcard = "%" . $_GET['location'] . "%";
    $whereConditions[] = "m.postcode LIKE ?";
    $params[] = $locationWildcard;
    $types .= "s";
}

// department match
if (isset($_GET['department']) && $_GET['department'] !== '') {
    $department = $_GET['department'];
    $whereConditions[] = "i.category = ?";
    $params[] = $department;
    $types .= "s";
}

// free postage only
if (isset($_GET['freePostage']) && $_GET['freePostage'] === '1') {
    $whereConditions[] = "i.postage = 0";
}

// ---- Main Query ----
$sql = "
    SELECT 
        i.itemId, i.title, i.category, i.description, i.price, i.postage, i.start, i.finish,
        i.currentBid, i.bidUser,
        img.image_data, m.postcode AS location
    FROM iBayItems i
    LEFT JOIN (
        SELECT img1.itemId, img1.image AS image_data
        FROM iBayImages img1
        INNER JOIN (
            SELECT itemId, MIN(number) AS min_number
            FROM iBayImages
            GROUP BY itemId
        ) img2 ON img1.itemId = img2.itemId AND img1.number = img2.min_number
    ) img ON i.itemId = img.itemId
    LEFT JOIN iBayMembers m ON i.userId = m.userId
";

// add conditions if any
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY i.finish ASC";

// prepare + bind + run
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// format results
$items = [];

while ($row = $result->fetch_assoc()) {
    // handle image blob
    if (!empty($row['image_data'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($row['image_data']) ?: 'image/jpeg';
        $row['image'] = 'data:' . $mimeType . ';base64,' . base64_encode($row['image_data']);
    } else {
        $row['image'] = 'placeholder.jpg'; // fallback
    }

    unset($row['image_data']);

    // calculate time left in hours
    $finishTimestamp = strtotime($row['finish']);
    $row['time_remaining'] = round(($finishTimestamp - $currentTimestamp) / 3600);

    $items[] = $row;
}

echo json_encode($items);
?>