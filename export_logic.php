<?php
require_once __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$teacherID = $_GET['teacher'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

if (empty($teacherID) || empty($gibbonSchoolYearID)) {
    echo "<div style='color:red'><strong>Error:</strong> Missing required parameters.</div>";
    exit;
}

// DB Connection
$databaseServer = 'm.vanhoa.edu.vn';
$databaseUsername = 'gibbon_intern';
$databasePassword = 'vanhoa123@';
$databaseName = 'Gibbon_Intern';
$databasePort = 3306;
$pdo = new PDO("mysql:host=$databaseServer;dbname=$databaseName;port=$databasePort;charset=utf8mb4", $databaseUsername, $databasePassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once 'export_logic_core.php';

$spreadsheet = new Spreadsheet();
$sheetIndex = 0;

$stmt = $pdo->prepare("
    SELECT fg.name AS className, fg.gibbonSchoolYearID, sy.name AS schoolYearName, sy.firstDay, sy.lastDay
    FROM gibbonFormGroup fg
    JOIN gibbonSchoolYear sy ON fg.gibbonSchoolYearID = sy.gibbonSchoolYearID
    WHERE fg.gibbonPersonIDTutor = :teacherID
    ORDER BY sy.sequenceNumber DESC, fg.name
");
$stmt->execute([':teacherID' => $teacherID]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filteredClasses = [];

foreach ($classes as $class) {
    $formGroupName = $class['className'];
    $schoolYearID = $class['gibbonSchoolYearID'];

    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM gibbonFinanceInvoice i
        JOIN gibbonFinanceInvoicee ie ON i.gibbonFinanceInvoiceeID = ie.gibbonFinanceInvoiceeID
        JOIN gibbonPerson p ON ie.gibbonPersonID = p.gibbonPersonID
        JOIN gibbonStudentEnrolment e ON p.gibbonPersonID = e.gibbonPersonID
        JOIN gibbonFormGroup fg ON e.gibbonFormGroupID = fg.gibbonFormGroupID
        WHERE fg.name = :formGroup 
          AND fg.gibbonSchoolYearID = :schoolYearID 
          AND i.status LIKE 'Paid%'
          AND (
              (:startDate IS NULL AND :endDate IS NULL)
              OR (i.paidDate BETWEEN :startDate AND :endDate)
          )
    ");
    $checkStmt->execute([
        ':formGroup' => $formGroupName,
        ':schoolYearID' => $schoolYearID,
        ':startDate' => $startDate ?: null,
        ':endDate' => $endDate ?: null
    ]);

    $invoiceCount = $checkStmt->fetchColumn();

    if ($invoiceCount > 0) {
        $filteredClasses[] = $class; 
    }
}

$classes = $filteredClasses;


echo "<style>
    table.preview-table { border-collapse: collapse; width: 100%; background: #fff; margin-bottom: 40px; }
    table.preview-table td { padding: 8px; border: 1px solid #ccc; min-width: 100px; font-size: 14px; text-align: center; }
    table.preview-table tr:nth-child(odd) { background-color: #f9f9f9; }
    table.preview-table td[contenteditable='true'] { background-color: #ffffe0; }
</style>";

echo "<form method='post' action='save_excel.php'>";
foreach ($classes as $classIndex => $class) {
    $formGroupName = $class['className'];
    $schoolYearID = $class['gibbonSchoolYearID'];
    $schoolYearName = $class['schoolYearName'];
    $schoolYearStart = date('Y', strtotime($class['firstDay']));
    $schoolYearEnd = date('Y', strtotime($class['lastDay']));

    $sheet = ($sheetIndex == 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($sheetIndex);
    $sheet->setTitle(substr($formGroupName, 0, 31));
    $sheetIndex++;

    exportClassSheet($pdo, $sheet, $formGroupName, $schoolYearID, $schoolYearName, $schoolYearStart, $schoolYearEnd, $startDate, $endDate);

    echo "<h3>Sheet: " . htmlspecialchars($sheet->getTitle()) . "</h3>";
    echo "<input type='hidden' name='classList[$classIndex][className]' value='" . htmlspecialchars($formGroupName) . "'>";
    echo "<input type='hidden' name='classList[$classIndex][schoolYearID]' value='" . htmlspecialchars($schoolYearID) . "'>";
    echo "<input type='hidden' name='classList[$classIndex][schoolYearName]' value='" . htmlspecialchars($schoolYearName) . "'>";
    echo "<input type='hidden' name='classList[$classIndex][schoolYearStart]' value='" . htmlspecialchars($schoolYearStart) . "'>";
    echo "<input type='hidden' name='classList[$classIndex][schoolYearEnd]' value='" . htmlspecialchars($schoolYearEnd) . "'>";
    echo "<table class='preview-table'>";

    $highestRow = $sheet->getHighestRow();
    $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    
    $columnCount = $highestCol; 
    $colStart = 3; 
    $colEnd = $columnCount - 2; 
    
    $mergeStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colStart);
    $mergeEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colEnd);
    
    for ($r = 1; $r <= 7; $r++) {
        echo "<tr>";
    
        for ($c = 1; $c <= 2; $c++) {
            $val = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
            echo "<td>";
            echo "<textarea name='sheetData[$classIndex][$r][$c]' rows='2' style='width:100%; background:#ffffe0; font-weight:bold; text-align:left;'>"
                . htmlspecialchars($val) . "</textarea>";
            echo "</td>";
        }
    
        $val = $sheet->getCell("{$mergeStart}{$r}")->getFormattedValue();
        echo "<td colspan='" . ($colEnd - $colStart + 1) . "'>";
        echo "<textarea name='sheetData[$classIndex][$r][$colStart]' rows='2' style='width:100%; background:#ffffe0; font-weight:bold; text-align:center;'>"
            . htmlspecialchars($val) . "</textarea>";
        echo "</td>";
    
        for ($c = $colEnd+1; $c <= $columnCount; $c++) {
            $val = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
            echo "<td>";
            echo "<textarea name='sheetData[$classIndex][$r][$c]' rows='2' style='width:100%; background:#ffffe0; font-weight:bold; text-align:left;'>"
                . htmlspecialchars($val) . "</textarea>";
            echo "</td>";
        }
    
        echo "</tr>";
    }
        for ($r = 8; $r <= $highestRow; $r++) {
            echo "<tr>";
            for ($c = 1; $c <= $highestCol; $c++) {
                $value = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                echo "<td>" . nl2br(htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }

echo "<input type='hidden' name='teacherID' value='" . htmlspecialchars($teacherID) . "'>";
echo "<input type='hidden' name='gibbonSchoolYearID' value='" . htmlspecialchars($gibbonSchoolYearID) . "'>";
echo "<input type='hidden' name='startDate' value='" . htmlspecialchars($startDate) . "'>";
echo "<input type='hidden' name='endDate' value='" . htmlspecialchars($endDate) . "'>";

echo "<button type='submit' style='padding: 10px 25px; font-size: 16px; background-color: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer;'>Save to Excel</button>";
echo "</form>";
