<?php
require_once '../../gibbon.php';

if (!isActionAccessible($guid, $connection2, '/modules/Planner/planner_import.php')) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$file = $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $page->addError(__('File upload failed.'));
    header('Location: '.$session->get('absoluteURL').'/modules/Planner/planner_import.php');
    exit;
}

// Read CSV
$rows = array_map('str_getcsv', file($file['tmp_name']));
$headers = array_shift($rows);

// Load hooks
$hookGateway = $container->get(\Gibbon\Domain\System\HookGateway::class);
$hookRows = $hookGateway->selectBy(['type' => 'Lesson Planner'])->fetchAll();

$hooks = [];
foreach ($hookRows as $hookRow) {
    require_once $hookRow['file'];
    $class = $hookRow['class'];
    $hooks[] = new $class($pdo);
}

// Custom fields
$customFieldHandler = $container->get(\Gibbon\Forms\CustomFieldHandler::class);

$imported = 0;
$errors = [];

foreach ($rows as $rowNumber => $row) {
    $data = array_combine($headers, $row);

    // --- 1. Resolve class ---
    $gibbonCourseClassID = resolveClass($connection2, $data['course'] ?? '', $data['class'] ?? '');
    if (!$gibbonCourseClassID) {
        $errors[] = "Row ".($rowNumber+2).": Invalid course/class";
        continue;
    }

    // --- 2. Required fields ---
    $date = $data['date'] ?? '';
    $timeStart = $data['timeStart'] ?? '';
    $timeEnd = $data['timeEnd'] ?? '';
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (!$date || !$timeStart || !$timeEnd || !$name) {
        $errors[] = "Row ".($rowNumber+2).": Missing required fields";
        continue;
    }

    // --- 3. Custom fields ---
    $fields = $customFieldHandler->getFieldDataFromArray('Lesson Plan', $data);

    // --- 4. Insert lesson ---
    try {
        $insertData = [
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'date' => $date,
            'timeStart' => $timeStart,
            'timeEnd' => $timeEnd,
            'name' => $name,
            'description' => $description,
            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
            'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'),
            'fields' => $fields,
        ];

        $sql = 'INSERT INTO gibbonPlannerEntry SET
            gibbonCourseClassID=:gibbonCourseClassID,
            date=:date,
            timeStart=:timeStart,
            timeEnd=:timeEnd,
            name=:name,
            description=:description,
            gibbonPersonIDCreator=:gibbonPersonIDCreator,
            gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit,
            fields=:fields';

        $stmt = $connection2->prepare($sql);
        $stmt->execute($insertData);

        $gibbonPlannerEntryID = $connection2->lastInsertId();

        // --- 5. Process hooks ---
        foreach ($hooks as $hook) {
            $hook->process($gibbonPlannerEntryID, $data);
        }

        $imported++;

    } catch (PDOException $e) {
        $errors[] = "Row ".($rowNumber+2).": Database error";
        continue;
    }
}

// --- 6. Report results ---
if (!empty($errors)) {
    foreach ($errors as $err) {
        $page->addError($err);
    }
}

$page->addMessage(sprintf(__('Imported %1$s lessons.'), $imported));

header('Location: '.$session->get('absoluteURL').'/modules/Planner/planner_import.php');
exit;


// ------------------------------------------------------------
// Helper: resolve course/class
// ------------------------------------------------------------
function resolveClass($connection2, $courseShort, $classShort)
{
    if (!$courseShort || !$classShort) {
        return false;
    }

    $sql = "SELECT gibbonCourseClassID
            FROM gibbonCourseClass
            JOIN gibbonCourse USING (gibbonCourseID)
            WHERE gibbonCourse.nameShort = :course
            AND gibbonCourseClass.nameShort = :class";

    $stmt = $connection2->prepare($sql);
    $stmt->execute([
        'course' => $courseShort,
        'class' => $classShort,
    ]);

    return $stmt->fetchColumn() ?: false;
}
