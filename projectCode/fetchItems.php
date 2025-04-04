<?php
// Database connection credentials
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch item details and images
$sql = "SELECT iBayItems.itemId, iBayItems.title, iBayItems.price, iBayImages.image
        FROM iBayItems
        LEFT JOIN iBayImages ON iBayItems.itemId = iBayImages.itemId";
$result = $conn->query($sql);

// Check if there are any results
if ($result->num_rows > 0) {
    // Loop through all items and display them
    while ($row = $result->fetch_assoc()) {
        // Display item details
        echo '<div class="item-container">';
        echo '<h3>' . htmlspecialchars($row["title"]) . '</h3>';
        #echo '<p>' . htmlspecialchars($row["category"]) . '</p>';
        #echo '<p>' . htmlspecialchars($row["description"]) . '</p>';
        echo '<p>Price: £' . htmlspecialchars($row["price"]) . '</p>';
        
        // Display image
        $imageData = $row['image'];
        if ($imageData) {
            $image = base64_encode($imageData); // Convert binary data to base64
            echo '<img src="data:image/jpeg;base64,' . $image . '" alt="' . htmlspecialchars($row["title"]) . '" style="width: 100px; height: auto;">';
        }
        
        echo '</div>';
    }
} else {
    echo "No items found.";
}

// Close connection
$conn->close();
?>
