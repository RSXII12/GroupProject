<?php
// Database connection
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Europe/London');
$now = date('Y-m-d H:i:s');

// Random but consistent daily picks: join image and limit to 16
$sql = "
    SELECT i.itemId, i.title, i.price, i.currentBid, img.image
    FROM iBayItems i
    LEFT JOIN (
        SELECT itemId, image
        FROM iBayImages
        WHERE number = 1
    ) img ON i.itemId = img.itemId
    WHERE i.finish > ?
    ORDER BY MD5(CONCAT(i.itemId, CURDATE()))
    LIMIT 16
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $itemId = htmlspecialchars($row["itemId"]);
        $title = htmlspecialchars($row["title"]);
        $price = htmlspecialchars($row["price"]);
        $currentBid = isset($row["currentBid"]) ? htmlspecialchars($row["currentBid"]) : null;
        echo '<a href="itemDetails.php?id=' . $itemId . '" class="item-link">';
        echo '<div class="item-container">';

        // Centered image wrapper
        echo '<div class="image-wrapper">';
        if (!empty($row['image'])) {
            $imageData = base64_encode($row['image']);
            echo '<img src="data:image/jpeg;base64,' . $imageData . '" alt="Item Image">';
        } else {
            echo '<img src="placeholder.jpg" alt="No Image Available">';
        }
        echo '</div>';

        echo '<h3>' . $title . '</h3>';
        echo '<p>Starting price: £' . $price . '</p>';
        if ($currentBid !== null) {
            echo '<p>Current Bid: £' . $currentBid . '</p>';
        } else {
            echo '<p class="no-bid">No bids yet</p>';
        }

        echo '</div>';
        echo '</a>';
    }
} else {
    echo "<p>No top picks available today.</p>";
}

$stmt->close();
$conn->close();
?>