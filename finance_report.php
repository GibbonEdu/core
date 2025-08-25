<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/lib/openai.php';
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/db.php'; // <-- Dùng kết nối chung qua .env (tạo $pdo)

function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   Settings (JSON, no-code)
   ========================= */
$settingsPath = __DIR__ . '/finance_settings.json';

function loadSettings(string $path): array {
    if (is_file($path)) {
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    // defaults
    return [
        'centerFeePercent' => 20, // %
        'teacherNames' => [],     // one per line in UI
        'prompts' => [
            'detectTeacher' =>
"You are an assistant analyzing a payment note to determine which teacher is being referred to.

List of teachers:
{{TEACHER_LIST}}

Note:
\"{{NOTE}}\"

Rules:
- In the Vietnamese naming system, the person's given name is usually the last token.
- If the note has a single word, treat it as the given name and match.

Return ONE line only: the matched full name from the list or \"Unknown\".",
            'advance' =>
"Only answer Yes or No.
The following note is from a payment record.
Teacher: {{TEACHER_NAME}}
Does the note indicate an ADVANCE (money given to teacher)? Answer Yes only if it CLEARLY states advance/received/paid to teacher.
Note:
\"{{NOTE}}\"",
            'held' =>
"Only answer Yes or No.
The following note is about a tuition payment.
Does it clearly indicate the teacher COLLECTED AND KEPT the money (received but not forwarded)?
Keywords may include: \"thu v\u00e0 gi\u1eef\", \"thu va giu\", \"giu tien\", \"giu lai\".
Note:
\"{{NOTE}}\""
        ],
    ];
}

function saveSettings(string $path, array $data): bool {
    return (bool)file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

$APP_SETTINGS = loadSettings($settingsPath);

// Handle save from Settings modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $teacherRaw    = $_POST['teacherNames'] ?? '';
    $teacherLines  = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string)$teacherRaw))));
    $centerPercent = max(0, min(100, (int)($_POST['centerFeePercent'] ?? 20)));
    $detectP  = trim($_POST['prompt_detectTeacher'] ?? '');
    $advanceP = trim($_POST['prompt_advance'] ?? '');
    $heldP    = trim($_POST['prompt_held'] ?? '');

    $APP_SETTINGS['teacherNames']      = $teacherLines;
    $APP_SETTINGS['centerFeePercent']  = $centerPercent;
    if ($detectP  !== '') $APP_SETTINGS['prompts']['detectTeacher'] = $detectP;
    if ($advanceP !== '') $APP_SETTINGS['prompts']['advance']       = $advanceP;
    if ($heldP    !== '') $APP_SETTINGS['prompts']['held']          = $heldP;

    if (saveSettings($settingsPath, $APP_SETTINGS)) {
        header("Location: " . strtok($_SERVER['REQUEST_URI'],'?') . '?' . http_build_query($_GET + ['saved'=>'1']));
        exit;
    } else {
        header("Location: " . strtok($_SERVER['REQUEST_URI'],'?') . '?' . http_build_query($_GET + ['saved'=>'0']));
        exit;
    }
}

function renderPrompt(string $tpl, array $vars): string {
    foreach ($vars as $k => $v) {
        $tpl = str_replace('{{'.strtoupper($k).'}}', $v, $tpl);
        $tpl = str_replace('{{'.ucfirst($k).'}}', $v, $tpl);
        $tpl = str_replace('{{'.$k.'}}', $v, $tpl);
    }
    return $tpl;
}

/* ==============
   AI helpers
   ============== */
function detectTeacherNameFromNote($note, array $teacherList): string {
    global $APP_SETTINGS;
    $api_key = getApiKey();
    if ($api_key === '') return 'Unknown';

    $teacherLines = implode("\n- ", $teacherList);
    $tpl = $APP_SETTINGS['prompts']['detectTeacher'] ?? '';
    $prompt = renderPrompt($tpl, [
        'TEACHER_LIST' => "- " . $teacherLines,
        'NOTE' => (string)$note,
    ]);

    $url = 'https://api.openai.com/v1/chat/completions';
    $post_fields = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You answer only with one name or "Unknown".'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1,
        'max_tokens' => 30
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($post_fields, JSON_UNESCAPED_UNICODE)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return trim($data['choices'][0]['message']['content'] ?? 'Unknown');
}

function checkSalaryNote($note, $teacherFullName = '') {
    global $APP_SETTINGS;
    $api_key = getApiKey();
    if ($api_key === '') return 'No';

    $tpl = $APP_SETTINGS['prompts']['advance'] ?? '';
    $prompt = renderPrompt($tpl, [
        'TEACHER_NAME' => (string)$teacherFullName,
        'NOTE' => (string)$note,
    ]);

    $url = 'https://api.openai.com/v1/chat/completions';
    $post_fields = [
        'model' => 'gpt-4-1106-preview',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant that answers only "Yes" or "No".'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1,
        'max_tokens' => 10
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($post_fields, JSON_UNESCAPED_UNICODE)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return trim($data['choices'][0]['message']['content'] ?? 'No');
}

function checkHeldMoney($note) {
    global $APP_SETTINGS;
    $api_key = getApiKey();
    if ($api_key === '') return 'No';

    $tpl = $APP_SETTINGS['prompts']['held'] ?? '';
    $prompt = renderPrompt($tpl, [
        'NOTE' => (string)$note,
    ]);

    $url = 'https://api.openai.com/v1/chat/completions';
    $post_fields = [
        'model' => 'gpt-4-1106-preview',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a financial assistant. Only answer "Yes" or "No".'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1,
        'max_tokens' => 10
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($post_fields, JSON_UNESCAPED_UNICODE)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return trim($data['choices'][0]['message']['content'] ?? 'No');
}

/* ==============
   Filters
   ============== */
$from = $_GET['startDate'] ?? '';
$to = $_GET['endDate'] ?? '';
$class = $_GET['class'] ?? '';
$teacher = $_GET['teacher'] ?? '';
$feeType = $_GET['feeCategory'] ?? '';
$expenseType = $_GET['budgetCategory'] ?? '';
$selectedSchoolYear = $_GET['schoolYear'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

$classList = [];
if (!empty($selectedSchoolYear)) {
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM gibbonFormGroup WHERE gibbonSchoolYearID = :schoolYearID ORDER BY name ASC");
    $stmt->execute([':schoolYearID' => $selectedSchoolYear]);
    $classList = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$teacherList = [];
if (!empty($selectedSchoolYear)) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.gibbonPersonID AS id, p.firstName, CONCAT(p.surname, ' ', p.preferredName) AS name
        FROM gibbonFormGroup fg
        JOIN gibbonPerson p ON fg.gibbonPersonIDTutor = p.gibbonPersonID
        WHERE fg.gibbonSchoolYearID = :schoolYearID
        ORDER BY name ASC
    ");
    $stmt->execute([':schoolYearID' => $selectedSchoolYear]);
    $teacherList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$teacherNames = array_column($teacherList, 'name');
if (!empty($APP_SETTINGS['teacherNames'])) {
    // Use the manual list for AI (does not affect dropdown)
    $teacherNames = $APP_SETTINGS['teacherNames'];
}

$feeCategories = $pdo->query("SELECT gibbonFinanceFeeCategoryID AS id, name FROM gibbonFinanceFeeCategory WHERE active = 'Y' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$budgetCategories = $pdo->query("SELECT DISTINCT name FROM gibbonFinanceBudget WHERE active = 'Y' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

/* ==========================
   Build data & computation
   ========================== */
$data1 = []; // income
$data2 = []; // expense
$data = [];
$ketToan = null;
$totalRows = 0;
$totalPages = 0;
$noteToTeacherMap = [];
$cacheAdvance = [];
$cacheHeld = [];

$teacherMismatch = false;
if ($selectedSchoolYear && $class && $teacher) {
    $chk = $pdo->prepare("
        SELECT COUNT(*) 
        FROM gibbonFormGroup 
        WHERE gibbonSchoolYearID = :y AND name = :c AND gibbonPersonIDTutor = :t
    ");
    $chk->execute([':y' => $selectedSchoolYear, ':c' => $class, ':t' => $teacher]);
    $teacherMismatch = ($chk->fetchColumn() == 0);
}

if ($from && $to) {
    // ------- INCOME -------
    if ($feeType || $class || $teacher || (!$feeType && !$expenseType && !$class && !$teacher)) {
        $sql1 = "
            SELECT 
                'Income' AS type,
                student.officialName AS name,
                gibbonFinanceInvoice.gibbonFinanceInvoiceID AS recordID,
                gibbonFinanceInvoice.paidAmount AS amount,
                gibbonFinanceInvoice.invoiceIssueDate AS issueDate,
                gibbonFinanceInvoice.paidDate AS date,
                gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList AS feeCategoryIDs,
                gibbonFinanceInvoice.notes AS note,
                '' AS category
            FROM gibbonFinanceInvoice
            LEFT JOIN gibbonFinanceInvoicee 
                ON gibbonFinanceInvoice.gibbonFinanceInvoiceeID = gibbonFinanceInvoicee.gibbonFinanceInvoiceeID
            LEFT JOIN gibbonPerson AS student 
                ON gibbonFinanceInvoicee.gibbonPersonID = student.gibbonPersonID
            LEFT JOIN gibbonStudentEnrolment se
                ON student.gibbonPersonID = se.gibbonPersonID
            LEFT JOIN gibbonFormGroup 
                ON se.gibbonFormGroupID = gibbonFormGroup.gibbonFormGroupID
            LEFT JOIN gibbonPerson AS tutor 
                ON gibbonFormGroup.gibbonPersonIDTutor = tutor.gibbonPersonID
            WHERE gibbonFinanceInvoice.status = 'Paid'
              AND DATE(gibbonFinanceInvoice.paidDate) BETWEEN :from AND :to
        ";
        $params1 = [':from' => $from, ':to' => $to];

        if ($selectedSchoolYear) {
            $sql1 .= " AND se.gibbonSchoolYearID = :sy";
            $params1[':sy'] = $selectedSchoolYear;
        }
        if ($class) {
            $sql1 .= " AND gibbonFormGroup.name = :class";
            $params1[':class'] = $class;
        }
        if ($teacher) {
            $sql1 .= " AND tutor.gibbonPersonID = :teacher";
            $params1[':teacher'] = $teacher;
        }
        if ($feeType && $feeType !== '*all') {
            $sql1 .= " AND gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList IS NOT NULL 
                       AND FIND_IN_SET(:feeType, gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList)";
            $params1[':feeType'] = $feeType;
        }

        $sql1 .= " ORDER BY gibbonFinanceInvoice.paidDate ASC, gibbonFinanceInvoice.gibbonFinanceInvoiceID ASC";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute($params1);
        $data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // resolve fee category names
        if (!empty($data1)) {
            $feeCategoryMap = [];
            foreach ($feeCategories as $fc) $feeCategoryMap[$fc['id']] = $fc['name'];
            foreach ($data1 as &$row) {
                $ids = array_filter(array_map('trim', explode(',', $row['feeCategoryIDs'] ?? '')));
                $names = [];
                foreach ($ids as $id) if (isset($feeCategoryMap[$id])) $names[] = $feeCategoryMap[$id];
                $row['category'] = implode(', ', $names);
            }
            unset($row);
        }
    }

    // ------- EXPENSE -------
    if ($expenseType || (!$feeType && !$expenseType && !$class && !$teacher) || $teacher) {
        $sql2 = "
            SELECT 
                'Expense' AS type,
                gibbonFinanceExpense.title AS name,
                gibbonFinanceExpense.gibbonFinanceExpenseID AS recordID,
                gibbonFinanceExpense.cost AS amount,
                NULL AS issueDate,
                gibbonFinanceExpense.paymentDate AS date,
                gibbonFinanceBudget.name AS category,
                gibbonFinanceExpense.title AS note
            FROM gibbonFinanceExpense
            JOIN gibbonFinanceBudget ON gibbonFinanceExpense.gibbonFinanceBudgetID = gibbonFinanceBudget.gibbonFinanceBudgetID
            WHERE DATE(gibbonFinanceExpense.paymentDate) BETWEEN :from AND :to
        ";
        $params2 = [':from' => $from, ':to' => $to];

        if ($expenseType && $expenseType !== '*all') {
            $sql2 .= " AND gibbonFinanceBudget.name = :bcat";
            $params2[':bcat'] = $expenseType;
        }

        $sql2 .= " ORDER BY gibbonFinanceExpense.paymentDate ASC, gibbonFinanceExpense.gibbonFinanceExpenseID ASC";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // if filtering by teacher: keep expenses matched to teacher AND identified as advance
        if ($teacher && !empty($data2) && !empty($teacherList)) {
            $filtered = [];
            $selectedTeacherName = '';
            foreach ($teacherList as $t) {
                if ((string)$t['id'] === (string)$teacher) { $selectedTeacherName = $t['name']; break; }
            }
            // map note -> teacher
            foreach ($data2 as $row) {
                $note = $row['name'] ?? '';
                if (!isset($noteToTeacherMap[$note])) {
                    $noteToTeacherMap[$note] = detectTeacherNameFromNote($note, $teacherNames);
                }
            }
            foreach ($data2 as $row) {
                $note = $row['name'] ?? '';
                $identifiedTeacher = $noteToTeacherMap[$note] ?? 'Unknown';
                if ($identifiedTeacher === $selectedTeacherName) {
                    if (!isset($cacheAdvance[$note])) {
                        $cacheAdvance[$note] = strtolower(trim(checkSalaryNote($note, $identifiedTeacher)));
                    }
                    if (preg_replace('/[^a-z]/i', '', $cacheAdvance[$note]) === 'yes') {
                        $filtered[] = $row;
                    }
                }
            }
            $data2 = $filtered;
        }
    }

    // ------- MERGE + SORT -------
    $data = array_merge($data1, $data2);
    usort($data, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));

    // ------- TOTALS + SUMMARY -------
    $totalRows = count($data);
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    if ($teacher && (!empty($data1))) {
        $tongThu   = 0.0;
        $thuVaGiu  = 0.0;
        $ungLuong  = 0.0;

        $selectedTeacherName = '';
        foreach ($teacherList as $t) {
            if ((string)$t['id'] === (string)$teacher) { $selectedTeacherName = $t['name']; break; }
        }

        // expense -> teacher + advances
        foreach ($data2 as $row) {
            $note = $row['name'] ?? '';
            if (!isset($noteToTeacherMap[$note])) {
                $noteToTeacherMap[$note] = detectTeacherNameFromNote($note, $teacherNames);
            }
        }
        foreach ($data2 as $row) {
            $note = $row['name'] ?? '';
            $identifiedTeacher = $noteToTeacherMap[$note] ?? 'Unknown';
            if ($identifiedTeacher === $selectedTeacherName) {
                if (!isset($cacheAdvance[$note])) {
                    $cacheAdvance[$note] = strtolower(trim(checkSalaryNote($note, $identifiedTeacher)));
                }
                if (preg_replace('/[^a-z]/i', '', $cacheAdvance[$note]) === 'yes') {
                    $ungLuong += (float)$row['amount'];
                }
            }
        }

        // income totals + "hold & collect"
        foreach ($data1 as $row) {
            $tongThu += (float)$row['amount'];
            $note = $row['note'] ?? '';
            if (!isset($cacheHeld[$note])) {
                $cacheHeld[$note] = strtolower(trim(checkHeldMoney($note)));
            }
            if (preg_replace('/[^a-z]/i', '', $cacheHeld[$note]) === 'yes') {
                $thuVaGiu += (float)$row['amount'];
            }
        }

        $phiTrungTam = $tongThu * ((float)($APP_SETTINGS['centerFeePercent'] ?? 20) / 100.0);
        $ketToan = [
            'tongThu'     => $tongThu,
            'phiTrungTam' => $phiTrungTam,
            'thuVaGiu'    => $thuVaGiu,
            'ungLuong'    => $ungLuong
        ];
    }
}
?>
<!-- UI -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
    body { display: flex; min-height: 100vh; margin: 0; background:#fbfbfb; }
    .sidebar {
        width: 300px;
        background-color: #f8f9fa;
        padding: 20px;
        border-right: 1px solid #dee2e6;
        position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto;
    }
    .content {
        flex: 1; padding: 20px; margin-left: 300px; box-sizing: border-box;
        height: 100vh; overflow-y: auto;
        max-width: 1100px;   /* narrower layout */
    }
    .container-narrow { max-width: 1100px; margin: 0 auto; }
    .table thead th { position: sticky; top: 0; background: #fff; z-index: 5; }
    .bg-success.text-white { color: #ffffff !important; }
    .bg-warning.text-black { color: #000000 !important; }
    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-center-important { text-align: center !important; vertical-align: middle !important; }
</style>

<script>
window.onload = function () {
    const feeSelect = document.querySelector('select[name="feeCategory"]');
    const budgetSelect = document.querySelector('select[name="budgetCategory"]');
    const classSelect = document.querySelector('select[name="class"]');
    const teacherSelect = document.querySelector('select[name="teacher"]');
    if (feeSelect) feeSelect.addEventListener('change', function () {
        if (classSelect) classSelect.selectedIndex = 0;
        if (teacherSelect) teacherSelect.selectedIndex = 0;
    });
    if (budgetSelect) budgetSelect.addEventListener('change', function () {
        if (classSelect) classSelect.selectedIndex = 0;
        if (teacherSelect) teacherSelect.selectedIndex = 0;
    });
    if (classSelect) classSelect.addEventListener('change', function () {
        if (feeSelect) feeSelect.selectedIndex = 0;
        if (budgetSelect) budgetSelect.selectedIndex = 0;
    });
    if (teacherSelect) teacherSelect.addEventListener('change', function () {
        if (feeSelect) feeSelect.selectedIndex = 0;
        if (budgetSelect) budgetSelect.selectedIndex = 0;
    });
};
function resetFilters() {
    window.location.href = window.location.pathname;
}
</script>

<div id="filterSidebar" class="sidebar">
  <form method="get">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Finance Report</h5>
      <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#settingsModal">
        Settings
      </button>
    </div>
    <div class="form-group">
        <label>School Year</label>
        <select name="schoolYear" class="form-control" onchange="this.form.submit()">
            <option value="">-- Select Year --</option>
            <?php
            $schoolYears = $pdo->query("SELECT gibbonSchoolYearID AS id, name FROM gibbonSchoolYear ORDER BY sequenceNumber DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($schoolYears as $year) {
                $selected = ($year['id'] == $selectedSchoolYear) ? 'selected' : '';
                echo "<option value='".safe($year['id'])."' $selected>".safe($year['name'])."</option>";
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label>From</label>
        <input type="date" name="startDate" value="<?= safe($from) ?>" class="form-control">
    </div>
    <div class="form-group">
        <label>To</label>
        <input type="date" name="endDate" value="<?= safe($to) ?>" class="form-control">
    </div>
    <div class="form-group">
        <label>Class</label>
        <select name="class" class="form-control">
            <option value="">-- Select Class --</option>
            <?php foreach ($classList as $c): ?>
                <option value="<?= safe($c) ?>" <?= $class === $c ? 'selected' : '' ?>><?= safe($c) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="form-group">
        <label>Teacher</label>
        <select name="teacher" class="form-control">
            <option value="">-- Select Teacher --</option>
            <?php foreach ($teacherList as $t): ?>
                <option value="<?= safe($t['id']) ?>" <?= $teacher === $t['id'] ? 'selected' : '' ?>><?= safe($t['name']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="form-group">
        <label>Fee Category</label>
        <select name="feeCategory" class="form-control">
            <option value="">-- None --</option>
            <option value="*all" <?= $feeType === '*all' ? 'selected' : '' ?>>All Fees</option>
            <?php foreach ($feeCategories as $f): ?>
                <option value="<?= safe($f['id']) ?>" <?= $feeType === $f['id'] ? 'selected' : '' ?>><?= safe($f['name']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="form-group">
        <label>Budget Category</label>
        <select name="budgetCategory" class="form-control">
            <option value="">-- None --</option>
            <option value="*all" <?= $expenseType === '*all' ? 'selected' : '' ?>>All Budgets</option>
            <?php foreach ($budgetCategories as $b): ?>
                <option value="<?= safe($b) ?>" <?= $expenseType === $b ? 'selected' : '' ?>><?= safe($b) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-block">View</button>
    <button type="button" class="btn btn-light btn-block mt-2" onclick="resetFilters()">Reset</button>
  </form>
</div>

<div class="content container-narrow">
<?php if (isset($_GET['saved']) && $_GET['saved']=='1'): ?>
  <div class="alert alert-success py-2">Settings saved.</div>
<?php elseif (isset($_GET['saved']) && $_GET['saved']=='0'): ?>
  <div class="alert alert-danger py-2">Failed to save settings.</div>
<?php endif; ?>

<?php if (!empty($teacherMismatch)): ?>
  <div class="alert alert-danger mt-4">
    The selected teacher does not teach this class in the chosen school year.
  </div>
<?php endif; ?>

<?php if ($ketToan): ?>
  <div class="alert alert-warning mt-4 sticky-top" style="z-index: 1000;">
    <h5 class="mb-3">Teacher Financial Summary</h5>
    <ul>
        <li><strong>Total Income:</strong> <?= number_format($ketToan['tongThu'], 0, ',', '.') ?> VND</li>
        <li><strong>Center Fee (<?= safe($APP_SETTINGS['centerFeePercent'] ?? 20) ?>%):</strong> <?= number_format($ketToan['phiTrungTam'], 0, ',', '.') ?> VND</li>
        <li><strong>*Hold &amp; Collect Amount:</strong> <?= number_format($ketToan['thuVaGiu'], 0, ',', '.') ?> VND</li>
        <li><strong>Advance Paid:</strong> <?= number_format($ketToan['ungLuong'], 0, ',', '.') ?> VND</li>
        <li><strong><u>Remaining Payment for Teacher:</u></strong> <?= number_format($ketToan['tongThu'] - $ketToan['phiTrungTam'] - $ketToan['ungLuong'] - $ketToan['thuVaGiu'] , 0, ',', '.') ?> VND</li>
    </ul>
  </div>
<?php endif; ?>

<?php if ($from && $to): ?>
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted">Total records: <?= $totalRows ?></div>
    <?php if ($totalPages > 1): ?>
      <?php
        $queryBase = $_GET;
        $prevPage = max(1, $page - 1);
        $nextPage = min($totalPages, $page + 1);
        $buildUrl = function($p) use ($queryBase) {
            $queryBase['page'] = $p;
            return safe($_SERVER['PHP_SELF']).'?'.http_build_query($queryBase);
        };
      ?>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $buildUrl($prevPage) ?>">«</a></li>
          <li class="page-item disabled"><span class="page-link"><?= $page ?>/<?= $totalPages ?></span></li>
          <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= $buildUrl($nextPage) ?>">»</a></li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <?php
    // slice data for current page
    $offset = ($page - 1) * $perPage;
    $pageData = array_slice($data, $offset, $perPage);
  ?>

  <div style="max-height: 65vh; overflow-y: auto;">
    <table class="table table-bordered table-hover mt-3">
        <thead class="thead-light">
            <tr>
                <th style="width:90px;">Type</th>
                <th style="width:110px;">ID</th>
                <th class="text-center-important">Description</th>
                <th style="width:140px;" class="text-center-important">Amount</th>
                <th style="width:140px;" class="text-center-important">Issued Date</th>
                <th style="width:160px;" class="text-center-important">Completed Date</th>
                <th style="width:220px;">Category</th>
                <th style="min-width:240px;">Note</th>
            </tr>
        </thead>
        <tbody>
          <?php foreach ($pageData as $row): ?>
              <?php
                  $type = $row['type'] ?? '';
                  $note = $row['note'] ?? ($row['name'] ?? '');
                  $baseClass = $type === 'Expense' ? 'text-danger' : 'text-success';
                  $rowClass = $baseClass;

                  // conservative AI calls
                  if ($type === 'Expense') {
                      if (!isset($noteToTeacherMap[$note]) && !empty($teacherNames)) {
                          $noteToTeacherMap[$note] = detectTeacherNameFromNote($note, $teacherNames);
                      }
                      $identifiedTeacher = $noteToTeacherMap[$note] ?? 'Unknown';
                      if ($identifiedTeacher !== 'Unknown' && !isset($cacheAdvance[$note])) {
                          $cacheAdvance[$note] = strtolower(trim(checkSalaryNote($note, $identifiedTeacher)));
                      }
                      if (isset($cacheAdvance[$note]) && preg_replace('/[^a-z]/i', '', $cacheAdvance[$note]) === 'yes') {
                          $rowClass .= ' bg-warning'; // advance
                      }
                  } else { // Income
                      if (!isset($cacheHeld[$note])) {
                          $cacheHeld[$note] = strtolower(trim(checkHeldMoney($note)));
                      }
                      if (preg_replace('/[^a-z]/i', '', $cacheHeld[$note]) === 'yes') {
                          $rowClass .= ' bg-success text-white'; // hold & collect
                      }
                  }
              ?>
              <tr class="<?= $rowClass ?>">
                  <td><?= safe($type) ?></td>
                  <td><?= safe($row['recordID'] ?? '') ?></td>
                  <td class="text-center-important"><?= safe($row['name']) ?></td>
                  <td class="text-center-important"><?= number_format((float)$row['amount'], 0, ',', '.') ?></td>
                  <td class="text-center-important">
                      <?= isset($row['issueDate']) && $row['issueDate'] ? date('d/m/Y', strtotime($row['issueDate'])) : '<em class="text-muted">None</em>' ?>
                  </td>
                  <td class="text-center-important">
                      <?= isset($row['date']) && $row['date'] ? date('d/m/Y', strtotime($row['date'])) : '<em class="text-muted">None</em>' ?>
                  </td>
                  <td><?= safe($row['category']) ?></td>
                  <td><?= $row['note'] ? nl2br(html_entity_decode(safe($row['note']))) : '<em class="text-muted">None</em>' ?></td>
              </tr>
          <?php endforeach; ?>
        </tbody>
    </table>
  </div>
<?php endif; ?>
</div>

<?php
$teacherNamesText = implode("\n", $APP_SETTINGS['teacherNames'] ?? []);
$detectTpl  = $APP_SETTINGS['prompts']['detectTeacher'] ?? '';
$advanceTpl = $APP_SETTINGS['prompts']['advance'] ?? '';
$heldTpl    = $APP_SETTINGS['prompts']['held'] ?? '';
$centerPct  = (int)($APP_SETTINGS['centerFeePercent'] ?? 20);
?>
<!-- Settings Modal (English) -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalLabel">Financial Summary - Settings</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <?php if (isset($_GET['saved'])): ?>
          <div class="alert alert-<?= $_GET['saved']=='1'?'success':'danger' ?> py-2">
            <?= $_GET['saved']=='1' ? 'Settings saved successfully.' : 'Failed to save settings file.' ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label>Center Fee (%)</label>
          <input type="number" name="centerFeePercent" class="form-control" min="0" max="100" value="<?= safe($centerPct) ?>">
          <small class="text-muted">Percentage of total income kept by the center when calculating payouts.</small>
        </div>

        <div class="form-group">
          <label>Teacher Names (one per line)</label>
          <textarea name="teacherNames" class="form-control" rows="6" placeholder="E.g. John Smith&#10;Jane Doe"><?= safe($teacherNamesText) ?></textarea>
          <small class="text-muted">This list is used by the AI to detect teachers in payment notes. The teacher dropdown is still populated from the database.</small>
        </div>

        <hr>
        <h6 class="mb-2">Prompt Templates</h6>
        <p class="text-muted small mb-3">
          You can use variables: <code>{{TEACHER_LIST}}</code>, <code>{{NOTE}}</code>, <code>{{TEACHER_NAME}}</code>.
        </p>

        <div class="form-group">
          <label>Prompt - Detect Teacher from Note</label>
          <textarea name="prompt_detectTeacher" class="form-control" rows="7"><?= safe($detectTpl) ?></textarea>
        </div>
        <div class="form-group">
          <label>Prompt - Detect Advance Payment</label>
          <textarea name="prompt_advance" class="form-control" rows="6"><?= safe($advanceTpl) ?></textarea>
        </div>
        <div class="form-group mb-0">
          <label>Prompt - Detect "Hold &amp; Collect"</label>
          <textarea name="prompt_held" class="form-control" rows="6"><?= safe($heldTpl) ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <input type="hidden" name="save_settings" value="1">
        <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Settings</button>
      </div>
    </form>
  </div>
</div>
