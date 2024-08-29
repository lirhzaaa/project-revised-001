<?php
require_once 'vendor/autoload.php'; // Ensure this path is correct

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add some test data
$sheet->setCellValue('A1', 'Hello');
$sheet->setCellValue('B1', 'World');

// Set headers to indicate a file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="test.xlsx"');
header('Cache-Control: max-age=0');

// Write the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
