<?php
require_once __DIR__.'/vendor/autoload.php';
require_once 'export_logic_core.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

$sheetData = $_POST['sheetData'] ?? [];
$classList = $_POST['classList'] ?? []; 
$teacherID = $_POST['teacherID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

if (empty($sheetData) || empty($classList)) {
    die("No data or class list.");
}

$pdo = new PDO("mysql:host=m.vanhoa.edu.vn;dbname=Gibbon_Intern;port=3306;charset=utf8mb4", "gibbon_intern", "vanhoa123@", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$spreadsheet = new Spreadsheet();
$sheetIndex = 0;

foreach ($sheetData as $classIndex => $rows) {
    if (!isset($classList[$classIndex])) continue;
    
    $class = $classList[$classIndex];
    $formGroupName = $class['className'];
    $schoolYearID = $class['schoolYearID'];
    $schoolYearName = $class['schoolYearName'];
    $schoolYearStart = $class['schoolYearStart'];
    $schoolYearEnd = $class['schoolYearEnd'];

    $sheet = ($sheetIndex == 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($sheetIndex);
    $sheetTitle = $formGroupName;
    if ($schoolYearID != $gibbonSchoolYearID) {
        $sheetTitle .= " OFF";
    }
    $sheet->setTitle(substr($sheetTitle, 0, 31));
    $sheetIndex++;

    foreach ($rows as $rowIndex => $cols) {
        foreach ($cols as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow((int)$colIndex, (int)$rowIndex, $value);
        }
    }

    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    $highestRow = $sheet->getHighestRow();
    $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColIndex);

    $sheet->getStyle("A1:{$lastColLetter}{$highestRow}")
        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    for ($r = 1; $r <= 7; $r++) {
        for ($c = 3; $c <= 7; $c++) {
            $sheet->getStyleByColumnAndRow($c, $r)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
    
    $lastCol = $sheet->getHighestColumn(); 
    for ($row = 1; $row <= 7; $row++) {
        $range = "A{$row}:{$lastCol}{$row}";
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->getColor()->setRGB('FFFFFF'); 
    }

    exportClassSheet($pdo, $sheet, $formGroupName, $schoolYearID, $schoolYearName, $schoolYearStart, $schoolYearEnd, $startDate, $endDate);
}

$filename = "export_teacher_{$teacherID}_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
