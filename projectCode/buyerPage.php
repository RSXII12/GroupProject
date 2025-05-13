<?php
session_start(); //start/restart session
$isLoggedIn = isset($_SESSION['userId']); //determine if user is logged in 
$selectedCategory = $_GET['category'] ?? '';//get selected category from url if exists
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Search iBay</title>
  <link rel="stylesheet" href="buyerPage.css">
  <!-- jQuery & jQuery UI -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body>
  <!-- HEADER -->
  <div class="header">
    <?php if($isLoggedIn)://handle user welcome if logged in, change site layout as appropriate ?> 
      <span>You are logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>.
        <a href="logout.php">Log out</a> |
        <a href="listingPage.php">View My Listings</a>
      </span>
    <?php else: ?>
      <span>Please <a href="sellerLogin.html">Log in</a> or
      <a href="sellerSignUp.html">Sign up</a> to use iBay</span>
    <?php endif; ?>
    <a href="<?= $isLoggedIn ? 'sellerPage.html' : 'sellerLogin.html' ?>" class="create-listing"> <!--Create link for listings, redirect as appropriate-->
      Create a listing
    </a>
  </div>

  <!-- MAIN LAYOUT -->
  <div class="container">
    <div class="sidebar">
      <div class="logo">
        <a href="index.php"><img src="iBay-logo.png" alt="iBay logo"></a>
      </div>
    <!--Filters, advanced search and pagenation-->
      <h3>Advanced Search</h3>
      <label for="items-per-page">Items per page:</label>
      <select id="items-per-page">
        <option value="5" selected>5</option>
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="50">50</option>
      </select>

      <label for="sort-options">Sort By</label>
      <select id="sort-options">
        <option value="">—</option>
        <option value="price-asc">Price ↑</option>
        <option value="price-desc">Price ↓</option>
        <option value="bid-asc">Bid ↑</option>
        <option value="bid-desc">Bid ↓</option>
        <option value="time-asc">Time Remaining</option>
      </select>

      <label for="department">Department</label>
      <select id="department">
        <option value="">All</option>
        <?php
          $depts = ['Books','Clothing','Computing','DvDs','Electronics',
                    'Collectables','Home & Garden','Music','Outdoors',
                    'Toys','Sports Equipment'];//allowed departments
          foreach($depts as $d) {//generate from list of allowed departments 
            $sel = $selectedCategory === $d ? ' selected' : '';
            echo "<option value=\"{$d}\"{$sel}>{$d}</option>";
          }
        ?>
      </select>

      <label for="price-range">Starting Price:</label>
      <input type="text" id="price-range" readonly>
      <div id="price-slider"></div>

      <label for="time-remaining">Time Remaining (days):</label>
      <input type="number" id="time-remaining" min="1" placeholder="e.g. 2">

      <label for="location">Postcode</label>
      <input type="text" id="location" placeholder="e.g. AB12 3CD">

      <label><input type="checkbox" id="free-postage"> Free Postage</label>
      <button id="apply-filters">Apply Filters</button>
    </div>

    <div class="main-content">
      <div class="search-bar-container" style="display:flex; width:100%; gap:8px; margin-bottom:16px;">
        <input type="text" id="search-field" placeholder="Search…" style="flex:1; padding:8px;" /> <!--Free text search bar-->
        <button id="search-button" style="padding:8px 16px;">Search</button>
      </div>
      <!--Pagenation buttons-->
      <div id="pagination-container" class="pagination"></div>
    </div>
  </div>

  <div class="footer">© 2025 iBay Inc. All rights reserved</div>

  <script>
  $(function(){
    // Pre-select department if passed via URL
    // Initialise price-range slider (0–5000) and set its display
    // Hook all filter controls to performSearch()
    const initialCategory = '<?= addslashes($selectedCategory) ?>';
    if (initialCategory) $('#department').val(initialCategory);

    $("#price-slider").slider({
      range: true,
      min: 0,
      max: 5000,
      values: [0,5000],
      slide(_, ui){
        $("#price-range").val(`£${ui.values[0]} - £${ui.values[1]}`);
      }
    });
    const vals = $("#price-slider").slider("values");
    $("#price-range").val(`£${vals[0]} - £${vals[1]}`);

    $("#apply-filters, #search-button").on("click", ()=> performSearch(1));
    $("#sort-options, #items-per-page").on("change", ()=> performSearch(1));

    performSearch(); // Fetch & render first page of results
  });

  function performSearch(page=1) {
    const params = { page };
    const txt = $('#search-field').val().trim();
    if (txt) params.searchText = txt;

    const [minPrice, maxPrice] = $("#price-slider").slider("values");
    if (minPrice>0||maxPrice<5000) {
      params.minPrice = minPrice;
      params.maxPrice = maxPrice;
    }

    const dept = $('#department').val(); if (dept) params.department = dept;
    const days = $('#time-remaining').val(); if (days) params.timeRemaining = days;
    const loc  = $('#location').val().trim(); if (loc) params.location = loc;
    if ($('#free-postage').is(':checked')) params.freePostage = 1;
    const sort = $('#sort-options').val(); if (sort) params.sortBy = sort;
    const per  = parseInt($('#items-per-page').val(),10); if (per) params.perPage = per;
    // Read all filter inputs (searchText, minPrice, maxPrice, department, etc.)
    // Only add to params if the user has changed them from defaults
    $.getJSON('search.php', params)
      .done(data => { renderResults(data.items); renderPagination(data.page, data.total, data.perPage); })
      .fail(() => { $('.main-content').html('<p>Error loading results.</p>'); });
  }

  function renderResults(items) {
  // Clear old results
  // If no items: show “No matching items found.”
  // Otherwise, loop each item and build the HTML:
  //    Thumbnail (<img src="data:…">)
  //    Title link
  //    Starting price, Current bid, postage, location, category, time remaining
  //    Optional description block
    const container = $(".main-content");
    container.find(".search-results").remove();
    const $results = $('<div class="search-results">');
    if (!items.length) {
      $results.append('<p>No matching items found.</p>');
    } else {
      items.forEach(item => {
        const tm   = formatTime(item.time_remaining);
        const img  = `<img src="${item.image}" alt="">`;
        const desc = item.description ? `<div class="description">${item.description}</div>` : '';
        $results.append(`
          <div class="result-card">
            ${img}
            <div class="card-content">
              <a href="itemDetails.php?id=${item.itemId}">
                <h4>${item.title}</h4>
              </a>
              <div class="card-details">
                <div class="price">Starting price: £${item.price}</div>
                <div class="bid">Current bid: £${item.currentBid ?? item.price}</div>
                <div class="postage">Postage: £${item.postage}</div>
                <div class="location">Postcode: ${item.location}</div>
                <div class="category">Category: ${item.category}</div>
                <div class="time-remaining">${tm}</div>
                ${desc}
              </div>
            </div>
          </div>
        `);
      });
    }
    container.append($results);
  }

  function renderPagination(page, total, perPage) {
    // Calculate totalPages = ceil(total / perPage)
    // Render numbered buttons, disabling the current page
    // Clicking a button calls performSearch(page)
    const totalPages = Math.ceil(total / perPage);
    const $p = $('#pagination-container').empty();
    for (let i=1; i<= totalPages; i++) {
      const $btn = $(`<button>${i}</button>`);
      if (i===page) $btn.prop('disabled',true);
      $btn.on('click', ()=> performSearch(i));
      $p.append($btn);
    }
  }

  function formatTime(sec) {
    sec = sec -3600 //dealing with DST is awful and I just want the format to be correct :( 
    // -this is a bandaid fix because I only noticed the time discrepancies one day before deadline
    // Convert seconds to “Xd Xh Xm left”
    const d = Math.floor(sec/86400),
          h = Math.floor((sec%86400)/3600),
          m = Math.floor((sec%3600)/60);
    let s = '';
    if (d) s+= `${d}d `;
    if (h||d) s+= `${h}h `;
    s+= `${m}m`;
    return s + (s?' left':'');
  }
  </script>
</body>
</html>