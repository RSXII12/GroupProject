<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
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

// Remember to change so it isn't hard-coded
$userId = '0079504835d'; 

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];

    // Delete from iBayImages first
    $stmt = $conn->prepare("DELETE FROM iBayImages WHERE itemId = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // Delete from iBayItems
    $stmt = $conn->prepare("DELETE FROM iBayItems WHERE itemId = ? AND userId = ?");
    $stmt->bind_param("ss", $deleteId, $userId);
    $stmt->execute();
    $stmt->close();
}

// Fetch listings for this seller
$sql = "SELECT * FROM iBayItems WHERE userId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Fetching images for item
$sql = "
    SELECT i.*, img.image
    FROM iBayItems i
    LEFT JOIN iBayImages img ON i.itemId = img.itemId
    WHERE i.userId = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Listings</title>
    <link rel="stylesheet" href="listingPage.css">
</head>
<body>
    <div class="container">
        <h2>Active Listings</h2>
        <?php
        $hasActive = false;
        foreach ($items as $item):
            if (strtotime($item['finish']) > time()):
                $hasActive = true;
        ?>
            <div class="listing-card">
                <div class="listing-image">
                    <?php
                    if (!empty($item['image'])) {
                        $base64Image = base64_encode($item['image']);
                        echo '<img src="data:image/jpeg;base64,' . $base64Image . '" alt="Listing Image">';
                    } else {
                        echo '<img src="placeholder.jpg" alt="No Image">';
                    }
                    ?>
                </div>
                <div class="listing-info">
                    <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                    £<?= number_format($item['price'], 2) ?><br>
                    Category: <?= htmlspecialchars($item['category']) ?>
                </div>
                <div class="listing-actions">
                    <!-- EDIT BUTTON -->
                    <a href="editItem.php?id=<?= $item['itemId'] ?>" class="edit-button">Edit</a>

                    <!-- DELETE BUTTON -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $item['itemId'] ?>">
                        <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</button>
                    </form>
                </div>
            </div>
        <?php
            endif;
        endforeach;
        if (!$hasActive) {
            echo "<p>No active listings.</p>";
        }
        ?>

        <h2>Inactive Listings</h2>
        <?php
        $hasInactive = false;
        foreach ($items as $item):
            if (strtotime($item['finish']) <= time()):
                $hasInactive = true;
        ?>
            <div class="listing-card inactive">
                <div class="listing-image">
                    <?php
                    if (!empty($item['image'])) {
                        $base64Image = base64_encode($item['image']);
                        echo '<img src="data:image/jpeg;base64,' . $base64Image . '" alt="Listing Image">';
                    } else {
                        echo '<img src="placeholder.jpg" alt="No Image">';
                    }
                    ?>
                </div>
                <div class="listing-info">
                    <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                    £<?= number_format($item['price'], 2) ?> (Expired)<br>
                    Category: <?= htmlspecialchars($item['category']) ?>
                </div>
                <div class="listing-actions">
                    <!-- EDIT BUTTON -->
                    <a href="itemDetails.php?id=<?= $item['itemId'] ?>" class="edit-button">Edit</a>

                    <!-- DELETE BUTTON -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $item['itemId'] ?>">
                        <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</button>
                    </form>
                </div>
            </div>
        <?php
            endif;
        endforeach;
        if (!$hasInactive) {
            echo "<p>No inactive listings.</p>";
        }
        ?>
    </div>
</body>