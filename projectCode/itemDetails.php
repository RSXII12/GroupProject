<?php
// Connect to database using mysqli
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get item ID from URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($itemId > 0) {
    // Fetch item details
    $stmt = $conn->prepare("SELECT title, description, price, finish FROM iBayItems WHERE itemId = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
        echo "<p>Description: " . htmlspecialchars($row['description']) . "</p>";
        echo "<p>Price: £" . htmlspecialchars($row['price']) . "</p>";
        echo "<p>Auction ends at: " . htmlspecialchars($row['finish']) . "</p>";

        // Fetch images
        $imgStmt = $conn->prepare("SELECT image FROM iBayImages WHERE itemId = ?");
        $imgStmt->bind_param("i", $itemId);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        echo '<div class="image-carousel">';
        $first = true;
        while ($imgRow = $imgResult->fetch_assoc()) {
            $imageData = base64_encode($imgRow['image']);
            $display = $first ? 'block' : 'none';
            echo '<img src="data:image/jpeg;base64,' . $imageData . '" style="width:200px; display:' . $display . ';">';
            $first = false;
        }
        echo '</div>';
    } else {
        echo "Item not found.";
    }

    $stmt->close();
} else {
    echo "Invalid item ID.";
}

$conn->close();
?>

<!-- Simple image switching again -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.image-carousel').forEach(container => {
        const images = container.querySelectorAll('img');
        let index = 0;
        if (images.length < 2) return;

        setInterval(() => {
            images.forEach((img, i) => {
                img.style.display = i === index ? 'block' : 'none';
            });
            index = (index + 1) % images.length;
        }, 2000);
    });
});
</script>
