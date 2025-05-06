<?php
session_start();

// DB Connection
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

    <!-- Page Layout -->
    <div class="container">

        <!-- Main Content -->
        <div class="main-content">
            <?php
            if (!empty($itemId)) {
                $stmt = $conn->prepare("SELECT itemId, userId, title, description, price, currentBid, bidUser, category, postage, start, finish FROM iBayItems WHERE itemId = ?");
                $stmt->bind_param("s", $itemId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $sellerId = $row['userId'];
                    $loggedIn = isset($_SESSION['userId']);
                    $currentUserId = $loggedIn ? $_SESSION['userId'] : null;

                    // Bid submission logic
                    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bidAmount'])) {
                        if (!$loggedIn) {
                            header("Location: sellerLogin.html");
                            exit;
                        }

                        if ($currentUserId === $sellerId) {
                            echo "<p class='error'> You cannot bid on your own item.</p>";
                        } else {
                            $startingPrice = floatval($row['price']);
                            $currentBid = floatval($row['currentBid']);
                            $rawInput = $_POST['bidAmount'];
                            if (strpos($rawInput, '.') !== false) {
                                $bidAmount = floatval($rawInput); // has decimal, use as-is
                            } else {
                                $bidAmount = floatval($rawInput); // no decimal, still use as-is
                            }
                            $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

                            if ($bidAmount < $minBid) {
                                echo "<p class='error'> Your bid must be at least £" . number_format($minBid, 2) . "</p>";
                            } else {
                                $update = $conn->prepare("UPDATE iBayItems SET currentBid = ?, bidUser = ? WHERE itemId = ?");
                                $update->bind_param("dss", $bidAmount, $currentUserId, $itemId);
                                if ($update->execute()) {
                                    echo "<p class='success'> Bid of £" . number_format($bidAmount, 2) . " placed!</p>";
                                    $row['currentBid'] = $bidAmount;
                                    $row['bidUser'] = $currentUserId;
                                } else {
                                    echo "<p class='error'> Bid failed: " . $conn->error . "</p>";
                                }
                                $update->close();
                            }
                        }
                    }

                    // Item Info Display
                    echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
                    echo "<p><strong>Description:</strong> " . nl2br(htmlspecialchars($row['description'])) . "</p>";
                    echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
                    echo "<p><strong>Starting Price:</strong> £" . number_format($row['price'], 2) . "</p>";
                    echo "<p><strong>Current Bid:</strong> £" . number_format($row['currentBid'] ?: $row['price'], 2) . "</p>";
                    echo "<p><strong>Postage:</strong> £" . number_format($row['postage'], 2) . "</p>";
                    echo "<p><strong>Start Time:</strong> " . htmlspecialchars($row['start']) . "</p>";
                    echo "<p><strong>Auction Ends:</strong> " . htmlspecialchars($row['finish']) . "</p>";

                    // Image Gallery
                    $imgStmt = $conn->prepare("SELECT image FROM iBayImages WHERE itemId = ? ORDER BY number ASC");
                    $imgStmt->bind_param("s", $itemId);
                    $imgStmt->execute();
                    $imgResult = $imgStmt->get_result();

                    echo '<div class="image-gallery">';
                    while ($imgRow = $imgResult->fetch_assoc()) {
                        $imageData = base64_encode($imgRow['image']);
                        echo '<img src="data:image/jpeg;base64,' . $imageData . '" alt="Item image">';
                    }
                    echo '</div>';

                    // Bid Form
                    if (!$loggedIn) {
                        echo "<p>🔒 <a href='sellerLogin.html'>Log in</a> to place a bid.</p>";
                    } elseif ($currentUserId !== $sellerId) {
                        $startingPrice = floatval($row['price']);
                        $currentBid = floatval($row['currentBid']);
                        $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

                        echo '<form action="" method="POST">';
                        echo '<label for="bidAmount"><strong>Enter your bid (£):</strong></label><br>';
                        echo '<input type="text" name="bidAmount" pattern="^\d+(\.\d{1,2})?$" title="Enter a bid>';
                        echo '<br>';
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

            $conn->close();
            ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
    Copyright @2025-25 iBay Inc. All rights reserved
    </div>
</body>
</html>