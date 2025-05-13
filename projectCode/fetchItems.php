<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');// Tell the client we’re returning JSON

$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";
// Connect to MySQL
$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

date_default_timezone_set('Europe/London');
$now = date('Y-m-d H:i:s');
// Use server’s local time to filter out expired listings

//main sql query
$sql = "
  SELECT 
    i.itemId,
    i.title,
    i.price,
    i.currentBid,
    img.image
  FROM iBayItems i
  LEFT JOIN (
    SELECT itemId, image
    FROM iBayImages
    WHERE number = 1
  ) img ON i.itemId = img.itemId
  WHERE i.finish > ?
  ORDER BY MD5(CONCAT(i.itemId, CURDATE()))
  LIMIT 16
";
// Bind the current timestamp to the query’s placeholder
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

//build output array
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'itemId'     => $row['itemId'],
        'title'      => $row['title'],
        'price'      => (float)$row['price'],
        'currentBid' => $row['currentBid'] !== null ? (float)$row['currentBid'] : null,
        'image'      => $row['image'] !== null
                         ? base64_encode($row['image'])
                         : null
    ];
}

$stmt->close();
$mysqli->close();
// Send the JSON-encoded array of items to the client
echo json_encode($items);