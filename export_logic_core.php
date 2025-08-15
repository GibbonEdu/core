<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!function_exists('formatDateVN')) {
    function formatDateVN($date) {
        if (empty($date)) return '';
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('getMonthFromBillingName')) {
    function getMonthFromBillingName($name) {
        preg_match('/\d{1,2}/', $name, $matches);
        return isset($matches[0]) ? (int)$matches[0] : null;
    }
}

if (!function_exists('exportClassSheet')) {
function exportClassSheet($pdo, $sheet, $formGroup, $gibbonSchoolYearID, $schoolYearName, $schoolYearStart, $schoolYearEnd, $startDate, $endDate) {
    $months = [];
    $schoolYearMonths = [];

    $stmt = $pdo->prepare("SELECT firstDay, lastDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID = :id");
    $stmt->execute([':id' => $gibbonSchoolYearID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
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

    $students = [];
    $totalPerMonth = [];
    $stmt = $pdo->prepare("
        SELECT p.officialName, p.gibbonPersonID
        FROM gibbonPerson p
        JOIN gibbonStudentEnrolment e ON p.gibbonPersonID = e.gibbonPersonID
        JOIN gibbonFormGroup fg ON e.gibbonFormGroupID = fg.gibbonFormGroupID
        WHERE fg.name = :formGroup AND fg.gibbonSchoolYearID = :schoolYearID
        ORDER BY p.officialName
    ");
    $stmt->execute([
        ':formGroup' => $formGroup,
        ':schoolYearID' => $gibbonSchoolYearID
    ]);
    foreach ($stmt as $s) {
        $students[$s['officialName']] = ['id' => $s['gibbonPersonID'], 'Months' => []];
    }

    if (empty($students)) return false;

    $stmt = $pdo->prepare("
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
    ");
    $stmt->execute([
        ':formGroup' => $formGroup,
        ':schoolYearID' => $gibbonSchoolYearID
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) return false;

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
                $totalPerMonth[$key] = ($totalPerMonth[$key] ?? 0) + $r['paidAmount'];
            } else {
                $cell = "DCS" . date("d-m-Y", strtotime(date("Y-m-t", $paidTS)));
            }
        } else {
            $cell = number_format($r['paidAmount'], 0);
            $totalPerMonth[$key] = ($totalPerMonth[$key] ?? 0) + $r['paidAmount'];
        }

        if (!empty($cell)) {
            $students[$r['studentName']]['Months'][$key][] = $cell;
        }
    }

    $sheet->setCellValue('A1', 'BD: ');
    $sheet->setCellValue('A2', 'HP: ');
    $sheet->setCellValue('A3', 'BDH: ');
    $sheet->setCellValue('A4', 'SLBT: ');
    $sheet->setCellValue('A5', 'BMT: ');
    $sheet->setCellValue('A6', 'BKH: ');
    $sheet->setCellValue('A7', 'DGKH: ');

    $sheet->setCellValue('C1', 'Ghi chu: ...');
    $sheet->setCellValue('C2', 'DANH SACH HOC SINH ' . $formGroup);
    $sheet->setCellValue('C3', 'NAM HOC: ' . $schoolYearName . ' / GIAO VIEN: ' . $formGroup);
    $sheet->setCellValue('C4', 'LICH HOC: ');
    $sheet->setCellValue('C5', 'Ngay hoan thanh: ');
    $sheet->setCellValue('C6', 'Thu hoc phi theo BUOI DA HOC hoac theo THANG');

    $sheet->setCellValue('M1', 'DIEN GIAI');
    $sheet->setCellValue('M2', 'DCS: Da chot so');
    $sheet->setCellValue('M3', 'HP: Hoc phi');
    $sheet->setCellValue('M4', 'BD: Bat dau day');
    $sheet->setCellValue('M5', 'BMT: Buoi moi tuan');
    $sheet->setCellValue('M6', 'SLBT: So buoi thang');
    $sheet->setCellValue('M7', 'BDH: Buoi da hoc');
    $sheet->setCellValue('N1', 'Nen vang: Giao vien thu');
    $sheet->setCellValue('N2', 'BKH: Buoi khong hoc');
    $sheet->setCellValue('N3', 'DGKH: Dien giai khong hoc');

    $columnCount = count($months) + 2;
    $colStart = 3;
    $colEnd = $columnCount - 2;

    $mergeStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colStart);
    $mergeEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colEnd);

    foreach (range(1, 7) as $row) {
        $sheet->mergeCells("{$mergeStart}{$row}:{$mergeEnd}{$row}");
        $sheet->getStyle("{$mergeStart}{$row}")->getAlignment()
            ->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle("{$mergeStart}{$row}")->getFont()->setSize($row == 1 ? 16 : 13);
    }

    foreach (range(1, 7) as $row) {
        foreach (['A', 'B', 'L', 'M'] as $col) {
            $sheet->getStyle("{$col}{$row}")->getAlignment()
                ->setHorizontal('left')->setVertical('top')->setWrapText(true);
        }
    }
    
    $totalRow = ['Total',''];
        foreach ($months as $m) {
            $key = $m['key'];
            $totalRow[] = isset($totalPerMonth[$key]) && $totalPerMonth[$key] > 0
                ? number_format($totalPerMonth[$key], 0) : '';
        }
        $sheet->mergeCells("A8:B8");
        $sheet->getStyle("A8")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A8")->getFont()->setBold(true);
        $sheet->fromArray($totalRow, null, "A8");


    $header = ['No.','Student'];
    foreach ($months as $m) {
        $header[] = "{$m['month']}/{$m['year']}";
    }
    $sheet->fromArray($header, null, 'A9');
    $rowNum = 10;
    $stt = 1;
    foreach ($students as $student => $data) {
        $row = [$stt++, $student];
        foreach ($months as $m) {
            $key = $m['key'];
            $items = $data['Months'][$key] ?? [];
            $row[] = implode("\n", $items);
        }
        $sheet->fromArray($row, null, "A{$rowNum}");
        $rowNum++;
    }

    $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
    $lastDataRow = $rowNum - 1;
    $sheet->getStyle("A8:{$highestColumn}{$lastDataRow}")->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getStyle("{$col}")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return true;
}
}
