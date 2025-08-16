<?php
require_once __DIR__ . '/gibbon.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
$pdo = $connection2;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Copy Form Group</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 mb-5">
    <h2 class="mb-4">Copy Form Group</h2>
<?php

if (!isset($_POST['step'])) {
    $years = $pdo->query("
        SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear ORDER BY status DESC, sequenceNumber DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<form method='post' class='p-4 bg-white border rounded shadow-sm'>";
    echo "<input type='hidden' name='step' value='2'>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Source Year:</label>";
    echo "<select name='fromYearID' class='form-select' required>";
    echo "<option value=''>-- Select Year --</option>";
    foreach ($years as $year) {
        echo "<option value='{$year['gibbonSchoolYearID']}'>{$year['name']}</option>";
    }
    echo "</select></div>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Target Year:</label>";
    echo "<select name='toYearID' class='form-select' required>";
    echo "<option value=''>-- Select Year --</option>";
    foreach ($years as $year) {
        echo "<option value='{$year['gibbonSchoolYearID']}'>{$year['name']}</option>";
    }
    echo "</select></div>";
    echo "<button type='submit' class='btn btn-primary'>Next</button>";
    echo "</form>";
    exit;
}

if ($_POST['step'] == '2') {
    $fromYearID = $_POST['fromYearID'];
    $toYearID = $_POST['toYearID'];

    echo "<form method='post' class='p-4 bg-white border rounded shadow-sm'>";
    echo "<input type='hidden' name='step' value='3'>";
    echo "<input type='hidden' name='fromYearID' value='$fromYearID'>";
    echo "<input type='hidden' name='toYearID' value='$toYearID'>";

    $stmt = $pdo->prepare("
        SELECT gibbonFormGroupID, name, nameShort FROM gibbonFormGroup 
        WHERE gibbonSchoolYearID = :yearID ORDER BY name ASC
    ");
    $stmt->execute(['yearID' => $fromYearID]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Source Year ID:</strong> $fromYearID &nbsp;&nbsp; <strong>Target Year ID:</strong> $toYearID</p>";
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Select class to copy:</label>";
    echo "<select name='fromFormGroupID' class='form-select' required>";
    echo "<option value=''>-- Choose a class --</option>";
    foreach ($groups as $group) {
        echo "<option value='{$group['gibbonFormGroupID']}'>{$group['nameShort']} - {$group['name']}</option>";
    }
    echo "</select></div>";

    echo "<button type='submit' class='btn btn-success'>Copy Now</button>";
    echo "</form>";
    exit;
}

if ($_POST['step'] == '3') {
    $fromYearID = $_POST['fromYearID'];
    $toYearID = $_POST['toYearID'];
    $fromFormGroupID = $_POST['fromFormGroupID'];

    $stmt = $pdo->prepare("
        SELECT * FROM gibbonFormGroup
        WHERE gibbonFormGroupID = :id AND gibbonSchoolYearID = :yearID
    ");
    $stmt->execute(['id' => $fromFormGroupID, 'yearID' => $fromYearID]);
    $oldFormGroup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldFormGroup) {
        echo "<div class='alert alert-danger'>Selected class not found.</div>";
        echo "<a href='' class='btn btn-secondary'>Try Again</a>";
        exit;
    }

    $originalName = $oldFormGroup['name'];
    $originalShortName = $oldFormGroup['nameShort'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM gibbonFormGroup 
        WHERE nameShort = :nameShort AND gibbonSchoolYearID = :yearID
    ");
    $stmt->execute([
        'nameShort' => $originalShortName,
        'yearID' => $toYearID
    ]);
    $shortExists = $stmt->fetchColumn();

    if ($shortExists > 0) {
        echo "<div class='alert alert-warning'>A class with short name <strong>$originalShortName</strong> already exists in the target year. Copy aborted.</div>";
        echo "<a href='' class='btn btn-secondary'>Copy another class</a>";
        exit;
    }

    $newName = $originalName . ' (copy)';
    $oldFormGroup['name'] = $newName;
    $newGroup = $oldFormGroup;
    unset($newGroup['gibbonFormGroupID']);
    $newGroup['gibbonSchoolYearID'] = $toYearID;
    $cols = implode(', ', array_keys($newGroup));
    $placeholders = ':' . implode(', :', array_keys($newGroup));
    $stmt = $pdo->prepare("INSERT INTO gibbonFormGroup ($cols) VALUES ($placeholders)");
    $stmt->execute($newGroup);
    $newFormGroupID = $pdo->lastInsertId();

    echo "<div class='alert alert-success'>";
    echo "Successfully copied class <strong>$originalName</strong> as <strong>$newName</strong> (New ID: $newFormGroupID)<br>";

    $stmt = $pdo->prepare("
        SELECT gibbonPersonID, gibbonYearGroupID
        FROM gibbonStudentEnrolment
        WHERE gibbonFormGroupID = :formGroupID AND gibbonSchoolYearID = :yearID
    ");
    $stmt->execute([
        'formGroupID' => $fromFormGroupID,
        'yearID' => $fromYearID
    ]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($students as $student) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO gibbonStudentEnrolment 
            (gibbonSchoolYearID, gibbonPersonID, gibbonFormGroupID, gibbonYearGroupID)
            VALUES (:yearID, :personID, :formGroupID, :yearGroupID)
        ");
        $stmtInsert->execute([
            'yearID' => $toYearID,
            'personID' => $student['gibbonPersonID'],
            'formGroupID' => $newFormGroupID,
            'yearGroupID' => $student['gibbonYearGroupID']
        ]);
        $count++;
    }

    echo "<strong>$count</strong> students copied successfully.</div>";
    echo "<a href='' class='btn btn-primary mt-3'>Copy another class</a>";
}
?>
</div>
</body>
</html>
