<?php
session_start();

$servername = "localhost"; 
$dbUsername = "root"; 
$dbPassword = "piepie665"; 
$dbName = "teamdatabase"; 

// Connect to MySQL
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password FROM user WHERE username = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hashedPassword);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        // Verify the hashed password
        if (password_verify($inputPassword, $hashedPassword)) {
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $inputUsername;
            
            // Redirect to seller page
            header("Location: sellerPage.php");
            exit();
        } else {
            echo "<script>alert('Invalid password!'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found!'); window.location.href='login.html';</script>";
    }
    $stmt->close();
}
$conn->close();
?>
