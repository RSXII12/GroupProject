<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// must be logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid request.']);
    exit;
}

$itemId   = $data['itemId']  ?? '';
$bidInput = $data['bid']     ?? '';

if (!is_numeric($bidInput) || $bidInput < 0) {
    echo json_encode(['success'=>false,'error'=>'Bid must be a non-negative number.']);
    exit;
}
$bid = floatval($bidInput);

$mysqli = new mysqli("sci-project.lboro.ac.uk","295group6","wHiuTatMrdizq3JfNeAH","295group6");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Database error.']);
    exit;
}

// fetch item
$stmt = $mysqli->prepare("
    SELECT currentBid, price AS startPrice, finish, userId AS sellerId
      FROM iBayItems
     WHERE itemId = ?
");
$stmt->bind_param('s',$itemId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success'=>false,'error'=>'Item not found.']);
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();

// Cast and parse fetched values
$currentBid  = floatval($row['currentBid']);
$startPrice  = floatval($row['startPrice']);
$finishTime  = new DateTime($row['finish']);
$sellerId    = $row['sellerId'];
$now         = new DateTime();

// Prevent self-bidding
if ($_SESSION['userId'] === $sellerId) {
    echo json_encode(['success'=>false,'error'=>'Cannot bid on your own item.']);
    exit;
}
// Prevent bidding after auction has ended
if ($now > $finishTime) {
    echo json_encode(['success'=>false,'error'=>'Auction has ended.']);
    exit;
}
// Enforce minimum increment
$minAllowed = max($startPrice, $currentBid) + 0.01;
if ($bid < $minAllowed) {
    echo json_encode(['success'=>false,'error'=>"Bid must be at least £".number_format($minAllowed,2)]);
    exit;
}

// update
$stmt = $mysqli->prepare("
    UPDATE iBayItems
       SET currentBid = ?, bidUser = ?
     WHERE itemId = ?
");
$stmt->bind_param('dss', $bid, $_SESSION['userId'], $itemId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Failed to place bid.']);
    exit;
}
$stmt->close();
$mysqli->close();

// success
echo json_encode(['success'=>true,'newBid'=>$bid]);
exit;
?>