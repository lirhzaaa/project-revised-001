<?php
require_once 'config.php'; 

header('Content-Type: application/json');

// Establish Database Connection
try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

function logAbsentees($pdo) {
    // Assume working days are Monday to Friday
    $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    // Get all users
    $users = $pdo->query("SELECT DISTINCT user_id FROM users")->fetchAll(PDO::FETCH_ASSOC);

    // Iterate over each user
    foreach ($users as $user) {
        $userId = $user['user_id'];

        // Get all dates where the user has entries
        $entries = $pdo->prepare("SELECT DISTINCT DATE(datetime) as date FROM attendance_records WHERE user_id = :user_id");
        $entries->execute(['user_id' => $userId]);
        $userDates = $entries->fetchAll(PDO::FETCH_COLUMN);

        // Get the current month dates
        $firstDayOfMonth = new DateTime('first day of this month');
        $lastDayOfMonth = new DateTime('last day of this month');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($firstDayOfMonth, $interval, $lastDayOfMonth);

        foreach ($period as $date) {
            if (in_array($date->format('l'), $workingDays)) {
                // Check for both check-in AND check-out on this date
                $hasCheckIn = $pdo->prepare("SELECT COUNT(*) FROM attendance_records WHERE user_id = :user_id AND DATE(datetime) = :date AND check_type = 1"); // Check-in
                $hasCheckIn->execute(['user_id' => $userId, 'date' => $date->format('Y-m-d')]);
                $hasCheckOut = $pdo->prepare("SELECT COUNT(*) FROM attendance_records WHERE user_id = :user_id AND DATE(datetime) = :date AND check_type = 0"); // Check-out
                $hasCheckOut->execute(['user_id' => $userId, 'date' => $date->format('Y-m-d')]);
        
                if ($hasCheckIn->fetchColumn() == 0 || $hasCheckOut->fetchColumn() == 0) {
                    // Log the user as absent if either check-in or check-out is missing
                    $stmt = $pdo->prepare("INSERT INTO attendance_records (user_id, datetime, attendance_status, check_type) VALUES (:user_id, :datetime, '0', '1')");
                    $stmt->execute([
                        'user_id' => $userId,
                        'datetime' => $date->format('Y-m-d 09:00:00') // Assuming 9 AM for absentees
                    ]);
                }
            }
        }
    }
}

// Call the function to log absentees
logAbsentees($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    if ($action == 'edit') {
        $user_id = $_POST['user_id'];
        $datetime = $_POST['datetime'];
        $attendance_status = $_POST['attendance_status']; // Capture attendance_status
        $check_type = $_POST['check_type'];

        try {
            // Prepare and execute the update statement
            $stmt = $pdo->prepare("UPDATE attendance_records SET user_id = :user_id, datetime = :datetime, attendance_status = :attendance_status, check_type = :check_type WHERE id = :id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':datetime', $datetime);
            $stmt->bindParam(':attendance_status', $attendance_status); // Bind attendance_status
            $stmt->bindParam(':check_type', $check_type);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    } elseif ($action == 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Deletion failed: ' . $e->getMessage()]);
        }
    } elseif ($action == 'delete_all') {
        try {
            $query = "DELETE FROM attendance_records";
            $stmt = $pdo->prepare($query);
            $stmt->execute();

            // Reset auto-increment value
            $pdo->exec("ALTER TABLE attendance_records AUTO_INCREMENT = 1");

            echo json_encode(['status' => 'success', 'message' => 'Records deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Deletion of all records failed: ' . $e->getMessage()]);
        }
    } elseif ($action == 'cleanup_duplicates') {
        try {
            $stmt = $pdo->prepare("DELETE ar1 FROM attendance_records ar1
                                   INNER JOIN attendance_records ar2
                                   WHERE ar1.id < ar2.id
                                   AND ar1.user_id = ar2.user_id
                                   AND DATE(ar1.datetime) = DATE(ar2.datetime)
                                   AND ar1.attendance_status = ar2.attendance_status");
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Duplicate records cleaned up successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Cleanup duplicates failed: ' . $e->getMessage()]);
        }
    } elseif ($action == 'auto_correct') {
        try {
            // Get all records grouped by user_id and date
            $stmt = $pdo->query("SELECT user_id, DATE(datetime) as date, GROUP_CONCAT(id ORDER BY datetime) as ids, GROUP_CONCAT(check_type ORDER BY datetime) as check_types
                                 FROM attendance_records
                                 GROUP BY user_id, DATE(datetime)");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($records as $record) {
                $ids = explode(',', $record['ids']);
                $check_types = explode(',', $record['check_types']);

                // Correct double check-ins or check-outs
                if (count(array_unique($check_types)) == 1) {
                    if ($check_types[0] == '0') { // All are check-ins
                        $stmt = $pdo->prepare("UPDATE attendance_records SET check_type = 1 WHERE id = ?");
                        $stmt->execute([$ids[count($ids) - 1]]); // Last one is check-out
                    } elseif ($check_types[0] == '1') { // All are check-outs
                        $stmt = $pdo->prepare("UPDATE attendance_records SET check_type = 0 WHERE id = ?");
                        $stmt->execute([$ids[0]]); // First one is check-in
                    }
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Auto-correction process completed successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Auto-correction process failed: ' . $e->getMessage()]);
        }
    } elseif ($action == 'manage_users') {
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

        if ($user_id !== null) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                echo json_encode(['status' => 'success', 'message' => 'User removed successfully']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'User removal failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        }
    } elseif ($action == 'delete_all_users') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users");
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'All users deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Deletion of all users failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    exit();
}
?>
