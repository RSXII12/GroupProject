<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
$sellLink = isset($_SESSION['userId']) ? 'sellerPage.html' : 'sellerLogin.html';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>iBay – Home</title>
    <link rel="stylesheet" href="index.css" />
    <script>
        function toggleSidebar() {
            document.getElementById("category-sidebar").classList.toggle("open-sidebar");
        }
    </script>
</head>
<body>

    <!-- Sidebar Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰ Categories</button>

    <!-- Sidebar Menu -->
    <div id="category-sidebar" class="sidebar">
        <?php
        $categories = [
            "Books", "Clothing", "Computing", "DvDs", "Electronics", "Collectables",
            "Home & Garden", "Music", "Outdoors", "Toys", "Sports Equipment"
        ];
        foreach ($categories as $cat) {
            echo '<a href="buyerPage.php?category=' . urlencode($cat) . '">' . htmlspecialchars($cat) . '</a>';
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
            <a href="<?= $sellLink ?>" class="w3-bar-item w3-button">Sell</a>
        </div>
    </div>

<!-- Page Title -->
<div class="page-title">
    <h2>Today's Top Picks</h2>
</div>
        
    <!-- Items Grid -->
    <main id="items-container">
        <?php include 'fetchItems.php'; ?>
    </main>

    <footer class="footer">
        &copy; <?= date("Y") ?> iBay Inc. All rights reserved.
    </footer>

</body>
</html