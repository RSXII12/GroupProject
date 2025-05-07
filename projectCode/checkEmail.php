<?php
// Database credentials
$servername = "sci-project.lboro.ac.uk";
$dbUsername = "295group6";
$dbPassword = "wHiuTatMrdizq3JfNeAH";
$dbName = "295group6";

// Set response to JSON
header('Content-Type: application/json');

// Connect to the database
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error); // Server-side log
    http_response_code(500); // Internal server error
    echo json_encode(['error' => 'Internal server error.']);
    exit();
}

// Check if email was passed
if (!isset($_GET['email'])) {
    http_response_code(400); // Bad request
    echo json_encode(['error' => 'Missing email parameter.']);
    exit();
}

$email = trim($_GET['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format.']);
    exit();
}

// Use prepared statement to safely query database
$stmt = $conn->prepare("SELECT userId FROM iBayMembers WHERE email = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error.']);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// Respond with whether the email is already taken
echo json_encode(['taken' => $stmt->num_rows > 0]);

// Clean up
$stmt->close();
$conn->close();
?>