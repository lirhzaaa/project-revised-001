<?php
$host = 'localhost';
$dbname = 'attendance_db'; // Replace with your actual database name
$username = 'root'; 
$password = '';  

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $fullName = $_POST['full_name'];

    // Data Validation (Add as needed)
    // Check if user_id already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        die("User ID already exists!"); 
    }

    // Sanitize input to prevent SQL injection (use prepared statements)
    $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name) VALUES (:user_id, :full_name)");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':full_name', $fullName);

    try {
        $stmt->execute();
        echo "User added successfully"; 
        header('Location: templates/manage_users.html'); // Redirect back to manage_users
        exit(); // Ensure the script stops after redirection
    } catch (PDOException $e) {
        die("Error adding user: " . $e->getMessage());
    }
}
?>
