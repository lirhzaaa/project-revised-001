<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (isset($_GET['file'])) {
    $file = urldecode($_GET['file']);
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read all lines, ignoring empty lines
    $totalLines = count($lines);

    if ($totalLines > 0) {
        unset($lines[$totalLines - 1]); // Remove the last line
    }

    foreach ($lines as $line) {
        $data = explode("\t", $line);

        // Validate data: Exactly 6 fields
        if (count($data) !== 6) {
            error_log("Invalid line in file: $line");
            continue;
        }

        $user_id = $data[0];
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $data[1]);
        $check_type = $data[3]; // Use check_type from the fourth field (0: check-in, 1: check-out)
        // Ignore $data[2], $data[4], and $data[5]

        if ($datetime === false) {
            error_log("Invalid datetime format: " . $data[1]);
            continue;
        }

        $formatted_datetime = $datetime->format('Y-m-d H:i:s');

        // Check for duplicates and auto-correct if necessary
        $check_stmt = $pdo->prepare("SELECT * FROM attendance_records 
                                    WHERE user_id = :user_id AND DATE(datetime) = :date");
        $date = $datetime->format('Y-m-d');
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':date', $date);
        $check_stmt->execute();
        $existingRecords = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($existingRecords) > 0) {
            // Find earliest check-in and latest check-out, delete others
            $earliestCheckIn = null;
            $latestCheckOut = null;

            foreach ($existingRecords as $record) {
                if ($record['check_type'] == 0 && ($earliestCheckIn === null || $record['datetime'] < $earliestCheckIn)) {
                    $earliestCheckIn = $record['datetime'];
                } elseif ($record['check_type'] == 1 && ($latestCheckOut === null || $record['datetime'] > $latestCheckOut)) {
                    $latestCheckOut = $record['datetime'];
                }
            }

            // Delete records that are not earliest check-in or latest check-out
            foreach ($existingRecords as $record) {
                if ($record['datetime'] != $earliestCheckIn && $record['datetime'] != $latestCheckOut) {
                    $delete_stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = :id");
                    $delete_stmt->bindParam(':id', $record['id']);
                    $delete_stmt->execute();
                }
            }
        }

        // Check if the record is late (Only for check-ins, i.e., check_type = 0)
        $isLate = ($check_type == 0 && $datetime->format('H:i:s') > $checkInTimeLimit) ? 1 : 0;

        // Insert record
        $stmt = $pdo->prepare("INSERT INTO attendance_records (user_id, datetime, attendance_status, check_type, is_late) 
                                VALUES (:user_id, :datetime, 1, :check_type, 1, :is_late)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':datetime', $formatted_datetime);
        $stmt->bindParam(':check_type', $check_type);
        $stmt->bindParam(':is_late', $isLate);

        $stmt->execute();
    }

    // Redirect or provide feedback to the user
    header("Location: templates/index.php"); // Replace with your success page
    exit();
} else {
    // Handle the case when no file is uploaded
    echo "No file uploaded."; // Or redirect to a relevant error page
}