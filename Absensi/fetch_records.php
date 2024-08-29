<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get filter parameters
$id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$attendance_status = isset($_GET['attendance_status']) ? $_GET['attendance_status'] : null;
$name = isset($_GET['name']) ? $_GET['name'] : null;
$is_late = isset($_GET['is_late']) ? $_GET['is_late'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Initialize base query
$query = 'SELECT ar.*, u.full_name FROM attendance_records ar 
          JOIN users u ON ar.user_id = u.user_id WHERE 1=1';
$params = [];

// Apply filters only if they are set (triggered by user input)
$filtersApplied = false;

if ($id !== null && $id !== '') {
    $query .= ' AND ar.id = :id';
    $params[':id'] = $id;
    $filtersApplied = true;
}

if ($user_id !== null && $user_id !== '') {
    $query .= ' AND ar.user_id LIKE :user_id';
    $params[':user_id'] = '%' . $user_id . '%';
    $filtersApplied = true;
}

if ($attendance_status !== null && $attendance_status !== '') {
    $query .= ' AND ar.attendance_status = :attendance_status';
    $params[':attendance_status'] = $attendance_status;
    $filtersApplied = true;
}

if ($name !== null && $name !== '') {
    $query .= ' AND u.full_name LIKE :name';
    $params[':name'] = '%' . $name . '%';
    $filtersApplied = true;
}

if ($is_late !== null && $is_late !== '') {
    $query .= ' AND ar.is_late = :is_late';
    $params[':is_late'] = $is_late;
    $filtersApplied = true;
}

// Apply date range filters only if specified
if ($start_date !== null && $end_date !== null) {
    $query .= ' AND ar.datetime BETWEEN :start_date AND :end_date';
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
    $filtersApplied = true;
} elseif ($start_date !== null) {
    $query .= ' AND ar.datetime >= :start_date';
    $params[':start_date'] = $start_date;
    $filtersApplied = true;
} elseif ($end_date !== null) {
    $query .= ' AND ar.datetime <= :end_date';
    $params[':end_date'] = $end_date;
    $filtersApplied = true;
}

// If no filters are applied, default to fetching all records
if (!$filtersApplied) {
    $query = 'SELECT ar.*, u.full_name FROM attendance_records ar 
              JOIN users u ON ar.user_id = u.user_id';
}

$stmt = $pdo->prepare($query);

// Execute query and check for errors
if (!$stmt->execute($params)) {
    http_response_code(500); 
    echo json_encode(["error" => "An error occurred while fetching data."]);
    exit();
}

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map attendance status and late/early leave status
$attendanceStatusMapping = [
    '0' => 'Absent', 
    '1' => 'Present', 
    '2' => 'Izin',
    '3' => 'Sakit',
    '4' => 'Cuti',
    '5' => 'Belum Checkout',
    '6' => 'Belum Checkin'
];

foreach ($records as &$record) {
    $record['attendance_status_text'] = $attendanceStatusMapping[$record['attendance_status']] ?? 'Unknown';
    
    // Combine late and early leave status into a single field
    $lateStatus = $record['is_late'] ? 'Late' : 'On Time';
    $earlyLeaveStatus = isset($record['early_leave']) && $record['early_leave'] == 1 ? 'Early Leave' : '-';
    
    // If no check-out data, only show check-in late status
    if ($record['check_type'] == 0 && !isset($record['early_leave'])) {
        $record['late_early_status'] = $lateStatus . ' / -';
    } else {
        $record['late_early_status'] = $lateStatus . ' / ' . $earlyLeaveStatus;
    }
}

// Send back the data as JSON
header('Content-Type: application/json');
echo json_encode($records);
?>
