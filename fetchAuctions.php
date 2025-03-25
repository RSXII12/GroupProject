<?php
session_start();

// Database connection
$servername = "localhost"; 
$dbUsername = "root"; 
$dbPassword = ""; 
$dbName = "teamdatabase"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$search = isset($_POST['search']) ? $_POST['search'] : '';
$priceMin = isset($_POST['priceMin']) ? $_POST['priceMin'] : 0;
$priceMax = isset($_POST['priceMax']) ? $_POST['priceMax'] : 1000;
$timeRemaining = isset($_POST['timeRemaining']) ? $_POST['timeRemaining'] : 0;
$location = isset($_POST['location']) ? $_POST['location'] : '';

// Build query to fetch filtered auction items
$query = "SELECT * FROM auctions WHERE price BETWEEN ? AND ?";

if (!empty($search)) {
    $query .= " AND (item_name LIKE ? OR category LIKE ?)";
}

if ($timeRemaining > 0) {
    $query .= " AND time_remaining <= ?";
}

if (!empty($location)) {
    $query .= " AND location LIKE ?";
}

$stmt = $conn->prepare($query);
$params = [$priceMin, $priceMax];

// Add dynamic filters
if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    array_push($params, $searchTerm, $searchTerm);
}

if ($timeRemaining > 0) {
    array_push($params, $timeRemaining);
}

if (!empty($location)) {
    $locationTerm = "%" . $location . "%";
    array_push($params, $locationTerm);
}

$stmt->bind_param(str_repeat('s', count($params)), ...$params);

$stmt->execute();
$result = $stmt->get_result();

// Output the auction items
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="auction-item">';
        echo '<h3>' . htmlspecialchars($row['item_name']) . '</h3>';
        echo '<p>Price: $' . $row['price'] . '</p>';
        echo '<p>Time Remaining: ' . $row['time_remaining'] . ' hours</p>';
        echo '<p>Location: ' . htmlspecialchars($row['location']) . '</p>';
        echo '</div>';
    }
} else {
    echo '<p>No auctions found matching your criteria.</p>';
}

$stmt->close();
$conn->close();
?>
