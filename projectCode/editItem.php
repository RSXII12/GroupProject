<?php
session_start();

// DB connection
$servername = "sci-project.lboro.ac.uk";
$username = "295group6";
$password = "wHiuTatMrdizq3JfNeAH";
$dbname = "295group6";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("No item ID specified.");
}

$itemId = $_GET['id'];
$errors = [];

$imageStmt = $conn->prepare("
  SELECT `imageId`, `image`
    FROM `iBayImages`
   WHERE `itemId` = ?
ORDER BY `number`
");
$imageStmt->bind_param("s", $itemId);
$imageStmt->execute();
$imageRes = $imageStmt->get_result();
$existingImages = [];
while ($row = $imageRes->fetch_assoc()) {
    $existingImages[] = $row;
}
$imageStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $category = htmlspecialchars(trim($_POST['category']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = trim($_POST['price']);
    $postage = trim($_POST['postage']);
    $allowedCategories = ['Books', 'Clothing', 'Computing', 'DvDs', 'Electronics', 'Collectables', 'Home & Garden', 'Music', 'Outdoors', 'Toys', 'Sports Equipment'];
    // Server-side validation
    if (empty($title) || strlen($title) < 4) {
        $errors[] = "Title must be at least 4 characters long.";
    }
    if (empty($category) || !in_array($category, $allowedCategories)) {
        $errors[] = "Please select a valid category.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if (!is_numeric($price) || $price < 0 || $price > 5000) {
        $errors[] = "Price must be between £0 and £5000.";
    }
    if (!is_numeric($postage) || $postage < 0) {
        $errors[] = "Postage must be a valid non-negative number.";
    }
        $newImages = [];
    // if first file input slot isn’t empty, user uploaded something
    if (isset($_FILES['images'])
        && is_array($_FILES['images']['name'])
        && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE
    ) {
    $count = count($_FILES['images']['name']);
        if ($count > 2) {
            $errors[] = "You can only upload up to 2 photos.";
        } else {
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['images']['tmp_name'][$i];
                    $newImages[] = file_get_contents($tmp);
                } else {
                    $errors[] = "Error uploading image #".($i+1);
                }
            }
        }
    }

    if (empty($errors)) {
        $price = floatval($price);
        $postage = floatval($postage);
        $stmt = $conn->prepare(
      "UPDATE `iBayItems`
          SET title=?, category=?, description=?, price=?, postage=?
        WHERE itemId=?"
            );
            $stmt->bind_param("sssdds", $title, $category, $description, $price, $postage, $itemId);
            if ($stmt->execute()) {
                // Only now, if new images were uploaded, replace them
                if (!empty($newImages)) {
                    //  delete old
                    $del = $conn->prepare(
                    "DELETE FROM `iBayImages`
                        WHERE `itemId` = ?"
                    );
                    $del->bind_param("s", $itemId);
                    $del->execute();
                    $del->close();

                    //  insert new
                    $ins = $conn->prepare(
                    "INSERT INTO `iBayImages`
                        (`imageId`, `image`, `itemId`, `imageSize`, `number`)
                    VALUES (UUID(), ?, ?, ?, ?)"
                    );
                    foreach ($newImages as $idx => $blob) {
                        $size = strlen($blob);
                        $num  = $idx + 1;
                        // correct binding: blob, itemId, size, number
                        $ins->bind_param("bsii", $blob, $itemId, $size, $num);
                        $ins->send_long_data(0, $blob);
                        if (! $ins->execute()) {
                            error_log("Image insert failed for item $itemId: " . $ins->error);
                        }
                    }
                    $ins->close();
                }

                // now—and only now—redirect back
                header("Location: listingPage.php?success=1");
                exit;
            } else {
                $errors[] = "Error updating item: " . $stmt->error;
            }
            $stmt->close();
            }
        }

$stmt = $conn->prepare("SELECT * FROM iBayItems WHERE itemId = ?");
$stmt->bind_param("s", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
if (!$item) die("Item not found.");
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Listing - <?= htmlspecialchars($item['title']) ?></title>
    <link rel="stylesheet" href="editItem.css" />
</head>
<body>
    <div class="header">
        <div class="header-left"><a href="index.php"><img src="iBay-logo.png" alt="iBay Logo"></a></div>
        <div class="header-center">Edit Listing</div>
        <div class="header-right"></div>
    </div>
    <div class="form-container">
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" id="edit-form" enctype="multipart/form-data">
            <div class="row">
                <div class="form-group">
                    <label for="title">Listing Name</label>
                    <input type="text" id="title" name="title" required maxlength="100" value="<?= htmlspecialchars($item['title']) ?>" />
                    <div class="error" id="titleError">Title must be at least 4 characters.</div>
                </div>
                <div class="form-group">
                    <label for="category">Department</label>
                    <select
                    id="category"
                    name="category"
                    required
                    data-current="<?= htmlspecialchars($item['category']) ?>"
                    >
                    <option value="">Select a department</option>
                        <option>Books</option>
                        <option>Clothing</option>
                        <option>Computing</option>
                        <option>DvDs</option>
                        <option>Electronics</option>
                        <option>Collectables</option>
                        <option>Home & Garden</option>
                        <option>Music</option>
                        <option>Outdoors</option>
                        <option>Toys</option>
                        <option>Sports Equipment</option>
                    </select>
                    <div class="error" id="categoryError">Please select a department.</div>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Item Description (max 1000 chars)</label>
                <textarea id="description" name="description" rows="4" maxlength="1000"><?= htmlspecialchars(trim($item['description'])) ?></textarea>
                <div id="desc-counter">0 / 1000</div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="price">Price (£)</label>
                    <input type="number" id="price" name="price" step="0.01" required min="0" max="5000" value="<?= htmlspecialchars(number_format($item['price'],2,'.','')) ?>" />
                    <div class="error" id="priceError">Price must be between £0 and £5000.</div>
                </div>
                <div class="form-group">
                    <label for="postage">Postage Fee (£)</label>
                    <input type="number" id="postage" name="postage" step="0.01" required min="0" value="<?= htmlspecialchars(number_format($item['postage'],2,'.','')) ?>" />
                    <div class="error" id="postageError">Please enter a valid postage fee.</div>
                </div>
            </div>
            <div class="form-group">
            <label for="images">Photos (max 2)</label>
            <input
                type="file"
                id="images"
                name="images[]"
                accept="image/*"
                multiple
            >
            <div class="photo-preview" id="photo-preview" style="maxwidth:100px; maxheight:100px; object-fit: cover;">
            

            <?php if (!empty($existingImages)): ?>
            <div class="form-group">
                <label>Current Photos</label>
                <div class="photo-preview">
                <?php foreach ($existingImages as $img): ?>
                    <img
                    src="data:image/jpeg;base64,<?= base64_encode($img['image']) ?>"
                    />
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
                </div>
            </div>
            <div id="lightbox-overlay" class="hidden">
            <span id="lightbox-close">&times;</span>
            <img id="lightbox-img" src="" alt="Enlarged photo">
            </div>        

            <div class="form-buttons">
                <button type="submit" class="button">Save Changes</button>
                <a href="listingPage.php" class="nav-button cancel-button">Cancel</a>
            </div>
        </form>
    </div>
    <script>
        // Character counter
        const descInput = document.getElementById('description');
        const descCounter = document.getElementById('desc-counter');
        descInput.addEventListener('input', () => {
            descCounter.textContent = `${descInput.value.length} / 1000`;
        });

        // Validation setup
        const form = document.getElementById('edit-form');
        const fields = {
            'title': {
                el: document.getElementById('title'),
                error: document.getElementById('titleError'),
                validate: val => val.length >= 4
            },
            'category': {
                el: document.getElementById('category'),
                error: document.getElementById('categoryError'),
                validate: val => val !== ''
            },
            'description': {
                el: document.getElementById('description'),
                error: null,
                validate: val => val.length > 0
            },
            'price': {
                el: document.getElementById('price'),
                error: document.getElementById('priceError'),
                validate: val => !isNaN(val) && val > 0 && val <= 5000
            },
            'postage': {
                el: document.getElementById('postage'),
                error: document.getElementById('postageError'),
                validate: val => !isNaN(val) && val >= 0
            }
        };

        Object.values(fields).forEach(field => {
            if (!field.error) return;
            field.el.addEventListener('input', () => {
                const val = field.el.value.trim();
                if (field.validate(val)) {
                    field.error.style.display = 'none';
                } else {
                    field.error.style.display = 'block';
                }
            });
        });

        form.addEventListener('submit', (e) => {
            let valid = true;
            Object.values(fields).forEach(field => {
                const val = field.el.value.trim();
                if (!field.validate(val)) {
                    if (field.error) field.error.style.display = 'block';
                    valid = false;
                }
            });
            if (!valid) e.preventDefault();
        });

        // Run validation on page load
        window.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.getElementById('category');
            const current = categorySelect.dataset.current; // from data-current attr
            if (current) {
            categorySelect.value = current;
            }
            // initialize description counter
            descCounter.textContent = `${descInput.value.length} / 1000`;
            // initial field validation
            Object.values(fields).forEach(field => {
                const val = field.el.value.trim();
                if (field.error) {
                    if (!field.validate(val)) {
                        field.error.style.display = 'block';
                    } else {
                        field.error.style.display = 'none';
                    }
                }
            });
        });
    const imgInput = document.getElementById('images');
    const preview  = document.getElementById('photo-preview');

    imgInput.addEventListener('change', () => {
    preview.innerHTML = '';
    const files = Array.from(imgInput.files);
    if (files.length > 2) {
        alert('You can only upload a maximum of 2 photos.');
        imgInput.value = '';
        return;
    }
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
        const img = document.createElement('img');
        img.src = e.target.result;
        preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
    });

    document.addEventListener('DOMContentLoaded', () => {
    
  // grab all thumbnail imgs inside .photo-preview
  const thumbs = document.querySelectorAll('.photo-preview img');
  const overlay = document.getElementById('lightbox-overlay');
  const lightboxImg = document.getElementById('lightbox-img');
  const closeBtn = document.getElementById('lightbox-close');

  thumbs.forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', () => {
      lightboxImg.src = img.src;
      overlay.classList.remove('hidden');
    });
  });

  // close when clicking the X or outside the image
  closeBtn.addEventListener('click', () => overlay.classList.add('hidden'));
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.add('hidden');
  });

  // optional: Esc key to close
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') overlay.classList.add('hidden');
  });
});
    </script>
</body>
</html>