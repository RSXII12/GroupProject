<?php
// search.php
session_start();
header('Content-Type: application/json');// Return JSON responses

$servername  = "sci-project.lboro.ac.uk";
$dbUsername  = "295group6";
$dbPassword  = "wHiuTatMrdizq3JfNeAH";
$dbName      = "295group6";
// Connect to MySQL
$conn = new mysqli($servername,$dbUsername,$dbPassword,$dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed.']);
    exit;
}

$page    = max(1, intval($_GET['page']    ?? 1));// Current page
$perPage = max(1, intval($_GET['perPage'] ?? 10));// Items per page
$offset  = ($page-1)*$perPage;// SQL offset
$now     = time();// Current timestamp

//query builder
$where = [];
$types = '';
$params = [];

// text search
if (!empty($_GET['searchText'])) {
    $where[] = '(i.title LIKE ? OR i.description LIKE ?)';
    $like = '%'.$_GET['searchText'].'%';
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}

// price
if (isset($_GET['minPrice'],$_GET['maxPrice'])) {
    $where[] = 'i.price BETWEEN ? AND ?';
    $params[] = floatval($_GET['minPrice']);
    $params[] = floatval($_GET['maxPrice']);
    $types .= 'dd';
}

// expiry filter
$where[] = 'UNIX_TIMESTAMP(i.finish) >= ?';
$params[] = $now; $types .= 'i';

if (!empty($_GET['timeRemaining'])) {
    $where[] = 'UNIX_TIMESTAMP(i.finish) <= ?';
    $params[] = $now + intval($_GET['timeRemaining'])*86400;
    $types .= 'i';
}

// location
if (!empty($_GET['location'])) {
    $where[] = 'm.postcode LIKE ?';
    $params[] = '%'.$_GET['location'].'%';
    $types .= 's';
}

// department
$valid = ['Books','Clothing','Computing','DvDs','Electronics',
          'Collectables','Home & Garden','Music','Outdoors',
          'Toys','Sports Equipment'];
if (!empty($_GET['department']) && in_array($_GET['department'],$valid)) {
    $where[] = 'i.category=?';
    $params[] = $_GET['department'];
    $types .= 's';
}

// free postage
if (isset($_GET['freePostage']) && $_GET['freePostage']=='1') {
    $where[] = 'i.postage=0';
}

// build SQL
$sql = "SELECT
    i.itemId,i.title,i.category,i.description,i.price,i.postage,i.start,i.finish,
    i.currentBid,
    img.image_data,
    m.postcode AS location
  FROM iBayItems i
  LEFT JOIN (
    SELECT itemId, image AS image_data
    FROM iBayImages
    GROUP BY itemId
  ) img ON i.itemId=img.itemId
  LEFT JOIN iBayMembers m ON i.userId=m.userId";

if ($where) $sql .= ' WHERE '.implode(' AND ',$where);

// sorting
$order = 'i.finish ASC';
switch($_GET['sortBy'] ?? '') {
  case 'price-asc':  $order='i.price ASC'; break;
  case 'price-desc': $order='i.price DESC';break;
  case 'bid-asc':    $order='i.currentBid ASC';break;
  case 'bid-desc':   $order='i.currentBid DESC';break;
  case 'time-asc':   $order='i.finish ASC';break;
}
$sql .= " ORDER BY $order LIMIT ? OFFSET ?";
$params[] = $perPage; $params[] = $offset; $types .= 'ii';// Add pagination types

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($r = $res->fetch_assoc()) {
    if ($r['image_data']) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($r['image_data']);
        $r['image'] = "data:$mime;base64,".base64_encode($r['image_data']);
    } else {
        $r['image'] = 'placeholder.jpg';
    }
    unset($r['image_data']);
    $r['time_remaining'] = strtotime($r['finish']) - $now;
    $items[] = $r;
}
$stmt->close();

// total count
$countSql = "SELECT COUNT(*) AS total FROM iBayItems i LEFT JOIN iBayMembers m ON i.userId=m.userId";
if ($where) $countSql .= ' WHERE '.implode(' AND ',$where);
$countStmt = $conn->prepare($countSql);
if ($where) {
  // bind same params minus last two (LIMIT/OFFSET)
  $countStmt->bind_param(substr($types,0,-2), ...array_slice($params,0,-2));
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([//return json
  'items'   => $items,
  'total'   => intval($total),
  'page'    => $page,
  'perPage' => $perPage
]);
$conn->close();