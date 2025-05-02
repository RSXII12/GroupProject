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

// Get current time in correct format
date_default_timezone_set('Europe/London');
$now = date('Y-m-d H:i:s');

// Query to fetch non-expired items
$sql = "SELECT itemId, title, price FROM iBayItems WHERE finish > ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $itemId = htmlspecialchars($row["itemId"]);
        $title = htmlspecialchars($row["title"]);
        $price = htmlspecialchars($row["price"]);

        echo '<a href="itemDetails.php?id=' . $itemId . '" class="item-link">';
        echo '<div class="item-container">';
        echo '<h3>' . $title . '</h3>';
        echo '<p>Price: £' . $price . '</p>';

        // Get all images for this item
        $imgSql = "SELECT image FROM iBayImages WHERE itemId = ?";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $itemId);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        if ($imgResult->num_rows > 0) {
            echo '<div class="image-carousel">';
            $first = true;
            while ($imgRow = $imgResult->fetch_assoc()) {
                $imageData = base64_encode($imgRow['image']);
                $display = $first ? 'block' : 'none';
                echo '<img src="data:image/jpeg;base64,' . $imageData . '" style="width: 100px; display: ' . $display . ';">';
                $first = false;
            }
            echo '</div>';
        } else {
            echo '<img src="placeholder.jpg" alt="No Image Available" style="width: 100px;">';
        }

        echo '</div>';
        echo '</a>';
    }
} else {
    echo "No items found.";
}

// Close connection
$conn->close();
?>

<!-- JavaScript to cycle through images -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.image-carousel').forEach(container => {
        const images = container.querySelectorAll('img');
        let index = 0;
        if (images.length < 2) return; // Skip carousel if only one image

        setInterval(() => {
            images.forEach((img, i) => {
                img.style.display = i === index ? 'block' : 'none';
            });
            index = (index + 1) % images.length;
        }, 4000);
    });
});
</script>
