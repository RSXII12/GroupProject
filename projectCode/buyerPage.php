<?php
session_start();
$isLoggedIn = isset($_SESSION['userId']);
$selectedCategory = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>iBay Home</title>
    <link rel="stylesheet" href="buyerPage.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script>
    $(function () {
        // Initialize price slider
        $("#price-slider").slider({
            range: true,
            min: 0,
            max: 500,
            values: [0, 500],
            slide: function (event, ui) {
                $("#price-range").val("£" + ui.values[0] + " - £" + ui.values[1]);
                $("#price-range-label").text("£" + ui.values[0] + " - £" + ui.values[1]);
            }
        });

        // Set initial label
        const slider = $("#price-slider").slider("values");
        $("#price-range").val("£" + slider[0] + " - £" + slider[1]);
        $("#price-range-label").text("£" + slider[0] + " - £" + slider[1]);

        // Pre-fill department if URL has category param
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get("category");
        if (category) {
            $("#department").val(category);
        }

        // Initial search
        performSearch();

        // Hook up filters
        $("#search-button").on("click", performSearch);
        $("#apply-filters").on("click", performSearch);
        $("#sort-options").on("change", performSearch);
    });

    function performSearch() {
        const searchText = $('#search-field').val().trim();
        const [minPrice, maxPrice] = $("#price-slider").slider("values");
        const timeRemaining = $('#time-remaining').val().trim();
        const location = $('#location').val().trim();
        const department = $('#department').val().trim();

        const params = {};

        if (searchText) params.searchText = searchText;
        if (department) params.department = department;
        if (timeRemaining) params.timeRemaining = timeRemaining;
        if (location) params.location = location;
        if (minPrice !== 0 || maxPrice !== 500) {
            params.minPrice = minPrice;
            params.maxPrice = maxPrice;
        }

        $.ajax({
            url: 'search.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function (items) {
                const sortBy = $('#sort-options').val();

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
            items.forEach(item => {
                html += `
                    <div class="result-card">
                        <a href="itemDetails.php?id=${item.itemId}" class="result-link">
                            <div class="image-container">
                                <img src="${item.image}" alt="${item.title}">
                            </div>
                            <div class="content">
                                <h4>${item.title}</h4>
                                <p class="category"><strong>Department:</strong> ${item.category}</p>
                                <p class="time-remaining"><strong>Time remaining:</strong> ${formatTime(item.time_remaining * 3600)}</p>
                                <p class="price">
                                    <strong>Starting Price:</strong> £${item.price} <br>
                                    <strong>Current Bid:</strong> £${item.currentBid ?? item.price} (+ £${item.postage} postage)
                                </p>
                                <p class="location"><strong>Location:</strong> ${item.location}</p>
                            </div>
                        </a>
                    </div>
                `;
            });
        }

        resultsDiv.innerHTML = html;
        container.appendChild(resultsDiv);
    }

    function formatTime(seconds) {
        const days = Math.floor(seconds / (3600 * 24));
        seconds %= 3600 * 24;
        const hours = Math.floor(seconds / 3600);
        seconds %= 3600;
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);
        return `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }
</script>
</head>
<body>
    <div class="header">
        <?php if ($isLoggedIn): ?>
            <span>Welcome! <a href="logout.php">Log out</a></span>
        <?php else: ?>
            <span>Please <a href="sellerLogin.html">Log in</a> or <a href="sellerSignUp.html">Sign up</a> to use iBay</span>
        <?php endif; ?>
        <a href="<?= $isLoggedIn ? 'sellerPage.html' : 'sellerLogin.html' ?>" class="create-listing">Create a listing</a>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="logo" style="text-align: center; margin-bottom: 20px;">
                <a href="index.php"><img src="iBay-logo.png" style="max-width: 150px; height: auto;"></a>
            </div>
            <div class="search-options">
                <h3>Advanced Search</h3>
                <select id="sort-options" class="sort-dropdown">
                    <option value="">Sort By</option>
                    <option value="price-asc">Starting Price: Low to High</option>
                    <option value="price-desc">Starting Price: High to Low</option>
                    <option value="bid-asc">Current Bid: Low to High</option>
                    <option value="bid-desc">Current Bid: High to Low</option>
                    <option value="time-asc">Time Remaining</option>
                </select>
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <option value="">Select a department</option>
                    <option value="Technology" <?= $selectedCategory === 'Technology' ? 'selected' : '' ?>>Technology</option>
                    <option value="Fashion" <?= $selectedCategory === 'Fashion' ? 'selected' : '' ?>>Fashion</option>
                    <option value="Home & Garden" <?= $selectedCategory === 'Home & Garden' ? 'selected' : '' ?>>Home & Garden</option>
                    <option value="Toys" <?= $selectedCategory === 'Toys' ? 'selected' : '' ?>>Toys</option>
                    <option value="Sports" <?= $selectedCategory === 'Sports' ? 'selected' : '' ?>>Sports</option>
                </select>
                <label for="price-range">Starting Price:</label>
                <input type="text" id="price-range" readonly style="border:0;">
                <div id="price-slider"></div>
                <label for="time-remaining">Time Remaining (hours)</label>
                <input type="number" id="time-remaining" min="1" placeholder="Enter hours">
                <label for="location">Location</label>
                <input type="text" id="location" placeholder="Enter location">
                <button id="apply-filters">Apply Filters</button>
            </div>
        </div>

        <div class="main-content">
            <div style="display: flex; gap: 10px;">
                <input type="text" id="search-field" class="search-bar" placeholder="Search field">
                <button id="search-button">Search</button>
            </div>
        </div>
    </div>

    <div class="footer">
        Copyright @2025-25 iBay Inc. All rights reserved
    </div>
</body>
</html>