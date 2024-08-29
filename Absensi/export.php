<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

if (ob_get_length()) {
    ob_end_clean();
}

try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$firstDay = "{$year}-{$month}-01";
$lastDay = date("Y-m-t", strtotime($firstDay));

$stmt = $pdo->prepare(trim("
    SELECT u.user_id, u.full_name, ar.datetime, ar.attendance_status, ar.check_type
    FROM users u
    LEFT JOIN attendance_records ar 
    ON u.user_id = ar.user_id AND ar.datetime BETWEEN :firstDay AND :lastDay
    ORDER BY u.user_id, ar.datetime
"));
$stmt->execute(['firstDay' => $firstDay, 'lastDay' => $lastDay]);
$records = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Styling enhancements
$headerFillColor = 'FFD9E1F2'; // Light blue 
$summaryRowFillColor = 'FFF2F2F2'; // Light gray
$borderColor = 'FFCCCCCC'; // Light gray border
$titleFillColor = 'FFC0C0C0'; // Silver

// Set title and subtitle
$sheet->setCellValue('A1', date('F', strtotime($firstDay)) . ' Monthly Attendance Report');
$sheet->setCellValue('A2', 'PT Prio Integritas Universal');
// Merge cells for the title and company name
$sheet->mergeCells('A1:C2');
$sheet->getStyle('A1:C2')->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER, 
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'font' => [
        'bold' => true,
        'size' => 16
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => $titleFillColor]
    ]
]);

$sheet->getRowDimension(1)->setRowHeight(30); // Adjust the height as needed

// Set the header rows (start from row 4)
$sheet->setCellValue('A4', 'No.')
      ->setCellValue('B4', 'User ID')
      ->setCellValue('C4', 'Name')
      ->setCellValue('D4', 'Present')
      ->setCellValue('E4', 'Izin')
      ->setCellValue('F4', 'Sakit')
      ->setCellValue('G4', 'Cuti')
      ->setCellValue('H4', 'Late')          // Add new header for Late count
      ->setCellValue('I4', 'Early Leave');  // Add new header for Early Leave count

// Merge cell for the month display and set date headers dynamically
$currentColumn = 'J';
for ($date = 1; $date <= date('t', strtotime($firstDay)); $date++) {
    $dateHeader = $date . '-' . $month . '-' . $year;
    $sheet->setCellValue($currentColumn . '4', $dateHeader);
    $currentColumn++;
}
$lastColumn = $sheet->getHighestColumn();
$sheet->mergeCells('J3:' . $lastColumn . '3');
$sheet->setCellValue('J3', date('F Y', strtotime($firstDay))); 
$sheet->getStyle('J3:' . $lastColumn . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('J3:' . $lastColumn . '3')->getFont()->setBold(true);

$row = 5; // Start data from row 5
$no = 1;

foreach ($records as $user_id => $userRecords) {
    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $user_id);
    $sheet->setCellValue('C' . $row, $userRecords[0]['full_name'] ?? '');

    // Initialize summary counts
    $summary = [
        'present' => 0, 
        'izin' => 0, 
        'sakit' => 0, 
        'cuti' => 0,
        'late' => 0,
        'early_leave' => 0,
        'missed_checkin' => 0,
        'missed_checkout' => 0
    ];

    $currentColumn = 'J'; // Start from column J for attendance data (after summary columns)

    for ($date = 1; $date <= date('t', strtotime($firstDay)); $date++) {
        $dateString = "{$year}-{$month}-" . str_pad($date, 2, '0', STR_PAD_LEFT);
        $checkinData = '-';
        $checkoutData = '-';
        $hasCheckIn = false;
        $hasCheckOut = false;

        foreach ($userRecords as $record) {
            if (strpos($record['datetime'], $dateString) !== false) {
                if ($record['check_type'] == 0) { // Check-in
                    $checkinData = getAttendanceSymbol($record['attendance_status']);
                    $hasCheckIn = true;
        
                    // Only increment 'present' count if check-in status is '1' (Present)
                    if ($record['attendance_status'] == '1') {
                        $summary['present']++;
                    } elseif ($record['attendance_status'] == '2') {
                        $summary['izin']++;
                    } elseif ($record['attendance_status'] == '3') {
                        $summary['sakit']++;
                    } elseif ($record['attendance_status'] == '4') {
                        $summary['cuti']++;
                    }
        
                    // Check if check-in is late
                    if (isset($record['is_late']) && $record['is_late'] == 1) {
                        $summary['late']++;
                    }
                } elseif ($record['check_type'] == 1) { // Check-out
                    $checkoutData = getAttendanceSymbol($record['attendance_status']);
                    $hasCheckOut = true;
        
                    // Check if check-out is early leave
                    if (isset($record['early_leave']) && $record['early_leave'] == 1) {
                        $summary['early_leave']++;
                    }
                }
            }
        }

        // Check for missed check-in or check-out and update summary counts
        if (!$hasCheckIn) {
            $summary['missed_checkin']++;
            $checkinData = 'X'; // Indicate missed check-in
        }
        if (!$hasCheckOut) {
            $summary['missed_checkout']++;
            $checkoutData = 'X'; // Indicate missed check-out
        }

        $sheet->setCellValue($currentColumn . $row, "Check In: $checkinData\nCheck Out: $checkoutData");
        $sheet->getStyle($currentColumn . $row)->getAlignment()->setWrapText(true);
        $sheet->getStyle($currentColumn . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentColumn++;
    }

    // Insert summary data for each user right after the name
    $sheet->setCellValue('D' . $row, $summary['present'])
          ->setCellValue('E' . $row, $summary['izin'])
          ->setCellValue('F' . $row, $summary['sakit'])
          ->setCellValue('G' . $row, $summary['cuti'])
          ->setCellValue('H' . $row, $summary['late'])
          ->setCellValue('I' . $row, $summary['early_leave']);

    $summaries[$user_id] = $summary;

    $row++;
    $no++;
}

// Add summary columns for missed check-ins and check-outs at the end of the table
$sheet->setCellValue('D' . $row, 'Summary')
      ->setCellValue('E' . $row, 'Missed Check-Ins: ' . array_sum(array_column($summaries, 'missed_checkin')))
      ->setCellValue('F' . $row, 'Missed Check-Outs: ' . array_sum(array_column($summaries, 'missed_checkout')))
      ->setCellValue('G' . $row, 'Total Present: ' . array_sum(array_column($summaries, 'present')))
      ->setCellValue('H' . $row, 'Total Izin: ' . array_sum(array_column($summaries, 'izin')))
      ->setCellValue('I' . $row, 'Total Sakit: ' . array_sum(array_column($summaries, 'sakit')))
      ->setCellValue('J' . $row, 'Total Cuti: ' . array_sum(array_column($summaries, 'cuti')))
      ->setCellValue('K' . $row, 'Total Late: ' . array_sum(array_column($summaries, 'late')))
      ->setCellValue('L' . $row, 'Total Early Leave: ' . array_sum(array_column($summaries, 'early_leave')));

// Apply borders and background color for headers and summary rows
$sheet->getStyle('A4:' . $sheet->getHighestColumn() . '4')->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => $headerFillColor],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => $borderColor],
        ],
    ],
]);

$sheet->getStyle('A1:' . $lastColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);

$sheet->getStyle('A5:C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A5:C' . $row)->getFont()->setBold(false);

$sheet->getStyle('D5:' . $lastColumn . $row)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => $borderColor],
        ],
    ],
]);

$sheet->getStyle('A5:' . $sheet->getHighestColumn() . ($row - 1))->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => $borderColor],
        ],
    ],
]);

// Export to Excel
$writer = new Xlsx($spreadsheet);

$filename = "Monthly_Attendance_Report_{$year}_{$month}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit();

function getAttendanceSymbol($status) {
    switch ($status) {
        case 1: return '✓'; // Present
        case 2: return 'Izin'; // Permit
        case 3: return 'Sakit'; // Sick
        case 4: return 'Cuti'; // Leave
        default: return 'X'; // Absent or Unknown
    }
}
?>
