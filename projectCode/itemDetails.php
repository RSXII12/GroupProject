<?php
session_start(); // Start session to access login info

// DB connection setup
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get item ID from URL (e.g. itemDetails.php?id=abc123)
$itemId = $_GET['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Details</title>
    <link rel="stylesheet" href="item.css">
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <a href="index.php"><img src="iBay-logo.png" alt="iBay logo"></a>
    </div>
    <div class="header-center"></div>
    <div class="header-right"></div>
</div>

<!-- Main container -->
<div class="container">
    <div class="main-content">
        <?php
        if (!empty($itemId)) {
            // Fetch item details
            $stmt = $conn->prepare("
                SELECT itemId, userId, title, description, price, currentBid, bidUser, category, postage, start, finish
                FROM iBayItems
                WHERE itemId = ?
            ");
            $stmt->bind_param("s", $itemId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Get user info
                $sellerId = $row['userId'];
                $loggedIn = isset($_SESSION['userId']);
                $currentUserId = $loggedIn ? $_SESSION['userId'] : null;

                // Handle bid submission (if user posted a bid)
                if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bidAmount'])) {
                    // Redirect if not logged in
                    if (!$loggedIn) {
                        header("Location: sellerLogin.html");
                        exit;
                    }

                    // Prevent seller from bidding on their own listing
                    if ($currentUserId === $sellerId) {
                        echo "<p class='error'>You cannot bid on your own item.</p>";
                    } else {
                        // Get price info
                        $startingPrice = floatval($row['price']);
                        $currentBid = floatval($row['currentBid']);
                        $bidAmount = floatval($_POST['bidAmount']);

                        // Determine minimum allowed bid
                        $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

                        // Reject bid if too low
                        if ($bidAmount < $minBid) {
                            echo "<p class='error'>Your bid must be at least £" . number_format($minBid, 2) . "</p>";
                        } else {
                            // Update bid in the database
                            $update = $conn->prepare("
                                UPDATE iBayItems SET currentBid = ?, bidUser = ? WHERE itemId = ?
                            ");
                            $update->bind_param("dss", $bidAmount, $currentUserId, $itemId);
                            if ($update->execute()) {
                                echo "<p class='success'>Bid of £" . number_format($bidAmount, 2) . " placed!</p>";
                                $row['currentBid'] = $bidAmount; // update displayed bid
                                $row['bidUser'] = $currentUserId;
                            } else {
                                echo "<p class='error'>Bid failed: " . $conn->error . "</p>";
                            }
                            $update->close();
                        }
                    }
                }

                // Display item info
                echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
                echo "<p><strong>Description:</strong> " . nl2br(htmlspecialchars($row['description'])) . "</p>";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
                echo "<p><strong>Starting Price:</strong> £" . number_format($row['price'], 2) . "</p>";
                echo "<p><strong>Current Bid:</strong> £" . number_format($row['currentBid'] ?: $row['price'], 2) . "</p>";
                echo "<p><strong>Postage:</strong> £" . number_format($row['postage'], 2) . "</p>";
                echo "<p><strong>Start Time:</strong> " . htmlspecialchars($row['start']) . "</p>";
                echo "<p><strong>Auction Ends:</strong> " . htmlspecialchars($row['finish']) . "</p>";

                // Load item images
                $imgStmt = $conn->prepare("SELECT image FROM iBayImages WHERE itemId = ? ORDER BY number ASC");
                $imgStmt->bind_param("s", $itemId);
                $imgStmt->execute();
                $imgResult = $imgStmt->get_result();

                // Output images
                echo '<div class="image-gallery">';
                while ($imgRow = $imgResult->fetch_assoc()) {
                    $imageData = base64_encode($imgRow['image']);
                    echo '<img src="data:image/jpeg;base64,' . $imageData . '" alt="Item image">';
                }
                echo '</div>';

                $imgStmt->close();

                // Show bid form (if user is not the seller)
                if (!$loggedIn) {
                    echo "<p><a href='sellerLogin.html'>Log in</a> to place a bid.</p>";
                } elseif ($currentUserId !== $sellerId) {
                    $startingPrice = floatval($row['price']);
                    $currentBid = floatval($row['currentBid']);
                    $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

                    echo '<form action="" method="POST">';
                    echo '<label for="bidAmount"><strong>Enter your bid (£):</strong></label><br>';
                    echo '<input type="text" name="bidAmount" required pattern="^\d+(\.\d{1,2})?$" title="Enter a valid amount (e.g. 10 or 10.99)"><br>';
                    echo '<button type="submit">Place Bid</button>';
                    echo '</form>';
                }
            } else {
                echo "<p>Item not found.</p>";
            }

            $stmt->close();
        } else {
            echo "<p>Invalid item ID.</p>";
        }

        $conn->close(); // cleanup
        ?>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    Copyright @2025-25 iBay Inc. All rights reserved
</div>
</body>
</html>