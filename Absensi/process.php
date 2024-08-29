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

$checkInTimeLimit = '09:30:00';
$expectedWorkDuration = 8 * 60 * 60; // 8 hours in seconds

if (isset($_SESSION['uploaded_file'])) {
    $target_file = $_SESSION['uploaded_file'];
    $allowedTypes = ['dat']; 
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    try {
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type. Please upload a .dat file.");
        }

        if (filesize($target_file) > 5000000) { // 5MB limit
            throw new Exception("File size exceeds the allowed limit (5MB).");
        }

        $lines = file($target_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLines = count($lines);

        if ($totalLines > 0) {
            unset($lines[$totalLines - 1]); // Remove the last line
        }

        $processedRecords = 0;
        $errors = [];

        foreach ($lines as $line) {
            $data = explode("\t", $line);

            if (count($data) !== 6) {
                $errors[] = "Invalid line format: $line";
                continue;
            }

            $user_id = $data[0];
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $data[1]);
            $check_type = $data[3];
            $attendance_status = 1;
            $attendance_type = $data[4];

            if ($datetime === false) {
                $errors[] = "Invalid datetime format: " . $data[1];
                continue;
            }

            $formatted_datetime = $datetime->format('Y-m-d H:i:s');
            $date = $datetime->format('Y-m-d');

            handleDuplicates($pdo, $user_id, $date, $check_type);

            $isLate = ($check_type == 0 && $datetime->format('H:i:s') > $checkInTimeLimit) ? 1 : 0;

            // Insert attendance record
            $stmt = $pdo->prepare("INSERT INTO attendance_records 
                (user_id, datetime, attendance_status, check_type, attendance_type, is_late) 
                VALUES (:user_id, :datetime, :attendance_status, :check_type, :attendance_type, :is_late)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':datetime' => $formatted_datetime,
                ':attendance_status' => $attendance_status,
                ':check_type' => $check_type,
                ':attendance_type' => $attendance_type,
                ':is_late' => $isLate
            ]);

            // Early Leave Detection (after check-out)
            if ($check_type == 1) { // If check-out
                $checkInStmt = $pdo->prepare("SELECT datetime FROM attendance_records 
                                              WHERE user_id = :user_id AND DATE(datetime) = :date AND check_type = 0");
                $checkInStmt->execute([':user_id' => $user_id, ':date' => $date]);
                $checkInRecord = $checkInStmt->fetch(PDO::FETCH_ASSOC);

                if ($checkInRecord) {
                    $checkInTime = strtotime($checkInRecord['datetime']);
                    $checkOutTime = strtotime($formatted_datetime);
                    $timeWorked = $checkOutTime - $checkInTime;

                    // Detect if early leave
                    $earlyLeave = ($timeWorked < $expectedWorkDuration) ? 1 : 0;

                    // Update record with early leave status
                    $updateEarlyLeave = $pdo->prepare("UPDATE attendance_records SET early_leave = :early_leave 
                                                       WHERE user_id = :user_id AND check_type = 1 AND DATE(datetime) = :date");
                    $updateEarlyLeave->execute([
                        ':early_leave' => $earlyLeave,
                        ':user_id' => $user_id,
                        ':date' => $date
                    ]);
                }
            }

            $processedRecords++;
        } 

        $response = ['status' => 'success', 'message' => "$processedRecords records processed successfully."];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        echo json_encode($response);

        // Clear the session after processing
        unset($_SESSION['uploaded_file']);
        header("Location: templates/index.php"); 
        exit(); // Terminate script execution after redirection

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    // Handle the case when no file is uploaded or session variable is not set
    echo json_encode(['status' => 'error', 'message' => 'No file to process or invalid request method.']);
}

function handleDuplicates($pdo, $user_id, $date, $check_type) {
    $check_stmt = $pdo->prepare("SELECT * FROM attendance_records 
                                 WHERE user_id = :user_id AND DATE(datetime) = :date");
    $check_stmt->execute([':user_id' => $user_id, ':date' => $date]);
    $results = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 1) {
        if ($check_type == 0) { // Check-in
            usort($results, function($a, $b) {
                return strtotime($a['datetime']) - strtotime($b['datetime']);
            });
            $firstCheckin = $results[0];
            $lastCheckin = end($results);

            $pdo->prepare("UPDATE attendance_records SET check_type = 0 WHERE id = :id")
                ->execute([':id' => $firstCheckin['id']]);

            $pdo->prepare("UPDATE attendance_records SET check_type = 1 WHERE id = :id")
                ->execute([':id' => $lastCheckin['id']]);
        } elseif ($check_type == 1) { // Check-out
            usort($results, function($a, $b) {
                return strtotime($a['datetime']) - strtotime($b['datetime']);
            });
            $firstCheckout = $results[0];
            $lastCheckout = end($results);

            $pdo->prepare("UPDATE attendance_records SET check_type = 1 WHERE id = :id")
                ->execute([':id' => $firstCheckout['id']]);

            $pdo->prepare("UPDATE attendance_records SET check_type = 0 WHERE id = :id")
                ->execute([':id' => $lastCheckout['id']]);
        }
    }
}
?>
