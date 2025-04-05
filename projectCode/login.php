<?php
session_start();

$servername = "sci-project.lboro.ac.uk"; 
$dbUsername = "295group6"; 
$dbPassword = "wHiuTatMrdizq3JfNeAH"; 
$dbName = "295group6"; 

// Connect to MySQL database 
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
    $stmt = $conn->prepare("SELECT userId, password FROM iBayMembers WHERE name = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hashedPassword);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        // Verify the hashed password
        if (password_verify($inputPassword, $hashedPassword)) {
            // Set session variables
            $_SESSION['userId'] = $userId;
            $_SESSION['username'] = $inputUsername;
            
            // Redirect to seller page
            header("Location: sellerPage.html");
            exit();
        } else {
            $temp = password_hash($inputPassword, PASSWORD_DEFAULT);
            echo "<script>alert('Invalid password! password:$inputPassword,hash:$hashedPassword,hashedPassword:$temp '); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found!'); window.location.href='login.html';</script>";
    }
    $stmt->close();
}
$conn->close();
?>
