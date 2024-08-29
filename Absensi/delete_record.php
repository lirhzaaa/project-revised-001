<?php
header('Content-Type: application/json');
$response = array('success' => false);

// Assuming you have some database connection setup here
// $conn = new mysqli('host', 'user', 'password', 'database');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];

    // Example query to delete a record
    // $result = $conn->query("DELETE FROM attendance_status WHERE id = $id");

    // For demonstration purposes, we'll assume the deletion was successful
    $response['success'] = true;
}

echo json_encode($response);
?>
