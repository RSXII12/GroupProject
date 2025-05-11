<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if needed
session_start();

// DB connection
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if item ID is provided
if (!isset($_GET['id'])) {
    die("No item ID specified.");
}

$itemId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim inputs
    $title = htmlspecialchars(trim($_POST['title']));
    $category = htmlspecialchars(trim($_POST['category']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = trim($_POST['price']);
    $postage = trim($_POST['postage']);
    // Allowed categories to select - must be updated when new categories are added or removed
    $allowedCategories = ["Fashion", "Technology", "Home & Garden", "Sports", "Toys"];

    $errors = [];

    // Validate required fields
    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (strlen($title) < 4) {
        $errors[] = "Title must be at least 4 characters long.";
    }

    if (empty($category)) {
        $errors[] = "Category is required.";
    }

    if (!in_array($category, $allowedCategories)) {
        $errors[] = "Invalid category selected";
    }

    if (empty($description)) {
        $errors[] = "Description is required.";
    }

    // Validate numeric fields
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a valid non-negative number.";
    }

    if ($price > 5000) {
        $errors[] = "Price must be less than £5000";
    }

    if (!is_numeric($postage) || $postage < 0) {
        $errors[] = "Postage must be a valid non-negative number.";
    }

    // If no errors, proceed to update
    if (empty($errors)) {
        $price = floatval($price);
        $postage = floatval($postage);

        // Prepare UPDATE statement
        $stmt = $conn->prepare("
            UPDATE iBayItems 
            SET title = ?, category = ?, description = ?, price = ?, postage = ?
            WHERE itemId = ?
        ");
        $stmt->bind_param("sssdds", $title, $category, $description, $price, $postage, $itemId);

        if ($stmt->execute()) {
            echo "<p>Item updated successfully! <a href='listingPage.php'>Back to listings</a></p>";
        } else {
            echo "<p>Error updating item: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        // Display validation errors
        foreach ($errors as $error) {
            echo "<p style='color: red;'>" . htmlspecialchars($error) . "</p>";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM iBayItems WHERE itemId = ?");
$stmt->bind_param("s", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    die("Item not found.");
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Item - <?= htmlspecialchars($item['title']) ?></title>
    <link rel="stylesheet" href="editItem.css">
</head>
<body>
    <div class="header">
    <div class="header-left">
        <a href="index.php"><img src="iBay-logo.png" alt="iBay Logo" /></a>
    </div>
    <div class="header-center">
        Edit Listings
    </div>
    <div class="header-right"></div>
    </div>
    <div class="container">
        <h2>Edit Listing: <?= htmlspecialchars($item['title']) ?></h2>

        <?php
        // Show PHP-side validation errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<p style='color: red;'>" . htmlspecialchars($error) . "</p>";
            }
        }
        ?>

        <div class="form-container">
    <form method="POST" onsubmit="return validateForm();">
        <div class="row">
            <div class="form-group">
                <label for="title">Listing Name</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($item['title']) ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="category">Department</label>
                <select id="category" name="category" required>
                    <option value="">Select a department</option>
                    <option <?= $item['category'] === 'Technology' ? 'selected' : '' ?>>Technology</option>
                    <option <?= $item['category'] === 'Fashion' ? 'selected' : '' ?>>Fashion</option>
                    <option <?= $item['category'] === 'Home & Garden' ? 'selected' : '' ?>>Home & Garden</option>
                    <option <?= $item['category'] === 'Sports' ? 'selected' : '' ?>>Sports</option>
                    <option <?= $item['category'] === 'Toys' ? 'selected' : '' ?>>Toys</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Item Description</label>
            <textarea id="description" name="description" rows="4" required maxlength="1000"><?= htmlspecialchars(trim($item['description'])) ?></textarea>
        </div>

        <div class="row">
            <div class="form-group">
                <label for="price">Price (£)</label>
                <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars(number_format($item['price'], 2, '.', '')) ?>" required min="0">
            </div>

            <div class="form-group">
                <label for="postage">Postage Fee (£)</label>
                <input type="number" step="0.01" id="postage" name="postage" value="<?= htmlspecialchars(number_format($item['postage'], 2, '.', '')) ?>" required min="0">
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="button">Save Changes</button>
            <a href="listingPage.php" class="nav-button cancel-button">Cancel</a>
        </div>
    </form>
</div>

    </div>

    <script>
        function validateForm() {
            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const postage = parseFloat(document.getElementById('postage').value);

            if (title === "" || category === "" || description === "") {
                alert("All fields must be filled out.");
                return false;
            }
            if (price < 0 || postage < 0) {
                alert("Price and Postage must be positive numbers.");
                return false;
            }
            if (title.length > 100 || category.length > 50 || description.length > 1000) {
                alert("Input is too long.");
                return false;
            }
            return true;
        }
    </script>

    <div class="footer">
    © 2025-25 iBay Inc. All rights reserved
    </div>
</body>
</html>
