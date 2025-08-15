<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$databaseServer = 'm.vanhoa.edu.vn';
$databaseUsername = 'gibbon_intern';
$databasePassword = 'vanhoa123@';
$databaseName = 'Gibbon_Intern';
$databasePort = 3306;

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function formatDateVN($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}
function getMonthFromBillingName($name) {
    preg_match('/\d{1,2}/', $name, $matches);
    return isset($matches[0]) ? (int)$matches[0] : null;
}

try {
    $pdo = new PDO("mysql:host=$databaseServer;dbname=$databaseName;port=$databasePort;charset=utf8mb4", $databaseUsername, $databasePassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $teacherID = $_GET['teacher'] ?? '';
    $formGroup = $_GET['formGroup'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';

    if (isset($_GET['export']) && $_GET['export'] == '1') {
        include 'export_logic.php';
        exit;
    }

    $schoolYearMonths = [];
    $months = [];
    $schoolYearName = '';
    $schoolYearStart = '';
    $schoolYearEnd = '';
    
    if (!empty($gibbonSchoolYearID)) {
        $stmt = $pdo->prepare("SELECT firstDay, lastDay, name FROM gibbonSchoolYear WHERE gibbonSchoolYearID = :id");
        $stmt->execute([':id' => $gibbonSchoolYearID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($row) {
            $schoolYearName = $row['name'];
            $schoolYearStart = date('Y', strtotime($row['firstDay']));
            $schoolYearEnd = date('Y', strtotime($row['lastDay']));
            $start = new DateTime($row['firstDay']);
            $end = new DateTime($row['lastDay']);
            $end->modify('last day of this month');
    
            while ($start <= $end) {
                $month = (int)$start->format('n');
                $year = (int)$start->format('Y');
                $key = "$month-$year";
    
                $schoolYearMonths[$key] = true;
                $months[] = ['key' => $key, 'month' => $month, 'year' => $year];
    
                $start->modify('+1 month');
            }
        }
    }


    echo "<style>
    .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .filter-form label { display: flex; flex-direction: column; font-weight: bold; font-size: 14px; color: #333; }
    .filter-form select, .filter-form input, .filter-form button { margin-top: 5px; padding: 8px 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; }
    .filter-form button { background-color: #1976d2; color: white; cursor: pointer; font-weight: bold; border: none; }
    .filter-form button:hover { background-color: #1558a3; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f0f0f0; }
    </style>";
    echo "<form method='get' class='filter-form'>";
    echo "<input type='hidden' name='startDate' value='".safe($startDate)."'>";
    echo "<input type='hidden' name='endDate' value='".safe($endDate)."'>";
    $stmt = $pdo->query("SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC");
    $schoolYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<label>School Year<select name='gibbonSchoolYearID' onchange='this.form.submit()'>";
    echo "<option value=''>-- Select Year --</option>";
    foreach ($schoolYears as $sy) {
        $selected = ($sy['gibbonSchoolYearID'] == $gibbonSchoolYearID) ? 'selected' : '';
        echo "<option value='".safe($sy['gibbonSchoolYearID'])."' $selected>".safe($sy['name'])."</option>";
    }
    echo "</select></label>";
        if (!empty($gibbonSchoolYearID)) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.gibbonPersonID, CONCAT(p.surname, ' ', p.preferredName) AS teacherName
                FROM gibbonFormGroup fg
                JOIN gibbonPerson p ON fg.gibbonPersonIDTutor = p.gibbonPersonID
                WHERE fg.gibbonSchoolYearID = :schoolYearID
                ORDER BY teacherName
            ");
            $stmt->execute([':schoolYearID' => $gibbonSchoolYearID]);
            $teacherList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            echo "<label>Teacher<select name='teacher' onchange='this.form.submit()'>";
            echo "<option value=''>-- Select --</option>";
            foreach ($teacherList as $t) {
                $selected = ($t['gibbonPersonID'] == $teacherID) ? 'selected' : '';
                echo "<option value='".safe($t['gibbonPersonID'])."' $selected>".safe($t['teacherName'])."</option>";
            }
            echo "</select></label>";
        }
        
        if (!empty($teacherID) && !empty($gibbonSchoolYearID)) {
            $stmt = $pdo->prepare("
                SELECT name 
                FROM gibbonFormGroup 
                WHERE gibbonPersonIDTutor = :teacherID 
                  AND gibbonSchoolYearID = :schoolYearID
                ORDER BY name
            ");
            $stmt->execute([
                ':teacherID' => $teacherID,
                ':schoolYearID' => $gibbonSchoolYearID
            ]);
            $formGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
            echo "<label>Class<select name='formGroup'>";
            echo "<option value=''>-- Select Class --</option>";
            foreach ($formGroups as $g) {
                $selected = ($g == $formGroup) ? 'selected' : '';
                echo "<option value='".safe($g)."' $selected>".safe($g)."</option>";
            }
            echo "</select></label>";
        }
    
    echo "<label>From Date<input type='date' name='startDate' value='".safe($startDate)."'></label>";
    echo "<label>To Date<input type='date' name='endDate' value='".safe($endDate)."'></label>";
    echo "<div><button type='submit'>View</button></div>";
    echo "<div><button type='submit' name='export' value='1'>Export to Excel</button></div>";
    echo "</form>";
    
    if ($formGroup == '') {
        echo "<div style='color:red'><strong>Please select class.</strong></div>";
        exit;
    }

    $teacherName = '';
    if ($teacherID != '' && $gibbonSchoolYearID != '') {
        $stmt = $pdo->prepare("
            SELECT name 
            FROM gibbonFormGroup 
            WHERE gibbonPersonIDTutor = :teacherID 
              AND gibbonSchoolYearID = :schoolYearID
            ORDER BY name
        ");
        $stmt->execute([
            ':teacherID' => $teacherID,
            ':schoolYearID' => $gibbonSchoolYearID
        ]);
        $formGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


    $students = [];
    $totalPerMonth = [];
    $studentRows = $pdo->prepare("
        SELECT p.officialName, p.gibbonPersonID
        FROM gibbonPerson p
        JOIN gibbonStudentEnrolment e ON p.gibbonPersonID = e.gibbonPersonID
        JOIN gibbonFormGroup fg ON e.gibbonFormGroupID = fg.gibbonFormGroupID
        WHERE fg.name = :formGroup AND fg.gibbonSchoolYearID = :schoolYearID
        ORDER BY p.officialName
    ");
    $studentRows->execute([
        ':formGroup' => $formGroup,
        ':schoolYearID' => $gibbonSchoolYearID
    ]);
    foreach ($studentRows as $s) {
        $students[$s['officialName']] = ['id' => $s['gibbonPersonID'], 'Months' => []];
    }

    $sql = "
        SELECT 
            p.officialName AS studentName,
            i.paidDate,
            i.paidAmount,
            bs.name AS billingScheduleName
        FROM gibbonFinanceInvoice i
        JOIN gibbonFinanceInvoicee ie ON i.gibbonFinanceInvoiceeID = ie.gibbonFinanceInvoiceeID
        JOIN gibbonPerson p ON ie.gibbonPersonID = p.gibbonPersonID
        JOIN gibbonStudentEnrolment e ON p.gibbonPersonID = e.gibbonPersonID
        JOIN gibbonFormGroup f ON e.gibbonFormGroupID = f.gibbonFormGroupID
        LEFT JOIN gibbonFinanceBillingSchedule bs ON i.gibbonFinanceBillingScheduleID = bs.gibbonFinanceBillingScheduleID
        WHERE f.name = :formGroup AND i.status LIKE 'Paid%' AND i.gibbonSchoolYearID = :schoolYearID
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':formGroup' => $formGroup,
        ':schoolYearID' => $gibbonSchoolYearID
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $paidTS = strtotime($r['paidDate'] ?? '');
        if ($paidTS <= 0) continue; 
        
        $billingMonth = getMonthFromBillingName($r['billingScheduleName'] ?? '');
        if (!$billingMonth) continue;
        
        $billingYear = $billingMonth >= 6 ? $schoolYearStart : $schoolYearEnd;
        $key = "$billingMonth-$billingYear";
        
        if (!array_key_exists($key, $schoolYearMonths)) continue;
        
        if (!empty($startDate) && !empty($endDate)) {
            $filterStart = strtotime($startDate);
            $filterEnd = strtotime($endDate);
        
            if ($paidTS > $filterEnd) continue;
        
            if ($paidTS >= $filterStart && $paidTS <= $filterEnd) {
                $cell = number_format($r['paidAmount'], 0);
                if (!isset($totalPerMonth[$key])) {
                    $totalPerMonth[$key] = 0;
                }
                $totalPerMonth[$key] += $r['paidAmount'];
            } else {
                $cell = "DCS" . date("d-m-Y", strtotime(date("Y-m-t", $paidTS)));
            }
        } else {
            $cell = number_format($r['paidAmount'], 0);
            $totalPerMonth[$key] += $r['paidAmount'];
        }
        
        if (!empty($cell)) {
            $students[$r['studentName']]['Months'][$key][] = $cell;
        }
    }

    echo "<h2>Payments - Class: ".safe($formGroup)." | School Year: ".safe($schoolYearName)."</h2>";
    echo "<div><strong>Class Teacher:</strong> ".safe($teacherName)."</div>";
    echo "<table><tr><th>Student</th>";  
    foreach ($months as $m) {
        echo "<th>{$m['month']}/{$m['year']}</th>";
    }
    echo "</tr>";

    foreach ($students as $student => $data) {
        echo "<tr><td>".safe($student)."</td>";
        foreach ($months as $m) {
            $key = $m['key'];
            $items = $data['Months'][$key] ?? [];
            echo "<td>".implode("<br>", $items)."</td>";
        }
        echo "</tr>";
    }

    echo "<tr><td><strong>Total</strong></td>";
    foreach ($months as $m) {
        $key = $m['key'];
        $value = $totalPerMonth[$key] ?? 0;
echo "<td><strong>".($value > 0 ? number_format($value, 0) : '')."</strong></td>";
    }
    echo "</tr></table>";

} catch (PDOException $e) {
    echo "<div style='color:red'><strong>DB ERROR:</strong> ".$e->getMessage()."</div>";
}
?>
