<?php
require_once 'config.php'; 

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch users with optional filters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';

$query = 'SELECT user_id, full_name FROM users WHERE 1=1';
$params = [];

if ($user_id !== '') {
    $query .= ' AND user_id LIKE :user_id';
    $params[':user_id'] = "%$user_id%";
}

if ($name !== '') {
    $query .= ' AND full_name LIKE :name';
    $params[':name'] = "%$name%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
?>
