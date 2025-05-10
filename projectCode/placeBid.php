<?php
// placeBid.php

session_start();

//login check
if (!isset($_SESSION['userId'])) {
    header('Location: sellerLogin.html');
    exit;
}

// ensure method is post, make sure itemid and bid aren't empty
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_POST['itemId'])
    || empty($_POST['bid'])
) {
    die("Invalid request.");
}

$itemId   = $_POST['itemId'];
$bidInput = $_POST['bid'];

// Sanitize inputs 
if (!preg_match('/^[a-zA-Z0-9]+$/', $itemId)) {
    die("Invalid item ID.");
}
if (!preg_match('/^\d+(\.\d{1,2})?$/', $bidInput)) {
    die("Bid must be a valid number (up to 2 decimal places).");
}
$bid = floatval($bidInput);


$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";

$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

// fetch item details + sellerId
$stmt = $mysqli->prepare("
    SELECT currentBid, price AS startPrice, finish, userId AS sellerId
    FROM iBayItems
    WHERE itemId = ?
");
$stmt->bind_param('s', $itemId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    die("Item not found.");
}
$row = $result->fetch_assoc();
$stmt->close();

$currentBid = floatval($row['currentBid']);
$startPrice = floatval($row['startPrice']);
$finishTime = new DateTime($row['finish']);
$sellerId   = $row['sellerId'];
$now        = new DateTime();

//  prevent self-bid
if ($_SESSION['userId'] === $sellerId) {
    $mysqli->close();
    die("You cannot bid on your own listing.");
}

//check auction still open
if ($now > $finishTime) {
    $mysqli->close();
    die("Sorry, the auction has already ended.");
}

// check bid high enough
$minAllowed = max($startPrice, $currentBid) + 0.01;
if ($bid < $minAllowed) {
    $mysqli->close();
    die("Your bid must be at least £" . number_format($minAllowed, 2));
}

// update table
$stmt = $mysqli->prepare("
    UPDATE iBayItems
    SET currentBid = ?, bidUser = ?
    WHERE itemId = ?
");
$stmt->bind_param('dss', $bid, $_SESSION['userId'], $itemId);
if (!$stmt->execute()) {
    $stmt->close();
    $mysqli->close();
    die("Failed to place bid. Please try again.");
}
$stmt->close();
$mysqli->close();

// redirect
header("Location: itemDetails.php?id=" . urlencode($itemId));
exit;
?>