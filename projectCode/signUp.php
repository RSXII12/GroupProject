<?php
header('Content-Type: application/json');// Respond with JSON
session_start();

// DB config
$servername  = "sci-project.lboro.ac.uk";
$dbUsername  = "295group6";
$dbPassword  = "wHiuTatMrdizq3JfNeAH";
$dbName      = "295group6";

// Connect
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    error_log("DB connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'error' => 'Internal server error.']);
    exit;
}

// Decode JSON input
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON.']);
    exit;
}
//extract and trim inputs
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');
$email    = trim($data['email']    ?? '');
$address  = trim($data['address']  ?? '');
$postcode = trim($data['postcode'] ?? '');

// Validators
function validPostcode($p) {
    return preg_match('/^[A-Z0-9 ]{5,8}$/i', $p);
}
if (strlen($username) < 4
 || strlen($password) < 4
 || strlen($address)  < 5
 || !validPostcode($postcode)) {
    http_response_code(422);// Unprocessable Entity
    echo json_encode(['success' => false, 'error' => 'Validation failed.']);
    exit;
}

// Check existing email
$stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already in use.']);// Email already registered

    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert user
$hash   = password_hash($password, PASSWORD_DEFAULT);// Hash password securely
$userId = hash('sha256', $username . $address . $email);// Create a unique userId

$stmt = $conn->prepare("
    INSERT INTO iBayMembers (userId, password, name, email, address, postcode)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssss', $userId, $hash, $username, $email, $address, $postcode);

if ($stmt->execute()) {// Success
    echo json_encode(['success' => true]);
} else {
    error_log('Insert error: ' . $stmt->error);// Log error for debugging, generic message to client
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}

$stmt->close();
$conn->close();
?>