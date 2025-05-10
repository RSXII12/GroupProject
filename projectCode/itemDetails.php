<?php
session_start();

// grab & validate ID
if (!isset($_GET['id']) || !preg_match('/^[a-z0-9]+$/i', $_GET['id'])) {
    die("Invalid item ID.");
}
$itemId = $_GET['id'];


$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";

$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

//  fetch item
$stmt = $mysqli->prepare("
    SELECT userId, title, category, description, price, postage, start, finish, currentBid
    FROM iBayItems
    WHERE itemId = ?
");
$stmt->bind_param('s', $itemId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Item not found.");
}
$item = $res->fetch_assoc();
$stmt->close();

// fetch seller postcode
$stmt = $mysqli->prepare("
    SELECT postcode
    FROM iBayMembers
    WHERE userId = ?
");
$stmt->bind_param('s', $item['userId']);
$stmt->execute();
$res = $stmt->get_result();
$member   = $res->fetch_assoc();
$postcode = $member['postcode'] ?? '';
$stmt->close();

//  fetch images
$stmt = $mysqli->prepare("
    SELECT image
    FROM iBayImages
    WHERE itemId = ?
    ORDER BY number
");
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
  <title><?php echo htmlspecialchars($item['title']); ?></title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
  <style>
    /* GLOBAL & LAYOUT */
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

    /* HEADER */
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
    .header-left img {
      width: 30%; height: auto; object-fit: cover;
    }

    /* MAIN CONTENT */
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

    /* IMAGE SLIDER */
    .images {
  position: relative;
  width: 400px;
  height: 400px;
  overflow: hidden;
  display: flex;              /* container flex so active slide can center */
  align-items: center;
  justify-content: center;
  background: transparent;     /* or whatever your page bg is */
}

.images .slide {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  display: none;              /* hide by default */
}

.images .slide.active {
  display: flex;              /* show only the active one */
  align-items: center;        /* center its img vertically */
  justify-content: center;    /* center its img horizontally */
}

.images .slide img {
  max-width: 100%;            /* scale to fit container */
  max-height: 100%;
  width: auto;
  height: auto;
  display: block;
}

/* keep your prev/next buttons the same */
.images button {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  /* …other styles… */
}
    .images .prev { left: 0.5em; }
    .images .next { right: 0.5em; }

    /* DETAILS & FORM */
    .details { flex: 1; }
    .details p { margin: 0.5em 0; }
    form.bid {
      margin: 1em 0; padding: 0.5em;
      border: 1px solid #888; display: inline-block;
    }
    form.bid input[type="text"] { width: 8em; }

    /* MAP */
    #map {
      margin-top: 1em;
      width: 100%;
      height: 300px;
      border: 1px solid #999;
    }

    /* LIGHTBOX */
    .lightbox {
      position: fixed;
      top: 0; left: 0;
      width: 100vw; height: 100vh;
      display: none;
      justify-content: center; align-items: center;
      background: rgba(0,0,0,0.8);
      z-index: 10000;
    }
    .lightbox img {
      max-width: 90%; max-height: 90%;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }
    .lightbox-close {
      position: absolute; top: 20px; right: 30px;
      font-size: 2rem; color: #fff; cursor: pointer;
      user-select: none;
    }

    /* FOOTER */
    .footer {
      flex: 0 0 auto;
      background: #2f64ad; color: #fff;
      text-align: center; padding: 10px; font-size: 12px;
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <div class="header">
    <div class="header-left">
      <a href="index.php"><img src="iBay-logo.png" alt="iBay logo"></a>
    </div>
    <div class="header-center">iBay</div>
    <div class="header-right"></div>
  </div>

  <!-- MAIN CONTENT -->
  <main class="page-content">
    <div class="flex">
      <!-- IMAGE SLIDER -->
      <div class="images">
        <?php foreach($images as $i => $src): ?>
          <div class="slide<?php echo $i === 0 ? ' active' : ''; ?>">
            <img src="<?php echo $src; ?>" alt="Item image <?php echo $i+1; ?>">
          </div>
        <?php endforeach; ?>
        <button class="prev">&larr;</button>
        <button class="next">&rarr;</button>
      </div>

      <!-- DETAILS -->
      <div class="details">
        <h1><?php echo htmlspecialchars($item['title']); ?></h1>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
        <p><strong>Starting Price:</strong> £<?php echo number_format($item['price'],2); ?></p>
        <p><strong>Current Bid:</strong> £<?php echo number_format($item['currentBid'],2); ?></p>
        <p><strong>Postage:</strong> £<?php echo number_format($item['postage'],2); ?></p>
        <p><strong>Start Time:</strong> <?php echo htmlspecialchars($item['start']); ?></p>
        <p><strong>Auction Ends:</strong> <?php echo htmlspecialchars($item['finish']); ?></p>

        <form class="bid" method="post" action="placeBid.php">
          <label for="bid">Enter your bid (£):</label>
          <input type="text" id="bid" name="bid" required>
          <input type="hidden" name="itemId" value="<?php echo htmlspecialchars($itemId); ?>">
          <button type="submit">Place Bid</button>
        </form>

        <p><strong>Description:</strong><br>
          <?php echo nl2br(htmlspecialchars($item['description'])); ?>
        </p>
        <p><strong>Item Location:</strong> <?php echo htmlspecialchars($postcode); ?></p>
      </div>
    </div>

    <!-- MAP -->
    <div id="map"></div>
  </main>

  <!-- LIGHTBOX (outside of main) -->
  <div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="Enlarged item">
  </div>

  <!-- FOOTER -->
  <div class="footer">
    &copy; 2025 iBay Inc. All rights reserved
  </div>

  <!-- SCRIPTS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    // BID VALIDATION
    const USER_ID     = "<?php echo addslashes($_SESSION['userId'] ?? ''); ?>";
    const SELLER_ID   = "<?php echo addslashes($item['userId']); ?>";
    const START_PRICE = <?php echo json_encode((float)$item['price']); ?>;
    const CURRENT_BID = <?php echo json_encode((float)$item['currentBid']); ?>;
    const FINISH_TIME = new Date("<?php echo $item['finish']; ?>");

    function showError(msg) {
      alert(msg);
    }

    document.addEventListener('DOMContentLoaded', () => {
      // disable self-bidding
      const form = document.querySelector('form.bid');
      if (USER_ID && USER_ID === SELLER_ID) {
        form.querySelector('input[name="bid"]').disabled = true;
        form.querySelector('button[type="submit"]').disabled = true;
        const note = document.createElement('p');
        note.textContent = "You cannot bid on your own listing.";
        note.style.color = "red";
        form.parentNode.insertBefore(note, form);
        return;
      }

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const bidStr = this.bid.value.trim();
        const moneyRE = /^(\d+)(\.\d{1,2})?$/;
        if (!moneyRE.test(bidStr)) return showError("Enter a non-negative amount with max two decimals.");
        const bidVal = parseFloat(bidStr);
        if (bidVal < 0) return showError("Your bid cannot be negative.");
        if (new Date() > FINISH_TIME) return showError("Auction has ended.");
        const minAllowed = Math.max(START_PRICE, CURRENT_BID) + 0.01;
        if (bidVal < minAllowed) return showError(`Bid must be at least £${minAllowed.toFixed(2)}.`);
        this.submit();
      });

      // LIGHTBOX
      const lightbox = document.getElementById('lightbox');
      const lbImg     = document.getElementById('lightbox-img');
      const lbClose   = document.querySelector('.lightbox-close');
      document.querySelectorAll('.slide img').forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', () => {
          lbImg.src = img.src;
          lightbox.style.display = 'flex';
        });
      });
      lbClose.addEventListener('click', () => lightbox.style.display = 'none');
      lightbox.addEventListener('click', e => {
        if (e.target === lightbox) lightbox.style.display = 'none';
      });

      // IMAGE SLIDER
      (() => {
        const slides = document.querySelectorAll('.slide');
        let idx = 0;
        document.querySelector('.next').onclick = () => {
          slides[idx].classList.remove('active');
          idx = (idx + 1) % slides.length;
          slides[idx].classList.add('active');
        };
        document.querySelector('.prev').onclick = () => {
          slides[idx].classList.remove('active');
          idx = (idx - 1 + slides.length) % slides.length;
          slides[idx].classList.add('active');
        };
      })();

      // MAP & GEOCODING
      (() => {
        fetch(
          'https://nominatim.openstreetmap.org/search?format=json&postalcode=' +
          encodeURIComponent("<?php echo addslashes($postcode); ?>") +
          '&countrycodes=gb'
        )
        .then(r => r.json())
        .then(data => {
          if (!data.length) return;
          const lat = parseFloat(data[0].lat),
                lon = parseFloat(data[0].lon);
          const map = L.map('map').setView([lat, lon], 13);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
          }).addTo(map);
          L.marker([lat, lon]).addTo(map);
          L.circle([lat, lon], { radius: 1000 }).addTo(map);
        })
        .catch(console.error);
      })();
    });
  </script>
</body>
</html>