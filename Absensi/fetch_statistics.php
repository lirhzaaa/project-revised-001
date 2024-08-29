<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if ($user_id !== null) {
    // Fetch user-specific statistics
    $stmt = $pdo->prepare('SELECT user_id, full_name FROM users WHERE user_id = :user_id');
    $stmt->bindParam(':user_id', $user_id);
    if (!$stmt->execute()) {
        die(json_encode(['status' => 'error', 'message' => 'Error fetching user data']));
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $query = "SELECT ar.* FROM attendance_records ar WHERE user_id = $user_id";

        $stmt = $pdo->prepare($query);
        $stmt->execute(); // Execute the prepared statement

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'user' => $user, 'attendance' => $records]);
    //     $stmt = $pdo->prepare('
    //     SELECT 
    //         a1.user_id,
    //         u.full_name,
    //         a1.datetime as check_in_time,
    //         a2.datetime as check_out_time,
    //         a1.attendance_status as check_in_status,
    //         a2.attendance_status as check_out_status,
    //         CASE 
    //             WHEN TIME(a1.datetime) > "09:45:00" THEN 1 
    //             ELSE 0 
    //         END AS late_check_in,
    //         CASE 
    //             WHEN TIME(a2.datetime) > "17:00:00" THEN 1 
    //             ELSE 0 
    //         END AS late_check_out
    //     FROM attendance_records a1
    //     LEFT JOIN attendance_records a2 
    //         ON a1.user_id = a2.user_id 
    //         AND a2.check_type = "Check Out"
    //     INNER JOIN users u 
    //         ON a1.user_id = u.user_id
    //     WHERE a1.user_id = :user_id 
    //     AND a1.check_type = "Check In"
    // ');
    // $stmt = $pdo->prepare(``);

    // $stmt->bindParam(':user_id', $user_id);
    // if (!$stmt->execute()) {
    //     die(json_encode(['status' => 'error', 'message' => 'Error fetching attendance records']));
    // }
    // $attendance_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    //     header('Content-Type: application/json');
    //     echo json_encode(['status' => 'success', 'user' => $user, 'attendance' => $attendance_status]); 
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}else {
    // Fetch general statistics
    try {
        $totalRecordsStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records');
        $totalRecords = $totalRecordsStmt->fetchColumn();

        $presentStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE attendance_status = "Present"');
        $present = $presentStmt->fetchColumn();

        $izinStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE attendance_status = "Izin"');
        $izin = $izinStmt->fetchColumn();

        $sakitStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE attendance_status = "Sakit"');
        $sakit = $sakitStmt->fetchColumn();

        $CutiStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE attendance_status = "Cuti"');
        $Cuti = $CutiStmt->fetchColumn();

        $lateStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE TIME(datetime) > "09:45:00"');
        $late = $lateStmt->fetchColumn();

        $onTimeStmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE TIME(datetime) <= "09:45:00"');
        $onTime = $onTimeStmt->fetchColumn();

        $late15to30Stmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE TIME(datetime) BETWEEN "09:45:01" AND "10:00:00"');
        $late15to30 = $late15to30Stmt->fetchColumn();

        $late30to60Stmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE TIME(datetime) BETWEEN "10:00:01" AND "10:30:00"');
        $late30to60 = $late30to60Stmt->fetchColumn();

        $late60Stmt = $pdo->query('SELECT COUNT(*) FROM attendance_records WHERE TIME(datetime) > "10:30:00"');
        $late60 = $late60Stmt->fetchColumn();

        $dateWiseStmt = $pdo->query('
            SELECT DATE(datetime) as date, 
                   SUM(CASE WHEN TIME(datetime) > "09:45:00" THEN 1 ELSE 0 END) as late_count, 
                   SUM(CASE WHEN TIME(datetime) <= "09:45:00" THEN 1 ELSE 0 END) as on_time_count
            FROM attendance_records 
            GROUP BY DATE(datetime)
            ORDER BY DATE(datetime)
        ');
        $dateWiseStats = $dateWiseStmt->fetchAll(PDO::FETCH_ASSOC);

        $statistics = [
            'total_records' => $totalRecords,
            'present' => $present,
            'izin' => $izin,
            'sakit' => $sakit,
            'Cuti' => $Cuti,
            'late' => $late,
            'on_time' => $onTime,
            'late_15_30' => $late15to30,
            'late_30_60' => $late30to60,
            'late_60' => $late60,
            'date_wise' => $dateWiseStats
        ];

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'statistics' => $statistics]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching statistics: ' . $e->getMessage()]);
    }
}
?>
