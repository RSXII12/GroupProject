<?php
// editItem.php
// Ensure PHP uses UK time (BST/GMT)
date_default_timezone_set('Europe/London');
session_start();

// Determine if AJAX JSON
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
} else {
    header('Content-Type: text/html; charset=UTF-8');
}

// Database connection
$servername = "sci-project.lboro.ac.uk";
$username   = "295group6";
$password   = "wHiuTatMrdizq3JfNeAH";
$dbname     = "295group6";

$mysqli = new mysqli($servername, $username, $password, $dbname);
if ($mysqli->connect_error) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Database connection failed.']);
    } else {
        die("DB connection failed: " . $mysqli->connect_error);
    }
    exit;
}

// Force MySQL session to UK local time
$ukOffset = date('P');
if (! $mysqli->query("SET time_zone = '{$ukOffset}'")) {
    error_log("Failed to set MySQL time_zone: " . $mysqli->error);
}

// Identify itemId from GET or POST
if (!isset($_GET['id']) && !isset($_POST['itemId'])) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'No item ID specified.']);
    } else {
        die("No item ID specified.");
    }
    exit;
}
$itemId = $_GET['id'] ?? $_POST['itemId'];

// Fetch existing images
$stmt = $mysqli->prepare("SELECT imageId, image FROM iBayImages WHERE itemId = ? ORDER BY number");
$stmt->bind_param("s", $itemId);
$stmt->execute();
$res = $stmt->get_result();
$existingImages = [];
while ($row = $res->fetch_assoc()) {
    $existingImages[] = $row;
}
$stmt->close();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read & sanitize inputs
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $postage     = trim($_POST['postage'] ?? '');
    $allowedCategories = ['Books','Clothing','Computing','DvDs','Electronics',
                          'Collectables','Home & Garden','Music','Outdoors',
                          'Toys','Sports Equipment'];

    // Validate fields
    if (strlen($title) < 4) {
        $errors[] = "Title must be at least 4 characters.";
    }
    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = "Please select a valid category.";
    }
    if (strlen($description) > 1000) {
        $errors[] = "Description is too long.";
    }
    if (!is_numeric($price) || $price < 0 || $price > 5000) {
        $errors[] = "Price must be between £0 and £5000.";
    }
    if (!is_numeric($postage) || $postage < 0) {
        $errors[] = "Postage must be a non-negative number.";
    }

    // Handle new images with MIME validation
    $newImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        $count = count($_FILES['images']['name']);
        if ($count > 2) {
            $errors[] = "You can only upload up to 2 photos.";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['images']['tmp_name'][$i];
                    $mime    = $finfo->file($tmpName);
                    // Allow only jpeg, png, webp
                    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
                        $errors[] = "Image #".($i+1)." must be JPEG, PNG or WebP.";
                        continue;
                    }
                    $newImages[] = file_get_contents($tmpName);
                } else {
                    $errors[] = "Error uploading image #" . ($i + 1) . ".";
                }
            }
        }
    }

    if (empty($errors)) {
        // Update listing
        $stmt = $mysqli->prepare(
            "UPDATE iBayItems
             SET title=?, category=?, description=?, price=?, postage=?
             WHERE itemId=?"
        );
        $stmt->bind_param("sssdds", $title, $category, $description, $price, $postage, $itemId);
        $ok = $stmt->execute();
        $stmt->close();

        // Replace images if new ones provided
        if ($ok && $newImages) {
            $del = $mysqli->prepare("DELETE FROM iBayImages WHERE itemId=?");
            $del->bind_param("s", $itemId);
            $del->execute(); $del->close();

            $ins = $mysqli->prepare(
                "INSERT INTO iBayImages
                 (imageId,image,itemId,imageSize,number)
                 VALUES (UUID(),?,?,?,?)"
            );
            foreach ($newImages as $idx => $blob) {
                $size = strlen($blob);
                $num  = $idx + 1;
                $ins->bind_param("bsii", $blob, $itemId, $size, $num);
                $ins->send_long_data(0, $blob);
                $ins->execute();
            }
            $ins->close();
        }

        if ($isAjax) {
            echo json_encode(['success' => true]);
        } else {
            header("Location: listingPage.php?success=1");
        }
        exit;
    }

    if ($isAjax) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
}

// On GET, load current item
$stmt = $mysqli->prepare("SELECT * FROM iBayItems WHERE itemId=?");
$stmt->bind_param("s", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Edit Listing – <?= htmlspecialchars($item['title']) ?></title>
  <link rel="stylesheet" href="editItem.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="header">
  <div class="header-left">
    <a href="index.php"><img src="iBay-logo.png" alt="iBay Logo"></a>
  </div>
  <div class="header-center"></div>
</div>

<div class="form-container">
  <!-- Error message div (hidden by default) -->
  <div id="error-message" class="error-message" style="display:none;"></div>
  <form id="edit-form" enctype="multipart/form-data">
    <input type="hidden" name="itemId" value="<?= htmlspecialchars($itemId) ?>">

    <div class="row">
      <div class="form-group">
        <label for="title">Listing Name</label>
        <input type="text" id="title" name="title" required maxlength="100"
               value="<?= htmlspecialchars($item['title']) ?>">
        <div class="error" id="titleError">Title must be at least 4 characters.</div>
      </div>
      <div class="form-group">
        <label for="category">Department</label>
        <select id="category" name="category" required>
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
      <label for="description">Description (max 1000)</label>
      <textarea id="description" name="description" rows="4" maxlength="1000"><?= htmlspecialchars($item['description']) ?></textarea>
      <div id="desc-counter">0 / 1000</div>
    </div>

    <div class="row">
      <div class="form-group">
        <label for="price">Price (£)</label>
        <input type="number" id="price" name="price" step="0.01" min="0" max="5000"
               value="<?= number_format($item['price'],2,'.','') ?>" required>
        <div class="error" id="priceError">Price must be between £0 and £5000.</div>
      </div>
      <div class="form-group">
        <label for="postage">Postage (£)</label>
        <input type="number" id="postage" name="postage" step="0.01" min="0"
               value="<?= number_format($item['postage'],2,'.','') ?>" required>
        <div class="error" id="postageError">Postage must be a non-negative number.</div>
      </div>
    </div>

    <div class="form-group">
      <label>Upload Photos (max 2)</label>
      <input type="file" id="images" name="images[]" accept="image/*" multiple>
      <div class="error" id="imagesError">Please upload up to 2 photos of type JPEG, PNG or WebP.</div>
    </div>

    <?php if ($existingImages): ?>
    <div class="form-group">
      <label>Current Photos</label>
      <div class="photo-preview" id="photo-preview">
        <?php foreach ($existingImages as $img): ?>
          <img src="data:image/jpeg;base64,<?= base64_encode($img['image']) ?>" />
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-buttons">
      <button type="submit">Save Changes</button>
      <a href="listingPage.php" class="cancel-button">Cancel</a>
    </div>
  </form>
</div>

<script>
$(function(){
  $('.error').hide();

  // set current category
  $('#category').val(<?= json_encode($item['category']) ?>);

  // desc counter
  const desc = $('#description'), cnt = $('#desc-counter');
  cnt.text(`${desc.val().length} / 1000`);
  desc.on('input', ()=> cnt.text(`${desc.val().length} / 1000`));

  // Allowed image MIME types
  const ALLOWED_TYPES = ['image/jpeg','image/png','image/webp'];

  // validators
  const validators = {
    title: v => v.length >= 4,
    category: v => v !== '',
    description: v => v.length <= 1000,
    price: v => !isNaN(v) && v > 0 && v <= 5000,
    postage: v => !isNaN(v) && v >= 0,
    images: () => {
      const files = $('#images')[0].files;
      if (files.length < 1 || files.length > 2) return false;
      for (let f of files) {
        if (!ALLOWED_TYPES.includes(f.type)) return false;
      }
      return true;
    }
  };

  // live validation
  $('#title').on('input', ()=> $('#titleError').toggle(!validators.title($('#title').val().trim())));
  $('#category').on('change', ()=> $('#categoryError').toggle(!validators.category($('#category').val())));
  $('#price').on('input', ()=> $('#priceError').toggle(!validators.price($('#price').val().trim())));
  $('#postage').on('input', ()=> $('#postageError').toggle(!validators.postage($('#postage').val().trim())));
  
  $('#images').on('change', function() {
    const preview = $('#photo-preview');
    preview.empty();

    const files = this.files;
    // validate count and type
    if (files.length < 1 || files.length > 2 ||
        Array.from(files).some(f => !ALLOWED_TYPES.includes(f.type))) {
      $('#imagesError').show();
      return;
    } else {
      $('#imagesError').hide();
    }

    // render thumbnails
    Array.from(files).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => $('<img>').attr('src', e.target.result)
                                   .css({ width: '100px', margin: '0 5px' })
                                   .appendTo(preview);
      reader.readAsDataURL(file);
    });
  });

  // form submission via AJAX
  $('#edit-form').on('submit', function(e){
    e.preventDefault();
    let ok = true;
    $.each(validators, (key, fn) => {
      const val = key==='images'? null : $(`#${key}`).val().trim();
      if (!fn(val)) { $(`#${key}Error`).show(); ok = false; }
    });
    if (!ok) return;

    const data = new FormData(this);
    $('#error-message').hide();

    $.ajax({
      url: 'editItem.php?id='+encodeURIComponent(data.get('itemId')),
      type: 'POST', data, processData:false, contentType:false, dataType:'json',
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .done(resp=>{
      if (resp.success) window.location='listingPage.php?success=1';
      else $('#error-message').html(resp.errors.map(e=>`<p>${e}</p>`).join('')).show();
    })
    .fail(()=>$('#error-message').text('Server error.').show());
  });
});
</script>
</body>
</html>
