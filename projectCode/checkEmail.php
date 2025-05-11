<?php
header('Content-Type: application/json');//  Ensures the client gets a JSON response  

// DB credentials
$servername  = "sci-project.lboro.ac.uk";
$dbUsername  = "295group6";
$dbPassword  = "wHiuTatMrdizq3JfNeAH";
$dbName      = "295group6";

// Connect
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error.']);
    exit;
}

// Validate input
if (!isset($_GET['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email parameter.']);
    exit;
}
$email = trim($_GET['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format.']);
    exit;
    // Return 400 for missing/invalid email, so client can handle validation
}

// Check email
$stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

echo json_encode(['taken' => $stmt->num_rows > 0]); //echo taken if email exists in db

$stmt->close();
$conn->close();
?>