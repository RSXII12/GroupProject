<?php
// index.php
session_start();
// Determine "Sell" link based on login status
$sellLink = isset($_SESSION['userId']) ? 'sellerPage.html' : 'sellerLogin.html';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>iBay – Home</title>
  <link rel="stylesheet" href="index.css" />
  <!-- jQuery for AJAX -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById("category-sidebar").classList.toggle("open-sidebar");
    }

    $(function(){
      // load today's top picks via AJAX
      const container = $('#items-container');
      $.getJSON('fetchItems.php')
        .done(function(items){
          if (!items.length) {
            // No picks available
            container.append('<p>No top picks available today.</p>');
            return;
          }
          // Loop through each item and render its card
          items.forEach(function(item){
            const imgSrc = item.image
              ? 'data:image/jpeg;base64,' + item.image
              : 'placeholder.jpg';
            const bidHtml = item.currentBid !== null // Determine bid text
              ? '<p>Current Bid: £' + item.currentBid.toFixed(2) + '</p>'
              : '<p class="no-bid">No bids yet</p>';
            const html = '' // Assemble the HTML for one card
              + '<a href="itemDetails.php?id=' + encodeURIComponent(item.itemId) + '" class="item-link">'
              +   '<div class="item-container">'
              +     '<div class="image-wrapper">'
              +       '<img src="' + imgSrc + '" alt="Item Image">'
              +     '</div>'
              +     '<h3>' + item.title + '</h3>'
              +     '<p>Starting price: £' + item.price.toFixed(2) + '</p>'
              +     bidHtml
              +   '</div>'
              + '</a>';
            container.append(html);
          });
        })
        .fail(function(){// Show error message on failure
          container.append('<p>Error loading top picks.</p>');
        });
    });
  </script>
</head>
<body>

  <!-- Sidebar Toggle -->
  <button class="sidebar-toggle" onclick="toggleSidebar()">☰ Categories</button>

  <!-- Sidebar Menu -->
  <div id="category-sidebar" class="sidebar">
    <?php
    $categories = [// Dynamically generate category links
      "Books","Clothing","Computing","DvDs","Electronics","Collectables",
      "Home & Garden","Music","Outdoors","Toys","Sports Equipment"
    ];
    foreach ($categories as $cat) {
      echo '<a href="buyerPage.php?category='
           . urlencode($cat) . '">'
           . htmlspecialchars($cat)
           . '</a>';
    }
    ?>
  </div>

  <!-- Header / Logo Section -->
  <div class="header">
    <div class="logo">
      <a href="index.php"><img src="iBay-logo.png" alt="iBay Logo" /></a>
    </div>
  </div>

  <!-- Navigation -->
  <div class="nav-container">
    <div class="w3-bar">
      <a href="buyerPage.php" class="w3-bar-item w3-button">Browse</a>
      <a href="<?= htmlspecialchars($sellLink) ?>"
         class="w3-bar-item w3-button">Sell</a>
    </div>
  </div>

  <!-- Page Title -->
  <div class="page-title">
    <h2>Today's Top Picks</h2>
  </div>

  <!-- Items Grid -->
  <main id="items-container">
    <!-- AJAX will populate here -->
  </main>

  <footer class="footer">
    &copy; <?= date("Y") ?> iBay Inc. All rights reserved.
  </footer>

</body>
</html>