<?php
session_start(); // resume session for login

// DB config
$servername = "sci-project.lboro.ac.uk";
$dbUsername = "295group6";
$dbPassword = "wHiuTatMrdizq3JfNeAH";
$dbName = "295group6";

// connect to DB
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    header("Location: sellerLogin.html?error=server");
    exit();
}

// only allow POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputEmail = trim($_POST['email'] ?? '');
    $inputPassword = trim($_POST['password'] ?? '');

    // basic checks
    if ($inputEmail === '' || $inputPassword === '') {
        header("Location: sellerLogin.html?error=empty");
        exit();
    }

    // validate email format
    if (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
        header("Location: sellerLogin.html?error=invalidemail");
        exit();
    }

    // fetch user from DB
    $stmt = $conn->prepare("SELECT userId, password, name FROM iBayMembers WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        header("Location: sellerLogin.html?error=server");
        exit();
    }

    $stmt->bind_param("s", $inputEmail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hashedPassword, $name);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();

        // verify password match
        if (password_verify($inputPassword, $hashedPassword)) {
            $_SESSION['userId'] = $userId;
            $_SESSION['username'] = $name;
            header("Location: buyerPage.php"); // redirect
            exit();
        } else {
            header("Location: sellerLogin.html?error=invalid");
            exit();
        }
    } else {
        header("Location: sellerLogin.html?error=notfound");
        exit();
    }

    $stmt->close();
}

$conn->close(); // close connection
?>