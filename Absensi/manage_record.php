<?php

require_once 'config.php'; 

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        exit();
    }

    $action = $data['action'] ?? '';
    $id = $data['id'] ?? null;
    $user_id = $data['user_id'] ?? '';
    $datetime = $data['datetime'] ?? '';
    $check_type = $data['check_type'] ?? null;
    $attendance_status = $data['attendance_status'] ?? '';

    // Handle the 'create' action
    if ($action == 'create') {
        if ($user_id && $datetime && $attendance_status !== '' && ($check_type === 0 || $check_type === 1)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO attendance_records (user_id, datetime, attendance_status, check_type) VALUES (:user_id, :datetime, :attendance_status, :check_type)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':datetime', $datetime);
                $stmt->bindParam(':attendance_status', $attendance_status);
                $stmt->bindParam(':check_type', $check_type);
                $stmt->execute();

                echo json_encode(['status' => 'success', 'message' => 'Record created successfully']);
                exit();
            } catch (PDOException $e) {
                error_log("Insertion failed: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Insertion failed: ' . $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided']);
            exit();
        }
    }

    // Handle the 'edit' action
    if ($action == 'edit') {
        $check_type = (int)$check_type;

        if ($id && $user_id && $attendance_status !== '' && ($check_type === 0 || $check_type === 1)) {
            try {
                if ($check_type === 0) {
                    // Update check-in time
                    $stmt = $pdo->prepare("UPDATE attendance_records SET user_id = :user_id, datetime = :datetime, attendance_status = :attendance_status WHERE id = :id");
                    $stmt->bindParam(':user_id', $user_id);
                } elseif ($check_type === 1) {
                    // Update check-out time
                    $stmt = $pdo->prepare("UPDATE attendance_records SET datetime = :datetime, attendance_status = :attendance_status WHERE id = :id");
                }
                
                $stmt->bindParam(':datetime', $datetime);
                $stmt->bindParam(':attendance_status', $attendance_status);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                
                echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
                exit();
                
            } catch (PDOException $e) {
                error_log("Update failed: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided']);
            exit();
        }
    }

    // Handle the 'delete' action
    elseif ($action == 'delete') {
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
                exit();
            } catch (PDOException $e) {
                error_log("Deletion failed: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit();
        }

    }

    // Handle the 'delete_all' action
    elseif ($action == 'delete_all') {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance_records");
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'All records deleted successfully']);
            exit();
        } catch (PDOException $e) {
            error_log("Deletion failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
            exit();
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
            exit();
        } catch (PDOException $e) {
            error_log("Deletion failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}
