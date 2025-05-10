<?php
session_start();

header('Content-Type: application/json');

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

$whereConditions = [];
$params = [];
$types = "";
$currentTimestamp = time();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
$offset = ($page - 1) * $perPage;

// Filters
if (!empty($_GET['searchText'])) {
    $searchWildcard = "%" . $_GET['searchText'] . "%";
    $whereConditions[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

if (isset($_GET['minPrice']) && isset($_GET['maxPrice'])) {
    $whereConditions[] = "i.price BETWEEN ? AND ?";
    $params[] = floatval($_GET['minPrice']);
    $params[] = floatval($_GET['maxPrice']);
    $types .= "dd";
}

if (!empty($_GET['timeRemaining'])) {
    $maxFinish = $currentTimestamp + (intval($_GET['timeRemaining']) * 86400); 
    $whereConditions[] = "UNIX_TIMESTAMP(i.finish) <= ?";
    $params[] = $maxFinish;
    $types .= "i";
}

// Always filter expired
$whereConditions[] = "UNIX_TIMESTAMP(i.finish) >= ?";
$params[] = $currentTimestamp;
$types .= "i";

if (!empty($_GET['location'])) {
    $locationWildcard = "%" . $_GET['location'] . "%";
    $whereConditions[] = "m.postcode LIKE ?";
    $params[] = $locationWildcard;
    $types .= "s";
}

$validDepartments = ['Books', 'Clothing', 'Computing', 'DvDs', 'Electronics', 'Collectables', 'Home & Garden', 'Music', 'Outdoors', 'Toys', 'Sports Equipment'];
if (!empty($_GET['department']) && in_array($_GET['department'], $validDepartments)) {
    $whereConditions[] = "i.category = ?";
    $params[] = $_GET['department'];
    $types .= "s";
}

if (isset($_GET['freePostage']) && $_GET['freePostage'] === '1') {
    $whereConditions[] = "i.postage = 0";
}

// Base SQL
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

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}
$orderBy = "i.finish ASC"; // default
if (!empty($_GET['sortBy'])) {
    switch ($_GET['sortBy']) {
        case 'price-asc':
            $orderBy = "i.price ASC";
            break;
        case 'price-desc':
            $orderBy = "i.price DESC";
            break;
        case 'time-asc':
            $orderBy = "i.finish ASC";
            break;
        case 'bid-asc':
            $orderBy = "(i.currentBid IS NULL) ASC, i.currentBid ASC";
            break;
        case 'bid-desc':
            $orderBy = "i.currentBid DESC";
            break;
    }
}
$sql .= " ORDER BY $orderBy LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed."]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['image_data'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($row['image_data']) ?: 'image/jpeg';
        $row['image'] = 'data:' . $mimeType . ';base64,' . base64_encode($row['image_data']);
    } else {
        $row['image'] = 'placeholder.jpg';
    }
    unset($row['image_data']);

    $finishTimestamp = strtotime($row['finish']);
    $row['time_remaining'] = $finishTimestamp - $currentTimestamp;

    $items[] = $row;
}
$stmt->close();

// Total count
$countSql = "
    SELECT COUNT(*) AS total
    FROM iBayItems i
    LEFT JOIN iBayMembers m ON i.userId = m.userId
";
if (!empty($whereConditions)) {
    $countSql .= " WHERE " . implode(" AND ", $whereConditions);
}
$countStmt = $conn->prepare($countSql);
$countParams = $params;
array_splice($countParams, -2); // remove LIMIT & OFFSET
$countTypes = substr($types, 0, -2);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    'items' => $items,
    'total' => $totalItems,
    'page' => $page,
    'perPage' => $perPage
]);
?>