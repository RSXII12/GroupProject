<?php
// listingPage.php
// Ensure PHP uses UK time (BST/GMT)
date_default_timezone_set('Europe/London');
session_start();
header('Content-Type: text/html; charset=UTF-8');

$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";

$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

// Force MySQL session to UK local time (BST/GMT)
$ukOffset = date('P');
if (! $mysqli->query("SET time_zone = '{$ukOffset}'")) {
    error_log("Failed to set MySQL time_zone: " . $mysqli->error);
}

$userId = $_SESSION['userId'] ?? null;
if (!$userId) {
    header("Location: sellerLogin.html");
    exit;
}

// AJAX deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
) {
    $data = json_decode(file_get_contents('php://input'), true);
    $deleteId = $data['delete_id'] ?? '';
    // delete images then item
    $stmt = $mysqli->prepare("DELETE FROM iBayImages WHERE itemId = ?");
    $stmt->bind_param('s', $deleteId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM iBayItems WHERE itemId = ? AND userId = ?");
    $stmt->bind_param('ss', $deleteId, $userId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode(['success' => $ok]);
    exit;
}

// Fetch all listings
$stmt = $mysqli->prepare(
    "SELECT i.itemId, i.title, i.price, i.category, i.finish,
            (SELECT img.image FROM iBayImages img
             WHERE img.itemId = i.itemId
             ORDER BY img.number ASC LIMIT 1) AS primaryImage
     FROM iBayItems i
     WHERE i.userId = ?
     ORDER BY i.finish DESC"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();

// Separate active/inactive based on UK-time adjusted finish
$now = time();
$active   = array_filter($items, fn($it) => strtotime($it['finish']) > $now);
$inactive = array_filter($items, fn($it) => strtotime($it['finish']) <= $now);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Seller Listings</title>
  <link rel="stylesheet" href="listingPage.css">
  <style>
    .header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
    .container { margin-top: 80px; }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <div class="header">
    <div class="header-left"><a href="index.php"><img src="iBay-logo.png" alt="iBay Logo"></a></div>
    <div class="header-center">My Listings</div>
    <div class="header-right"><a href="sellerPage.html" class="nav-button">Create New Listing</a></div>
  </div>

  <div class="container">

    <h2 class="section-title">Active Listings</h2>
    <div id="active-listings" class="scrollable-section">
      <?php if (empty($active)): ?>
        <p>No active listings.</p>
      <?php else: foreach ($active as $item):
        $img = $item['primaryImage']
             ? 'data:image/jpeg;base64,'.base64_encode($item['primaryImage'])
             : 'placeholder.jpg';
      ?>
      <div class="listing-card" data-id="<?= htmlspecialchars($item['itemId']) ?>">
        <div class="listing-image"><img src="<?= $img ?>" alt=""></div>
        <div class="listing-info">
          <strong><?= htmlspecialchars($item['title']) ?></strong><br>
          £<?= number_format($item['price'], 2) ?><br>
          Category: <?= htmlspecialchars($item['category']) ?>
        </div>
        <div class="listing-actions">
          <a href="editItem.php?id=<?= $item['itemId'] ?>" class="edit-button">Edit</a>
          <button class="delete-button">Delete</button>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <h2 class="section-title">Expired Listings</h2>
    <div id="inactive-listings" class="scrollable-section">
      <?php if (empty($inactive)): ?>
        <p>No expired listings.</p>
      <?php else: foreach ($inactive as $item):
        $img = $item['primaryImage']
             ? 'data:image/jpeg;base64,'.base64_encode($item['primaryImage'])
             : 'placeholder.jpg';
      ?>
      <div class="listing-card inactive" data-id="<?= htmlspecialchars($item['itemId']) ?>">
        <div class="listing-image"><img src="<?= $img ?>" alt=""></div>
        <div class="listing-info">
          <strong><?= htmlspecialchars($item['title']) ?></strong><br>
          £<?= number_format($item['price'], 2) ?> (Expired)<br>
          Category: <?= htmlspecialchars($item['category']) ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>

  <script>
  $(function() {
    $('.scrollable-section').on('click', '.delete-button', function() {
      const card = $(this).closest('.listing-card');
      const id   = card.data('id');
      if (!confirm('Delete this listing?')) return;
      $.ajax({
        url: 'listingPage.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ delete_id: id })
      })
      .done(resp => {
        if (resp.success) card.slideUp(300, () => card.remove());
        else alert('Delete failed.');
      })
      .fail(() => alert('Server error.'));
    });
  });
  </script>
</body>
</html>