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

$itemId = isset($_GET['id']) ? $_GET['id'] : '';
if (!empty($itemId)) {
    $stmt = $conn->prepare("SELECT itemId, userId, title, description, price, currentBid, bidUser, category, postage, start, finish FROM iBayItems WHERE itemId = ?");
    $stmt->bind_param("s", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $sellerId = $row['userId'];
        $loggedIn = isset($_SESSION['userId']);
        $currentUserId = $loggedIn ? $_SESSION['userId'] : null;

        // ----------------- Bid Submission Logic -----------------
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bidAmount'])) {
            if (!$loggedIn) {
                header("Location: sellerLogin.html");
                exit;
            }

            if ($currentUserId === $sellerId) {
                echo "<p style='color:red;'>❌ You cannot bid on your own item.</p>";
            } else {
                $startingPrice = floatval($row['price']);
                $currentBid = floatval($row['currentBid']); // could be 0.00
                $bidAmount = floatval($_POST['bidAmount']);

                // Minimum valid bid is greater than both currentBid and startingPrice
                $minRequiredBid = max($startingPrice + 0.01, $currentBid + 0.01);

                if ($bidAmount < $minRequiredBid) {
                    echo "<p style='color:red;'>❌ Your bid must be greater than both the starting price (£" . number_format($startingPrice, 2) . ") and current bid (£" . number_format($currentBid, 2) . "). Minimum allowed: £" . number_format($minRequiredBid, 2) . "</p>";
                } else {
                    $update = $conn->prepare("UPDATE iBayItems SET currentBid = ?, bidUser = ? WHERE itemId = ?");
                    $update->bind_param("dss", $bidAmount, $currentUserId, $itemId);
                    if ($update->execute()) {
                        echo "<p style='color:green;'>✅ Your bid of £" . number_format($bidAmount, 2) . " has been placed!</p>";
                        $row['currentBid'] = $bidAmount;
                        $row['bidUser'] = $currentUserId;
                    } else {
                        echo "<p style='color:red;'>❌ Failed to place bid: " . $conn->error . "</p>";
                    }
                    $update->close();
                }
            }
        }

        // ----------------- Display Item Info -----------------
        echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
        echo "<p><strong>Description:</strong> " . nl2br(htmlspecialchars($row['description'])) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><strong>Starting Price:</strong> £" . number_format($row['price'], 2) . "</p>";
        echo "<p><strong>Current Bid:</strong> £" . number_format($row['currentBid'] ?? $row['price'], 2) . "</p>";
        echo "<p><strong>Postage:</strong> £" . number_format($row['postage'], 2) . "</p>";
        echo "<p><strong>Start Time:</strong> " . htmlspecialchars($row['start']) . "</p>";
        echo "<p><strong>Auction Ends:</strong> " . htmlspecialchars($row['finish']) . "</p>";

        // ----------------- Display Images -----------------
        $imgStmt = $conn->prepare("SELECT image FROM iBayImages WHERE itemId = ? ORDER BY number ASC");
        $imgStmt->bind_param("s", $itemId);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();

        echo '<div class="image-gallery" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">';
        while ($imgRow = $imgResult->fetch_assoc()) {
            $imageData = base64_encode($imgRow['image']);
            echo '<img src="data:image/jpeg;base64,' . $imageData . '" style="width: 200px; height: auto; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
        }
        echo '</div>';

        // ----------------- Show Bid Form -----------------
        if (!$loggedIn) {
            echo "<p style='margin-top: 20px;'>🔒 <a href='sellerLogin.html'>Log in</a> to place a bid.</p>";
        } elseif ($currentUserId !== $sellerId) {
            $startingPrice = floatval($row['price']);
            $currentBid = floatval($row['currentBid']);
            $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

            echo '<div style="margin-top: 30px;">';
            echo '<form action="" method="POST">';
            echo '<label for="bidAmount"><strong>Enter your bid (£):</strong></label><br>';
            echo '<input type="number" step="0.01" min="' . number_format($minBid, 2, '.', '') . '" name="bidAmount" required style="padding: 8px; margin: 10px 0;"><br>';
            echo '<button type="submit" style="padding: 10px 20px; background-color: #0077b6; color: white; border: none; border-radius: 4px;">Place Bid</button>';
            echo '</form>';
            echo '</div>';
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