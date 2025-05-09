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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
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
            $stmt = $conn->prepare("SELECT i.itemId, i.userId, i.title, i.description, i.price, 
	    i.currentBid, i.bidUser, i.category, i.postage, i.start, i.finish, m.postcode AS location 
                FROM iBayItems i
                LEFT JOIN iBayMembers m ON i.userId = m.userId
                WHERE itemId = ?");
	    $stmt->bind_param("s", $itemId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Get user info
	        $location = $row['location'];
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
                            echo "<p class='error'>Your bid must be at least Â£" . number_format($minBid, 2) . "</p>";
                        } else {
                            // Update bid in the database
                            $update = $conn->prepare("
                                UPDATE iBayItems SET currentBid = ?, bidUser = ? WHERE itemId = ?
                            ");
                            $update->bind_param("dss", $bidAmount, $currentUserId, $itemId);
                            if ($update->execute()) {
                                echo "<p class='success'>Bid of Â£" . number_format($bidAmount, 2) . " placed!</p>";
                                $row['currentBid'] = $bidAmount; // update displayed bid
                                $row['bidUser'] = $currentUserId;
                            } else {
                                echo "<p class='error'>Bid failed: " . $conn->error . "</p>";
                            }
                            $update->close();
                        }
                    }
                }
	    
	    // Load item images
            $imgStmt = $conn->prepare("SELECT image FROM iBayImages WHERE itemId = ? ORDER BY number ASC");
            $imgStmt->bind_param("s", $itemId);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();

	    $imgStmt->close();


	    echo '<div class="item-details-container">';
	    
	    // Output images
            echo '<div class="image-gallery">';
	    echo '<div class="image-block">';
	    $images = [];

            while ($imgRow = $imgResult->fetch_assoc()) {
                $imageData = base64_encode($imgRow['image']);
		$images[] = 'data:image/jpeg;base64,' . $imageData;
            }

	    foreach ($images as $index => $imageSrc) {
		$activeClass = $index === 0 ? 'active' : '';
    		echo "<img src=\"$imageSrc\" class=\"carousel-image $activeClass\" alt=\"Item Image\">";
	    }

	    echo '<button class="carousel-btn prev">&#10094;</button>';
	    echo '<button class="carousel-btn next">&#10095;</button>';

	    echo'</div>'; // close image-block

	    echo '<p class="item-desc"><strong>Description:</strong> ' . '<br>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
	    

	    echo '</div>'; // close image-gallery


                // Display item info
	        echo '<div class="item-info">';
                echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
                echo "<p><strong>Starting Price:</strong> Â£" . number_format($row['price'], 2) . "</p>";
                echo "<p><strong>Current Bid:</strong> Â£" . number_format($row['currentBid'] ?: $row['price'], 2) . "</p>";
                echo "<p><strong>Postage:</strong> Â£" . number_format($row['postage'], 2) . "</p>";
                echo "<p><strong>Start Time:</strong> " . htmlspecialchars($row['start']) . "</p>";
                echo "<p><strong>Auction Ends:</strong> " . htmlspecialchars($row['finish']) . "</p>";

                
                // Show bid form (if user is not the seller)
                if (!$loggedIn) {
                    echo "<p><a href='sellerLogin.html'>Log in</a> to place a bid.</p>";
                } elseif ($currentUserId !== $sellerId) {
                    $startingPrice = floatval($row['price']);
                    $currentBid = floatval($row['currentBid']);
                    $minBid = max($startingPrice + 0.01, $currentBid + 0.01);

                    echo '<form action="" method="POST">';
                    echo '<label for="bidAmount"><strong>Enter your bid (Â£):</strong></label><br>';
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
	    echo '</div>'; // close item info

	    echo '</div>'; // close item details container
	
	    // Display location and map
	    echo '<div class="location-box">';
	    echo '<p style="font-size: 18px; font-weight: bold;">Item Location:</p>';
	    echo '<p id="postcode-label"></p>';
	    echo '<div id="map" style="height: 200px; width: 100%;"></div>';
	    echo '</div>';


        $conn->close(); // cleanup
        ?>
    </div>
</div>

<script>

// Map Function
document.addEventListener('DOMContentLoaded', () => {
  const postcode = <?php echo json_encode($location); ?>;

  fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(postcode)}`)
    .then(res => res.json())
    .then(data => {
      if (postcode !== null) {
        const lat = data[0].lat;
        const lon = data[0].lon;

        const map = L.map('map').setView([lat, lon], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lon]).addTo(map)
	document.getElementById('postcode-label').innerText = `${postcode}`;
      } else {
	// if null then display error message
        document.getElementById('map').innerHTML = '<p style="color: purple;">Location not available</p>';
      }
    })
    .catch(() => {
	// if postcode invalid only shows error message, not postcode as well 
    	document.getElementById('postcode-label').innerText = 'Location not available';
    });
});


// Carousel Function
document.addEventListener('DOMContentLoaded', function () {
    let images = document.querySelectorAll('.carousel-image');
    let currentIndex = 0;

    function showImage(index) {
        images.forEach((img, i) => {
            img.classList.toggle('active', i === index);
        });
    }

    document.querySelector('.carousel-btn.prev').addEventListener('click', function () {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
    });

    document.querySelector('.carousel-btn.next').addEventListener('click', function () {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    });

    showImage(currentIndex);
});
</script>

<!-- Footer -->
<div class="footer">
    Copyright @2025-25 iBay Inc. All rights reserved
</div>
</body>
</html>
