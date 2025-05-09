<?php
session_start();
//determine if user is logged in
$isLoggedIn = isset($_SESSION['userId']);
//get category from url if exists for lookup
$selectedCategory = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>iBay Home</title>
    <link rel="stylesheet" href="buyerPage.css">
    <!--jQuery and jQuery ui for sliders-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script>
        $(function () {
            //initialise slider
            $("#price-slider").slider({
                range: true,
                min: 0,
                max: 5000,
                values: [0, 5000],
                slide: function (event, ui) {
                    $("#price-range").val("£" + ui.values[0] + " - £" + ui.values[1]);
                    $("#price-range-label").text("£" + ui.values[0] + " - £" + ui.values[1]);
                }
            });
            //show initial label
            const slider = $("#price-slider").slider("values");
            $("#price-range").val("£" + slider[0] + " - £" + slider[1]);
            $("#price-range-label").text("£" + slider[0] + " - £" + slider[1]);
            //auto search on page load (url category integration)
            performSearch();
            //connect search buttons with function
            $("#search-button").on("click", performSearch);
            $("#apply-filters").on("click", performSearch);
            
        });

        function performSearch() {
            //select filters
            const searchText = $('#search-field').val().trim();
            const [minPrice, maxPrice] = $("#price-slider").slider("values");
            const timeRemaining = $('#time-remaining').val().trim();
            const location = $('#location').val().trim();
            const department = $('#department').val().trim();
            const freePostage = $('#free-postage').is(':checked');
            //add conditionally
            const params = {};
            if (searchText) params.searchText = searchText;
            if (department) params.department = department;
            if (timeRemaining) params.timeRemaining = timeRemaining;
            if (location) params.location = location;
            if (freePostage) params.freePostage = 1;
            if (minPrice !== 0 || maxPrice !== 5000) {
                params.minPrice = minPrice;
                params.maxPrice = maxPrice;
            }
            //send ajax request
            $.ajax({
                url: 'search.php',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function (items) {
                    const sortBy = $('#sort-options').val();
                    //sort on client side
                    if (sortBy === "price-asc") {
                        items.sort((a, b) => a.price - b.price);
                    } else if (sortBy === "price-desc") {
                        items.sort((a, b) => b.price - a.price);
                    } else if (sortBy === "time-asc") {
                        items.sort((a, b) => a.time_remaining - b.time_remaining);
                    } else if (sortBy === "bid-desc") {
                        items.sort((a, b) => (b.currentBid ?? b.price) - (a.currentBid ?? a.price));
                    } else if (sortBy === "bid-asc") {
                        items.sort((a, b) => (a.currentBid ?? a.price) - (b.currentBid ?? b.price));
                    }
                    //render results
                    displayResults(items);
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    $(".main-content").append("<p>Error loading results.</p>");
                }
            });
        }

        function displayResults(items) {
            const container = document.querySelector(".main-content");
            let existingResults = container.querySelector(".search-results");
            if (existingResults) existingResults.remove();

            const resultsDiv = document.createElement("div");
            resultsDiv.classList.add("search-results");

            let html = "";

            if (!items.length) {
                html += "<p>No matching items found.</p>";
            } else {
                //render itemCards
                items.forEach(item => {
                    const descriptionHTML = item.description
                ? `<div class="description">${item.description}</div>`
                : '';
                    html += `
                        <div class="result-card">
                        <img src="${item.image}" alt="Item Image" />
                        <div class="card-content">
                            <a href="itemDetails.php?id=${item.itemId}">
                                <h4>${item.title}</h4>
                            </a>
                            <div class="card-details">
                            <div class="left-info">
                                <div class="price">Starting Price: £${item.price}</div>
                                <div class="bid"> Current Bid: £${item.currentBid ?? item.price}</div>
                                <div class = "postage">Postage: £${item.postage}</div>
                                <div class="location">Postcode: ${item.location}</div>
                                <div class="category">Category: ${item.category}</div>
                                <div class="time-remaining">Time remaining: ${formatTime(item.time_remaining)}</div>
                            </div>
                            ${descriptionHTML}
                            </div>
                        </div>
                        </div>
                    `;
                });
            }
                        

            resultsDiv.innerHTML = html;
            container.appendChild(resultsDiv);
        }

        function formatTime(secondsLeft) {
    const days = Math.floor(secondsLeft / 86400);
    const hours = Math.floor((secondsLeft % 86400) / 3600);
    const minutes = Math.floor((secondsLeft % 3600) / 60);

    let result = '';
    if (days > 0) result += `${days}d `;
    if (hours > 0 || days > 0) result += `${hours}h `;
    if (minutes > 0 || hours > 0 || days > 0) result += `${minutes}m`;
    if (result === '') result = 'Less than 1m left';
    else result += ' left';

    return result;
}
    </script>
</head>
<body>
    <!--Header-->
    <div class="header">
        <?php if ($isLoggedIn): ?>
            <span>Welcome! <a href="logout.php">Log out</a></span>
        <?php else: ?>
            <span>Please <a href="sellerLogin.html">Log in</a> or <a href="sellerSignUp.html">Sign up</a> to use iBay</span>
        <?php endif; ?>
        <a href="<?= $isLoggedIn ? 'sellerPage.html' : 'sellerLogin.html' ?>" class="create-listing">Create a listing</a>
    </div>
    <!-- MAIN LAYOUT -->        
    <div class="container">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="logo">
                <a href="index.php"><img src="iBay-logo.png" class="img"></a>
            </div>
            <div class="search-options">
                <h3>Advanced Search</h3>
                <!-- Sort dropdown -->
                <select id="sort-options" class="sort-dropdown">
                    <option value="">Sort By</option>
                    <option value="price-asc">Starting Price: Low to High</option>
                    <option value="price-desc">Starting Price: High to Low</option>
                    <option value="bid-asc">Current Bid: Low to High</option>
                    <option value="bid-desc">Current Bid: High to Low</option>
                    <option value="time-asc">Time Remaining</option>
                </select>

                <!-- Department filter -->
                <label for="department">Department</label>
                <select id="department" name="department" class ="id-dropdown">
                    <option value="">Select a department</option>
                    <option value="Technology" <?= $selectedCategory === 'Technology' ? 'selected' : '' ?>>Technology</option>
                    <option value="Fashion" <?= $selectedCategory === 'Fashion' ? 'selected' : '' ?>>Fashion</option>
                    <option value="Home & Garden" <?= $selectedCategory === 'Home & Garden' ? 'selected' : '' ?>>Home & Garden</option>
                    <option value="Toys" <?= $selectedCategory === 'Toys' ? 'selected' : '' ?>>Toys</option>
                    <option value="Sports" <?= $selectedCategory === 'Sports' ? 'selected' : '' ?>>Sports</option>
                </select>

                <!-- Price Range -->
                <label for="price-range">Starting Price:</label>
                <input type="text" id="price-range" readonly style="border:0;width:95%">
                <div id="price-slider"></div>

                <!-- Time remaining filter -->
                <label for="time-remaining">Time Remaining (hours)</label>
                <input type="number" id="time-remaining" min="1" placeholder="Enter hours" class="time-box">

                <!-- Location filter -->
                <label for="location">Postcode</label>
                <input type="text" id="location" placeholder="Enter location" class ="postcode-box">

                <!-- Free postage toggle -->
                <label>
                    <input type="checkbox" id="free-postage"> Free Postage
                </label>
                <button id="apply-filters">Apply Filters</button>
            </div>
        </div>
        <!-- SEARCH & RESULTS -->
        <div class="main-content">
            <div style="display: flex; gap: 10px;">
                <input type="text" id="search-field" class="search-bar" placeholder="Search field">
                <button id="search-button">Search</button>
            </div>
        </div>
    </div>
    <!-- FOOTER -->        
    <div class="footer">
        Copyright @2025-25 iBay Inc. All rights reserved
    </div>

</body>
</html>