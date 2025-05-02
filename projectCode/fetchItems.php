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

// Query to fetch only non-expired item details and images
$sql = "SELECT iBayItems.itemId, iBayItems.title, iBayItems.price, iBayImages.image
        FROM iBayItems
        LEFT JOIN iBayImages ON iBayItems.itemId = iBayImages.itemId
        WHERE iBayItems.finish > NOW()";  // Only fetch items where the auction hasn't ended

$result = $conn->query($sql);

// Check if there are any results
if ($result->num_rows > 0) {
    // Loop through all items and display them
    while ($row = $result->fetch_assoc()) {
        $itemId = htmlspecialchars($row["itemId"]);
        $title = htmlspecialchars($row["title"]);
        $price = htmlspecialchars($row["price"]);
        $imageTag = "";

        if ($row['image']) {
            $imageData = base64_encode($row['image']); // Convert binary data to base64
            $imageTag = '<img src="data:image/jpeg;base64,' . $imageData . '" alt="' . $title . '" style="width: 100px; height: auto;">';
        }

        // Wrap each item in a clickable link to the item details page
        echo '<a href="itemDetails.php?id=' . $itemId . '" class="item-link">';
        echo '<div class="item-container">';
        echo '<h3>' . $title . '</h3>';
        echo '<p>Price: £' . $price . '</p>';
        echo $imageTag;
        echo '</div>';
        echo '</a>';
    }
} else {
    echo "No items found.";
}

// Close connection
$conn->close();
?>
