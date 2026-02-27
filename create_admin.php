<?php
// Load Database Config
require_once 'config/database.php';

// Connect to Database
$database = new Database();
$db = $database->getConnection();

// Admin Credentials
$username = "admin";
$password = "admin"; 
$email = "admin@kkkledshop.com"; // Required by DB schema

// Security: Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // 1. Check if user already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $checkStmt->bindParam(":username", $username);
    $checkStmt->bindParam(":email", $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        echo "<h2 style='color:red'>Error: User already exists!</h2>";
        echo "A user with this username or email is already in the database.";
    } else {
        // 2. Insert the new Admin
        $sql = "INSERT INTO users (username, email, password, role, is_verified) 
                VALUES (:username, :email, :password, 'admin', 1)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);

        if ($stmt->execute()) {
            echo "<h2 style='color:green'>Success! Admin Account Created.</h2>";
            echo "<strong>Username:</strong> admin<br>";
            echo "<strong>Email:</strong> admin@kkkledshop.com<br>";
            echo "<strong>Password:</strong> admin<br>";
            echo "<br><a href='/login'>Go to Login Page</a>";
        } else {
            echo "Failed to insert user.";
        }
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>