<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function redirectToCategory(category) {
            window.location.href = "buyerPage.php?category=" + encodeURIComponent(category);
        }
    </script>
</head>

<body>
    <h2>
        <div class="logo" style="text-align: center; margin-bottom: 20px;">
            <a href="index.php"><img src="iBay-logo.png" style="max-width: 150px; height: auto;"></a>
        </div>
    </h2>

    <div class="nav-container">
        <div class="w3-bar">
            <a href="buyerPage.php" class="w3-bar-item w3-button">Browse</a>
            <a href="sellerLogin.html" class="w3-bar-item w3-button">Sell</a>
        </div>
    </div>

    <div class="description-image-container">
        <div id="items-container">
            <?php include 'fetchItems.php'; ?>  <!-- Corrected PHP  -->
        </div>
    </div>

    <button class="sidebar-toggle" onclick="toggleSidebar()">☰ Categories</button>
    <div id="category-sidebar" class="sidebar">
        <a href="buyerPage.php?category=Technology">Technology</a>
        <a href="buyerPage.php?category=Fashion">Fashion</a>
        <a href="buyerPage.php?category=Home & Garden">Home & Garden</a>
        <a href="buyerPage.php?category=Toys">Toys</a>
        <a href="buyerPage.php?category=Sports">Sports</a>
    </div>

    <script>
        function toggleSidebar() {
            let sidebar = document.getElementById("category-sidebar");
            sidebar.classList.toggle("open-sidebar");
        }
    </script>
</body>

</html>
