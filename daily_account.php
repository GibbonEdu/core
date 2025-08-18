<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // dùng kết nối chung qua .env ($pdo)

function formatDateVN($date) {
    if (empty($date)) return '';
    $ts = strtotime($date);
    if ($ts === false) return '';
    return date('d/m/Y', $ts);
}

// Handle filter date
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year']  ?? date('Y');
$month = sprintf('%02d', (int)$month);
$year  = (int)$year;

$startDate = "$year-$month-01";
$endDate   = date("Y-m-t", strtotime($startDate));

// Style
echo "<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 14px;
        padding: 10px;
        max-width: 1000px;
        margin: 0 auto;
    }
    .filter-form {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filter-form label {
        display: flex;
        flex-direction: column;
        font-weight: bold;
        font-size: 13px;
    }
    .filter-form select,
    .filter-form input[type='number'],
    .filter-form button {
        padding: 4px 8px;
        height: 32px;
        font-size: 13px;
        border-radius: 4px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }
    .filter-form button {
        background: #1976d2;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        border: none;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 6px;
        text-align: center;
        font-size: 13px;
        vertical-align: top;
    }
    th.description { text-align: center; width: 35%; }
    td.description { text-align: left;   width: 35%; }
    th { background-color: #f0f0f0; font-weight: bold; }
</style>";

// Filter form
echo "<form method='get' class='filter-form'>
    <label>Month
        <select name='month'>";
for ($m = 1; $m <= 12; $m++) {
    $opt = sprintf('%02d', $m);
    $selected = ($opt == $month) ? 'selected' : '';
    echo "<option value='{$opt}' {$selected}>{$opt}</option>";
}
echo "</select></label>
    <label>Year
        <input type='number' name='year' min='2000' max='2100' value='{$year}'>
    </label>
    <button type='submit'>View</button>
</form>";

echo "<h2>BAN THACH: INCOME & EXPENSE TRANSACTIONS - {$month}/{$year}</h2>";

$transactions = [];

// ===== INCOME DATA =====
// Lấy phiếu thu đã thanh toán trong khoảng ngày
$sqlIncome = "
    SELECT 
        i.gibbonFinanceInvoiceID,
        i.paidDate,
        i.paidAmount,
        i.notes,
        p.officialName AS studentName
    FROM gibbonFinanceInvoice i
    JOIN gibbonFinanceInvoicee ie ON i.gibbonFinanceInvoiceeID = ie.gibbonFinanceInvoiceeID
    JOIN gibbonPerson p ON ie.gibbonPersonID = p.gibbonPersonID
    WHERE i.status = 'Paid'
      AND i.paidDate BETWEEN :start AND :end
";
$stmt = $pdo->prepare($sqlIncome);
$stmt->execute([':start' => $startDate, ':end' => $endDate]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $transactions[] = [
        'date'            => $row['paidDate'],
        'voucher_income'  => (string)$row['gibbonFinanceInvoiceID'],
        'voucher_expense' => '',
        'desc'            => ($row['studentName'] ?? '') . ' - ' . ($row['notes'] ?? ''),
        'thu'             => (float)$row['paidAmount'],
        'chi'             => 0.0
    ];
}

// ===== EXPENSE DATA =====
// Lấy phiếu chi trong khoảng ngày
$sqlExpense = "
    SELECT 
        gibbonFinanceExpenseID,
        paymentDate AS date,
        title,
        cost
    FROM gibbonFinanceExpense
    WHERE paymentDate BETWEEN :start AND :end
";
$stmt = $pdo->prepare($sqlExpense);
$stmt->execute([':start' => $startDate, ':end' => $endDate]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $transactions[] = [
        'date'            => $row['date'],
        'voucher_income'  => '',
        'voucher_expense' => (string)$row['gibbonFinanceExpenseID'],
        'desc'            => $row['title'] ?? '',
        'thu'             => 0.0,
        'chi'             => (float)$row['cost']
    ];
}

// Sort by date (safe for nulls)
usort($transactions, function($a, $b) {
    $ta = empty($a['date']) ? 0 : strtotime($a['date']);
    $tb = empty($b['date']) ? 0 : strtotime($b['date']);
    if ($ta == $tb) return 0;
    return ($ta < $tb) ? -1 : 1;
});

// ===== Display Table =====
echo "<table>
    <thead>
        <tr>
            <th rowspan='2'>Date</th>
            <th colspan='2'>Voucher No.</th>
            <th rowspan='2' class='description'>DESCRIPTION</th>
            <th colspan='3'>AMOUNT</th>
        </tr>
        <tr>
            <th>Income</th>
            <th>Expense</th>
            <th>Income</th>
            <th>Expense</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>";

$balance = 0.0;
foreach ($transactions as $t) {
    $thu = $t['thu'];
    $chi = $t['chi'];
    $balance += ($thu - $chi);

    echo "<tr>
        <td>" . formatDateVN($t['date']) . "</td>
        <td>" . $t['voucher_income'] . "</td>
        <td>" . $t['voucher_expense'] . "</td>
        <td class='description'>" . $t['desc'] . "</td>
        <td>" . ($thu > 0 ? number_format($thu, 0) : '') . "</td>
        <td>" . ($chi > 0 ? number_format($chi, 0) : '') . "</td>
        <td>" . number_format($balance, 0) . "</td>
    </tr>";
}

echo "  </tbody>
</table>";
