<?php
// itemDetails.php
// Ensure PHP uses UK time (BST/GMT)
date_default_timezone_set('Europe/London');
session_start();

// Validate and fetch 'id' parameter
$itemId = $_GET['id'];

// Database connection
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

// Fetch item metadata
$stmt = $mysqli->prepare(
    "SELECT userId, title, category, description, price, postage, i.start AS start, i.finish AS finish, currentBid
     FROM iBayItems i
     WHERE itemId = ?"
);
$stmt->bind_param('s', $itemId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Item not found.");
}
$item = $res->fetch_assoc();
$stmt->close();

// Fetch seller info
$stmt = $mysqli->prepare(
    "SELECT name, postcode
     FROM iBayMembers
     WHERE userId = ?"
);
$stmt->bind_param('s', $item['userId']);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$postcode   = $member['postcode'] ?? '';
$sellerName = $member['name']     ?? 'Unknown seller';
$stmt->close();

// Fetch images
$stmt = $mysqli->prepare(
    "SELECT image
     FROM iBayImages
     WHERE itemId = ?
     ORDER BY number"
);
$stmt->bind_param('s', $itemId);
$stmt->execute();
$res = $stmt->get_result();
$images = [];
while ($row = $res->fetch_assoc()) {
    $images[] = 'data:image/jpeg;base64,' . base64_encode($row['image']);
}
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($item['title']) ?></title>
  <!-- Leaflet CSS for the map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    /* styles (has to be here for some reason) */
    html { box-sizing: border-box; }
    *, *::before, *::after { box-sizing: inherit; }
    body {
      margin: 0; padding: 0;
      height: 100vh;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      font-family: Arial, sans-serif;
      background: #f8f9fa;
    }
    .header {
      flex: 0 0 auto;
      background: #0056b3; color: #fff;
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px; font-size: 24px; font-weight: bold;
    }
    .header-left, .header-center, .header-right {
      flex: 1; display: flex; align-items: center; justify-content: center;
    }
    .header-left { justify-content: flex-start; padding-left: 3%; }
    .header-right { justify-content: flex-end; padding-right: 3%; }
    .header-left img { width: 30%; height: auto; object-fit: cover; }
    main.page-content {
      flex: 1 1 auto;
      overflow-y: auto;
      max-width: 1000px;
      margin: 0 auto;
      padding: 1em;
      display: flex; flex-direction: column;
    }
    .flex { display: flex; gap: 2em; }
    @media (max-width: 768px) { .flex { flex-direction: column; } }
    .images {
      position: relative;
      width: 400px;
      height: 400px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
    }
    .images .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; }
    .images .slide.active { display: flex; align-items: center; justify-content: center; }
    .images .slide img { max-width: 100%; max-height: 100%; display: block; }
    .images button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 0.5em; cursor: pointer; }
    .images .prev { left: 0.5em; }
    .images .next { right: 0.5em; }
    .details { flex: 1; }
    .details p { margin: 0.5em 0; }
    form.bid { margin: 1em 0; padding: 0.5em; border: 1px solid #888; display: inline-block; }
    form.bid input[type="text"] { width: 8em; }
    #map { margin-top: 1em; width: 100%; height: 300px; border: 1px solid #999; }
    .lightbox { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; display: none; justify-content: center; align-items: center; background: rgba(0,0,0,0.8); z-index: 10000; }
    .lightbox img { max-width: 90%; max-height: 90%; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    .lightbox-close { position: absolute; top: 20px; right: 30px; font-size: 2rem; color: #fff; cursor: pointer; user-select: none; }
    .footer { flex: 0 0 auto; background: #2f64ad; color: #fff; text-align: center; padding: 10px; font-size: 12px; }
  </style>
</head>
<body>

  <div class="header">
    <div class="header-left">
      <a href="index.php"><img src="iBay-logo.png" alt="iBay logo"></a>
    </div>
  </div>

  <main class="page-content">
    <div class="flex">
      <div class="images">
        <?php foreach ($images as $i => $src): ?>
        <div class="slide<?= $i === 0 ? ' active' : '' ?>">
          <img src="<?= $src ?>" alt="Item image <?= $i+1 ?>">
        </div>
        <?php endforeach; ?>
        <?php if (count($images) === 2): ?>
        <button class="prev">&larr;</button>
        <button class="next">&rarr;</button>
        <?php endif; ?>
      </div>
      <div class="details">
        <h1><?= htmlspecialchars($item['title']) ?></h1>
        <p><strong>Seller:</strong> <?= htmlspecialchars($sellerName) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></p>
        <p><strong>Starting Price:</strong> £<span id="start-price"><?= number_format($item['price'],2) ?></span></p>
        <p><strong>Current Bid:</strong> £<span id="current-bid"><?= number_format($item['currentBid'],2) ?></span></p>
        <p><strong>Postage:</strong> £<?= number_format($item['postage'],2) ?></p>
        <p><strong>Start Time:</strong> <?= htmlspecialchars($item['start']) ?></p>
        <p><strong>Auction Ends:</strong> <?= htmlspecialchars($item['finish']) ?></p>
        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($item['description'])) ?></p>
        <p><strong>Item Location:</strong> <?= htmlspecialchars($postcode) ?></p>
        <form class="bid">
          <label for="bid">Enter your bid (£):</label>
          <input type="text" id="bid" name="bid" required>
          <button type="submit">Place Bid</button>
        </form>
        <div id="bid-message" style="margin:1em 0;color:red;"></div>
      </div>
    </div>

    <!-- Leaflet map initialization -->
    <div id="map"></div>
  </main>

  <div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="Enlarged item">
  </div>

  <div class="footer">&copy; 2025 iBay Inc. All rights reserved</div>

  <!-- SCRIPTS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
$(function(){
  // Map init (unchanged) …
  (function(){
    fetch(
      'https://nominatim.openstreetmap.org/search?format=json&postalcode=' +
      encodeURIComponent("<?= addslashes($postcode) ?>") +
      '&countrycodes=gb'
    )
    .then(r => r.json())
    .then(data => {
      if (!data.length) return;
      const lat = parseFloat(data[0].lat),
            lon = parseFloat(data[0].lon);
      const map = L.map('map').setView([lat, lon], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
      L.marker([lat, lon]).addTo(map);
      L.circle([lat, lon], { radius: 1000 }).addTo(map);
    })
    .catch(console.error);
  })();

  // Inject server‐side data into JS
  const USER_ID     = <?= json_encode($_SESSION['userId'] ?? '') ?>;
  const SELLER_ID   = <?= json_encode($item['userId']) ?>;
  const START_PRICE = <?= json_encode((float)$item['price']) ?>;
  const CURRENT_BID = <?= json_encode((float)$item['currentBid']) ?>;
  const FINISH_TIME = new Date(<?= json_encode($item['finish']) ?>);
  const ITEM_ID     = <?= json_encode($itemId) ?>;

  // Bid form logic
  const form = $('form.bid');
  function showMessage(msg, isError = true) {
    $('#bid-message')
      .text(msg)
      .css('color', isError ? 'red' : 'green');
  }

  if (!USER_ID) {
    form.find('input,button').prop('disabled', true);
    showMessage('Please log in to bid.');
  } else if (USER_ID === SELLER_ID) {
    form.find('input,button').prop('disabled', true);
    showMessage("You cannot bid on your own listing.");
  } else {
    form.on('submit', function(e){
      e.preventDefault();
      $('#bid-message').empty();
      let bidVal = $('#bid').val().trim();
      if (!/^\d+(\.\d{1,2})?$/.test(bidVal)) {
        return showMessage("Enter a valid amount up to two decimals.");
      }
      bidVal = parseFloat(bidVal);
      if (new Date() > FINISH_TIME) {
        return showMessage("Auction has ended.");
      }
      const minAllowed = Math.max(START_PRICE, CURRENT_BID) + 0.01;
      if (bidVal < minAllowed) {
        return showMessage(`Your bid must be at least £${minAllowed.toFixed(2)}.`);
      }

      // Submit via AJAX JSON
      $.ajax({
        url: 'placeBid.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ itemId: ITEM_ID, bid: bidVal })
      })
      .done(resp => {
        if (resp.success) {
          $('#current-bid').text(resp.newBid.toFixed(2));
          showMessage("Bid placed!", false);
          $('#bid').val('');
        } else {
          showMessage(resp.error);
        }
      })
      .fail(() => showMessage("Server error. Try again later."));
    });
  }
  $('.slide img').css('cursor','pointer').on('click', function(){
    $('#lightbox-img').attr('src', this.src);
    $('#lightbox').fadeIn();
  });
  $('.lightbox-close').on('click', ()=> $('#lightbox').fadeOut());
  $('#lightbox').on('click', e => {
    if (e.target.id === 'lightbox') $('#lightbox').fadeOut();
  });

  // --- SIMPLE SLIDER ---
  let currentIndex = 0;
  const slides = $('.slide');
  $('.next').on('click', function(){
    slides.eq(currentIndex).removeClass('active');
    currentIndex = (currentIndex + 1) % slides.length;
    slides.eq(currentIndex).addClass('active');
  });
  $('.prev').on('click', function(){
    slides.eq(currentIndex).removeClass('active');
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    slides.eq(currentIndex).addClass('active');
  });
});
</script>
</body>
</html>