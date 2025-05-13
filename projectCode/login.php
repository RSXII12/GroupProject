<?php
header('Content-Type: application/json; charset=utf-8');// Return JSON responses
session_start();

// DB config
$servername = "sci-project.lboro.ac.uk";
$dbUsername = "295group6";
$dbPassword = "wHiuTatMrdizq3JfNeAH";
$dbName     = "295group6";

$raw  = file_get_contents('php://input');// Read raw POST body
$data = json_decode($raw, true);// Decode JSON into array
if (json_last_error() !== JSON_ERROR_NONE) {// Validate JSON
    echo json_encode(['success' => false, 'error' => 'Invalid request format.']);
    exit;
}
//extract and trim credentials
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

// basic validation
if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Please enter both email and password.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {//check email format
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// connect
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);// log db con failure
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
    exit;
}

// lookup user
$stmt = $conn->prepare("SELECT userId, password, name FROM iBayMembers WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {// No user found with that email
    echo json_encode(['success' => false, 'error' => 'Email not registered.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->bind_result($userId, $hashed, $name);
$stmt->fetch();
$stmt->close();

// verify password
if (!password_verify($password, $hashed)) {
    echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
    $conn->close();
    exit;
}

// success
$_SESSION['userId']   = $userId;
$_SESSION['username'] = $name;

echo json_encode(['success' => true]);
$conn->close();
?>