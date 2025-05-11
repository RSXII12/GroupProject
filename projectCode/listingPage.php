<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['userId'];

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];

    // Delete related images
    $stmt = $conn->prepare("DELETE FROM iBayImages WHERE itemId = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // Delete the item itself
    $stmt = $conn->prepare("DELETE FROM iBayItems WHERE itemId = ? AND userId = ?");
    $stmt->bind_param("ss", $deleteId, $userId);
    $stmt->execute();
    $stmt->close();
}

// Fetch listings with exactly one (primary) image per item
// We use the `number` column in iBayImages to pick the “primary” (lowest) image number.
$sql = "
  SELECT 
    i.itemId,
    i.title,
    i.price,
    i.category,
    i.finish,
    (
      SELECT img.image
      FROM iBayImages img
      WHERE img.itemId = i.itemId
      ORDER BY img.number ASC
      LIMIT 1
    ) AS primaryImage
  FROM iBayItems i
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
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Seller Listings</title>
  <link rel="stylesheet" href="listingPage.css">
</head>
<body>
  <div class="header">
    <div class="header-left">
      <a href="index.php"><img src="iBay-logo.png" alt="iBay Logo" /></a>
    </div>
    <div class="header-center">My Listings</div>
    <div class="header-right">
      <a href="sellerPage.html" class="nav-button">Create New Listing</a>
    </div>
  </div>

  <div class="container">
    <!-- Active Listings -->
    <h2 class="section-title">Active Listings</h2>
    <div class="scrollable-section" style="height: 400px; overflow-y: auto; border: 1px solid #ccc; margin-bottom: 30px;
    border-radius: 5px; padding: 10px;">
      <?php
      $hasActive = false;
      foreach ($items as $item):
        if (strtotime($item['finish']) > time()):
          $hasActive = true;
      ?>
        <div class="listing-card">
          <div class="listing-image">
            <?php
            if ($item['primaryImage']) {
              $b64 = base64_encode($item['primaryImage']);
              echo "<img src=\"data:image/jpeg;base64,{$b64}\" alt=\"Listing Image\">";
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
            <a href="editItem.php?id=<?= $item['itemId'] ?>" class="edit-button">Edit</a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="delete_id" value="<?= $item['itemId'] ?>">
              <button type="submit" class="delete-button"
                      onclick="return confirm('Are you sure you want to delete this listing?');">
                Delete
              </button>
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
    </div>

    <!-- Inactive Listings -->
    <h2 class="section-title">Inactive Listings</h2>
    <div class="scrollable-section">
      <?php
      $hasInactive = false;
      foreach ($items as $item):
        if (strtotime($item['finish']) <= time()):
          $hasInactive = true;
      ?>
        <div class="listing-card inactive">
          <div class="listing-image">
            <?php
            if ($item['primaryImage']) {
              $b64 = base64_encode($item['primaryImage']);
              echo "<img src=\"data:image/jpeg;base64,{$b64}\" alt=\"Listing Image\">";
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
            <a href="itemDetails.php?id=<?= $item['itemId'] ?>" class="edit-button">Edit</a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="delete_id" value="<?= $item['itemId'] ?>">
              <button type="submit" class="delete-button"
                      onclick="return confirm('Are you sure you want to delete this listing?');">
                Delete
              </button>
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
  </div>

</body>
</html>