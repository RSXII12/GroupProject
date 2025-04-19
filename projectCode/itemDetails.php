<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php'; // Make sure this connects using PDO

if (!isset($_GET['id'])) {
    echo "No item specified.";
    exit;
}

$itemId = intval($_GET['id']);
$stmt = $pdo->prepare("
    SELECT i.itemId, i.title, i.description, i.price, i.postage, i.category, i.finish, img.image
    FROM iBayItems i
    LEFT JOIN iBayImages img ON i.itemId = img.itemId
    WHERE i.itemId = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    echo "Item not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($item['title']) ?> - iBay</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="item-detail-container">
        <a href="index.php">← Back to Home</a>
        <h1><?= htmlspecialchars($item['title']) ?></h1>

        <?php
        $imageData = $item['image'] ?? null;
        if ($imageData) {
            $base64Image = base64_encode($imageData);
            echo '<img src="data:image/jpeg;base64,' . $base64Image . '" alt="' . htmlspecialchars($item['title']) . '" style="max-width: 300px;">';
        } else {
            echo '<img src="placeholder.jpg" alt="No Image Available" style="max-width: 300px;">';
        }
        ?>
    
        <p><strong>Price:</strong> £<?= htmlspecialchars($item['price'] ?? 'N/A') ?></p>
        <p><strong>Postage:</strong> £<?= htmlspecialchars($item['postage'] ?? 'N/A') ?></p>

        <p><strong>Description:</strong></p>
        <p><?= nl2br(htmlspecialchars($item['description'] ?? 'No description provided')) ?></p>

        <?php
        date_default_timezone_set('Europe/London');
        $endTime = isset($item['finish']) ? strtotime($item['finish']) : null;

        if ($endTime) {
            $now = time();
            $diffSeconds = $endTime - $now;

            if ($diffSeconds > 0) {
                $daysRemaining = floor($diffSeconds / 86400); // 1 day = 86400 seconds
                $hoursRemaining = floor(($diffSeconds % 86400) / 3600); // Remaining hours
                $minutesRemaining = floor(($diffSeconds % 3600) / 60); // Remaining minutes

                echo "<p><strong>Time Remaining:</strong> $daysRemaining days $hoursRemaining hours $minutesRemaining minutes</p>";
            } else {
                echo "<p><strong>Time Remaining:</strong> Auction ended</p>";
            }
        } else {
            echo "<p><strong>Time Remaining:</strong> Not available</p>";
        }
        ?>
    </div>
</body>
</html>
