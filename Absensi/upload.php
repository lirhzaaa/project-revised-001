<?php
require_once 'config.php';
session_start(); // Start the session

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['attendanceFile'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["attendanceFile"]["name"]);
    $allowedTypes = ['dat']; 
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    try {
        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type. Please upload a .dat file.");
        }

        // Validate file size (adjust as needed)
        if ($_FILES["attendanceFile"]["size"] > 50000000) { // 50MB limit
            throw new Exception("File size exceeds the allowed limit (50MB).");
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES["attendanceFile"]["tmp_name"], $target_file)) {
            // Store the uploaded file path in session
            $_SESSION['uploaded_file'] = $target_file;
            header("Location: process.php");
            exit();
        } else {
            throw new Exception("Sorry, there was an error uploading your file.");
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or no file uploaded']);
}
?>
