<?php
require_once __DIR__ . '/moduleFunctions.php';

// Redirect target for errors/success (process scripts should redirect)
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/planner_import.php';

// Use planner_edit permission as the guard so import processing follows edit access
if (!isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
}

$action = $_GET['action'] ?? ''; 

// Debug endpoint: output discovered custom fields and aliases
if ($action === 'debugFields') {
    $cf = getCustomFieldMap($container);
    header('Content-Type: application/json');
    echo json_encode($cf, JSON_PRETTY_PRINT);
    exit;
}

// Helper: discover core planner columns
function getPlannerColumns($pdo)
{
    global $pdo;
    $cols = [];
    try {
        $stmt = $pdo->select("DESCRIBE gibbonPlannerEntry");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) $cols[] = $row['Field'];
    } catch (Exception $e) {
        // If we can't query, just return the default core order columns
        return ['gibbonUnitID','date','timeStart','timeEnd','name','summary','description','teachersNotes','gibbonSpaceID',
                'homework','homeworkDueDateTime','homeworkTimeCap','homeworkDetails','homeworkSubmission','homeworkSubmissionDateOpen',
                'homeworkSubmissionDrafts','homeworkSubmissionType','homeworkSubmissionRequired','viewableStudents','viewableParents'];
    }
    $exclude = ['gibbonPlannerEntryID', 'dateCreated', 'dateModified', 'createdBy', 'modifiedBy', 'dateDeleted'];
    // Use a blacklist: return all planner columns except internal/system fields so future fields
    // are automatically included in the template/import unless explicitly excluded above.
    $cols = array_values(array_diff($cols, $exclude));
    return $cols;
}

// Helper: discover Lesson Plan custom fields (returns header => id)
function getCustomFieldMap($container)
{
    $customFieldGateway = $container->get(\Gibbon\Domain\System\CustomFieldGateway::class);
    $map = [];
    $aliases = [];

    try {
        $grouped = $customFieldGateway->selectCustomFields('Lesson Plan')->fetchGrouped();
    } catch (Exception $e) {
        $grouped = [];
    }

    $excludeTypes = ['file', 'image', 'link', 'url', 'attachment'];

    foreach ($grouped as $heading => $fields) {
        if (empty($fields) || !is_array($fields)) continue;
        foreach ($fields as $f) {
            $fieldType = strtolower($f['type'] ?? 'text');
            if (in_array($fieldType, $excludeTypes)) continue;

            $context = $f['context'] ?? 'custom';
            $contextSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($context));
            $nameSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($f['name'] ?? ''));
            $canonical = $contextSlug.'_custom_'.$f['gibbonCustomFieldID'].'_'.($nameSlug ?: $f['gibbonCustomFieldID']);

            $map[$canonical] = [
                'id' => $f['gibbonCustomFieldID'],
                'type' => $f['type'] ?? 'text',
                'name' => $f['name'] ?? '',
                'context' => $context,
            ];

            $simple = preg_replace('/[^a-z0-9]+/', '_', strtolower($f['name'] ?? ''));
            $aliases[$simple] = $canonical;
            $aliases[strtolower($f['name'] ?? '')] = $canonical;
            $aliases[$contextSlug.'_'.$simple] = $canonical;
            $aliases[str_replace('_', ' ', $simple)] = $canonical;
            $aliases[preg_replace('/[^a-z0-9]+/', '', strtolower($f['name'] ?? ''))] = $canonical;
        }
    }

    // Do NOT include a fallback for inactive fields - selectCustomFields() already filters by active='Y'
    // This ensures the CSV template only includes fields that are currently active
    
    return ['map' => $map, 'aliases' => $aliases];

}

// Helper: probe accessible Lesson Planner hooks for import fields
function getHookFields($container)
{
    global $guid, $connection2, $session, $page;

    $hookGateway = $container->get(\Gibbon\Domain\System\HookGateway::class);
    $hooks = $hookGateway->getAccessibleHooksByType('Lesson Planner', $container->get('session')->get('gibbonRoleIDCurrent'));
    $hookFields = [];

    foreach ($hooks as $hook) {
        $include = $container->get('session')->get('absolutePath').'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
        if (!file_exists($include)) continue;

        // Skip hooks that are designed for viewing (not importing)
        // They won't provide import fields and can cause fatal errors
        if (strpos($hook['sourceModuleInclude'], 'hook_lessonPlannerView') !== false) {
            continue;
        }

        ob_start();
        try {
            include $include;
        } catch (Exception $e) {
            // ignore
        } catch (Throwable $e) {
            // catch fatal errors and other issues
        }
        ob_end_clean();

        $fn = $hook['sourceModuleName'].'_lessonPlannerImportFields';
        if (function_exists($fn)) {
            try {
                $fields = $fn();
                if (is_array($fields)) {
                    foreach ($fields as $key => $label) {
                        $header = 'hook_'.$hook['sourceModuleName'].'_'.$key;
                        $hookFields[$header] = $label;
                    }
                }
            } catch (Throwable $e) {
                // ignore errors from hook function
            }
            continue;
        }

        if (isset($lessonPlannerImportFields) && is_array($lessonPlannerImportFields)) {
            foreach ($lessonPlannerImportFields as $key => $label) {
                $header = 'hook_'.$hook['sourceModuleName'].'_'.$key;
                $hookFields[$header] = $label;
            }
            unset($lessonPlannerImportFields);
            continue;
        }
    }

    return $hookFields;
}

// Build a simple lookup table mapping DB/internal fields to user-facing labels
function getHeaderLookup($container, $pdo)
{
    // Core DB -> User labels (ordered)
    $coreOrder = [
        'gibbonUnitID','date','timeStart','timeEnd','name','summary','description','teachersNotes','gibbonSpaceID',
        'homework','homeworkDueDateTime','homeworkTimeCap','homeworkDetails','homeworkSubmission','homeworkSubmissionDateOpen',
        'homeworkSubmissionDrafts','homeworkSubmissionType','homeworkSubmissionRequired','viewableStudents','viewableParents',
    ];
    $dbToUser = [
        'gibbonCourseClassID' => 'Class',
        'gibbonUnitID' => 'Unit',
        'date' => 'Date',
        'timeStart' => 'Start Time',
        'timeEnd' => 'End Time',
        'name' => 'Lesson Name',
        'gibbonSpaceID' => 'Location',
        'summary' => 'Summary',
        'description' => 'Lesson Details',
        'teachersNotes' => "Teacher's Notes",
        'homework' => 'Add Homework',
        'homeworkDueDateTime' => 'Homework Due Date/Time',
        'homeworkTimeCap' => 'Homework Time Cap',
        'homeworkDetails' => 'Homework Details',
        'homeworkSubmission' => 'Online Submission',
        'homeworkSubmissionDateOpen' => 'Submission Open Date',
        'homeworkSubmissionDrafts' => 'Submission Drafts',
        'homeworkSubmissionType' => 'Submission Type',
        'homeworkSubmissionRequired' => 'Submission Required',
        'viewableStudents' => 'Viewable by Students',
        'viewableParents' => 'Viewable by Parents',
    ];

    // Custom fields and hooks
    $cf = getCustomFieldMap($container);
    $customMap = $cf['map'] ?? [];
    $customAliases = $cf['aliases'] ?? [];
    $hookFields = getHookFields($container);

    // Build userToDb by using friendly labels for custom fields
    $userToDb = [];
    foreach ($coreOrder as $db) {
        if (isset($dbToUser[$db])) $userToDb[$dbToUser[$db]] = $db;
    }

    foreach ($customMap as $canonical => $info) {
        $label = $info['name'] ?? $canonical;
        // Avoid collisions with core labels
        $label = $label ?: $canonical;
        $userToDb[$label] = $canonical;
        // also accept simple slug forms as labels
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
        $userToDb[$slug] = $canonical;
        $userToDb[strtolower($label)] = $canonical;
    }

    foreach ($hookFields as $header => $label) {
        $userToDb[$label] = $header; // header like hook_Module_key
        $userToDb[strtolower($label)] = $header;
        $userToDb[preg_replace('/[^a-z0-9]+/', '_', strtolower($label))] = $header;
    }

    return [
        'dbToUser' => $dbToUser,
        'userToDb' => $userToDb,
        'customMap' => $customMap,
        'customAliases' => $customAliases,
        'hookFields' => $hookFields,
        'coreOrder' => $coreOrder,
    ];
}

// Template download: core + custom + hook fields
if ($action === 'downloadTemplate') {
    try {
        $core = getPlannerColumns($pdo);
        
        $lookup = getHeaderLookup($container, $pdo);
        
        $dbToUser = $lookup['dbToUser'];
        $userToDb = $lookup['userToDb'];
        $customMap = $lookup['customMap'];
        $customAliases = $lookup['customAliases'];
        $hookFields = $lookup['hookFields'];
        $coreOrder = $lookup['coreOrder'];

        // Build ordered headers using friendly labels
        // Insert all custom fields immediately after `name` (Lesson Name) and before `summary`.
        $headers = [];
        $customInserted = false;
        foreach ($coreOrder as $db) {
            if (in_array($db, $core)) {
                $headers[] = $dbToUser[$db] ?? $db;

                // After the Lesson Name, insert all custom fields (friendly labels)
                if (!$customInserted && $db === 'name') {
                    foreach ($customMap as $canonical => $info) {
                        $label = $info['name'] ?? $canonical;
                        $headers[] = $label;
                    }
                    $customInserted = true;
                }
            }
        }
        // Hook fields (labels)
        foreach ($hookFields as $header => $label) {
            $headers[] = $label;
        }

        $examples = [];
        foreach ($headers as $h) {
            // Generate actual example values (not just format hints)
            switch ($h) {
                case 'Class': $examples[] = 'COURSE1.CLASSA'; break;
                case 'Date': $examples[] = date('Y-m-d'); break;
                case 'Start Time': $examples[] = '09:00'; break;
                case 'End Time': $examples[] = '10:00'; break;
                case 'Lesson Name': $examples[] = 'Introduction to Topic'; break;
                case 'Location': $examples[] = ''; break;
                case 'Unit': $examples[] = '1'; break;
                case 'Summary': $examples[] = 'Key learning objectives'; break;
                case 'Lesson Details': $examples[] = 'Detailed content and activities'; break;
                case "Teacher's Notes": $examples[] = 'Notes for preparation'; break;
                case 'Add Homework': $examples[] = 'Y'; break;
                case 'Homework Due Date/Time': $examples[] = date('Y-m-d H:i:s'); break;
                case 'Homework Time Cap': $examples[] = '30'; break;
                case 'Homework Details': $examples[] = 'Complete exercises 1-5'; break;
                case 'Online Submission': $examples[] = 'Y'; break;
                case 'Submission Open Date': $examples[] = date('Y-m-d'); break;
                case 'Submission Drafts': $examples[] = '2'; break;
                case 'Submission Type': $examples[] = 'Link'; break;
                case 'Submission Required': $examples[] = 'Y'; break;
                case 'Viewable by Students': $examples[] = 'Y'; break;
                case 'Viewable by Parents': $examples[] = 'Y'; break;
                default:
                    // Custom fields: use type to generate appropriate example
                    $type = null;
                    $fieldId = null;
                    
                    // Resolve custom field type
                    $internal = $userToDb[strtolower($h)] ?? $userToDb[$h] ?? null;
                    if ($internal && isset($customMap[$internal])) {
                        $type = strtolower($customMap[$internal]['type'] ?? 'text');
                    } else {
                        // Try by field name in custom map
                        foreach ($customMap as $canonical => $info) {
                            if (strtolower($info['name'] ?? '') === strtolower($h)) {
                                $type = strtolower($info['type'] ?? 'text');
                                break;
                            }
                        }
                    }

                    // Generate example based on field type
                    switch ($type) {
                        case 'date': $examples[] = date('Y-m-d'); break;
                        case 'time':
                        case 'timeofday': $examples[] = '14:00'; break;
                        case 'number': $examples[] = '42'; break;
                        case 'checkbox':
                        case 'checkboxes':
                        case 'yesno': $examples[] = 'Y'; break;
                        case 'select':
                        case 'dropdown': $examples[] = 'Option 1'; break;
                        default: $examples[] = 'Sample value'; break;
                    }
            }
        }

        // Prepend Class column (accepts COURSE.CLASS format like FL07.1)
        array_unshift($headers, 'Class');
        array_unshift($examples, 'COURSE1.CLASSA');

        $filename = 'planner_template_'.date('Y-m-d').'.csv';

        // Output CSV directly (Gibbon pattern)
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename).'"');

        $out = fopen('php://output', 'w');
        if ($out) {
            fputcsv($out, $headers);
            fputcsv($out, $examples);
            fclose($out);
        }
        exit;
    } catch (Exception $e) {
        $_SESSION['planner_import_errors'] = [__('Template download failed: ') . $e->getMessage()];
        header('Location: '.$URL.'&return=error1');
        exit;
    } catch (Throwable $e) {
        $_SESSION['planner_import_errors'] = [__('Template download failed: ') . $e->getMessage()];
        header('Location: '.$URL.'&return=error1');
        exit;
    }
}

// Otherwise: fall back to processing upload (existing behaviour)

// Expect uploaded file field 'file'
$file = $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['planner_import_errors'] = [__('File upload failed.')];
    header('Location: '.$URL);
    exit;
}

// Read CSV
$rows = array_map('str_getcsv', file($file['tmp_name']));
$headers = array_shift($rows);

// Build lookup for import mapping
$lookup = getHeaderLookup($container, $pdo);
$userToDb = $lookup['userToDb'];
$customMap = $lookup['customMap'];
$customAliases = $lookup['customAliases'];
$hookFields = $lookup['hookFields'];

$imported = 0;
$errors = [];

foreach ($rows as $rowNumber => $row) {
    // Skip empty rows
    if (count($row) === 1 && trim($row[0]) === '') continue;

    // Ensure row has same number of columns as headers
    $numHeaders = count($headers);
    $numRow = count($row);
    if ($numRow < $numHeaders) {
        // Pad missing columns with empty strings
        $row = array_pad($row, $numHeaders, '');
        $errors[] = "Row ".($rowNumber+2).": Column count (got {$numRow}, expected {$numHeaders}) - padded missing values";
    } elseif ($numRow > $numHeaders) {
        // Truncate extra columns
        $row = array_slice($row, 0, $numHeaders);
        $errors[] = "Row ".($rowNumber+2).": Column count (got {$numRow}, expected {$numHeaders}) - truncated extra values";
    }

    // Combine headers with row data
    $rowData = array_combine($headers, $row);

    // Build reverse-mapped data: use userToDb to extract values by their internal keys
    $data = [];
    foreach ($rowData as $incomingHeader => $value) {
        if ($value === '' || $value === null) continue;

        // Parse Class field (COURSE.CLASS format) into course and class
        $internalKey = null;
        if (strtolower($incomingHeader) === 'class') {
            // Split "COURSE.CLASS" format
            if (strpos($value, '.') !== false) {
                [$courseCode, $classCode] = explode('.', $value, 2);
                $data['course'] = trim($courseCode);
                $data['class'] = trim($classCode);
            } else {
                $errors[] = "Row ".($rowNumber+2).": Class must be in format COURSE.CLASS (e.g., FL07.1)";
                continue 2; // Skip to next row
            }
            continue;
        } else {
            // Try userToDb lookup first (for friendly labels and internal keys)
            $internalKey = $userToDb[strtolower($incomingHeader)] ?? $userToDb[$incomingHeader] ?? null;
            if (!$internalKey) {
                // Try case-insensitive match on custom field names
                $norm = preg_replace('/[^a-z0-9]+/', '_', strtolower($incomingHeader));
                $internalKey = $customAliases[$norm] ?? null;
            }
        }

        if ($internalKey) {
            $data[$internalKey] = $value;
        }
    }

    // --- 1. Resolve class ---
    $gibbonCourseClassID = resolveClass($connection2, $data['course'] ?? '', $data['class'] ?? '');
    if (!$gibbonCourseClassID) {
        $errors[] = "Row ".($rowNumber+2).": Invalid course/class";
        continue;
    }

    // --- 2. Extract and validate required fields ---
    $date = $data['date'] ?? '';
    $timeStart = $data['timeStart'] ?? '';
    $timeEnd = $data['timeEnd'] ?? '';
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (!$date || !$timeStart || !$timeEnd || !$name) {
        $errors[] = "Row ".($rowNumber+2).": Missing required fields (date, timeStart, timeEnd, name)";
        continue;
    }

    // --- 3. Extract custom fields and normalize values ---
    $fieldsData = [];
    foreach ($customMap as $canonical => $info) {
        $id = $info['id'];
        $type = strtolower($info['type'] ?? 'text');

        // Try to find the value by canonical key or by aliases
        $value = null;
        if (isset($data[$canonical])) {
            $value = $data[$canonical];
        } else {
            // Try by field name
            if (isset($data[$info['name']])) {
                $value = $data[$info['name']];
            }
        }

        if ($value === '' || $value === null) continue;

        // Normalize checkbox/yesno values to 'Include'
        if (in_array($type, ['checkboxes', 'yesno'])) {
            $valNorm = trim(strtolower($value));
            $fieldsData[$id] = in_array($valNorm, ['y', 'yes', '1', 'true']) ? 'Include' : $value;
        } else {
            $fieldsData[$id] = $value;
        }
    }

    // --- 4. Extract optional fields and normalize enums ---
    $summary = $data['summary'] ?? '';
    $teachersNotes = $data['teachersNotes'] ?? '';
    $gibbonSpaceID = resolveSpace($connection2, $data['gibbonSpaceID'] ?? '');
    $homework = normalizeEnum($data['homework'] ?? '', ['N', 'Y'], 'N');
    $homeworkDueDateTime = $data['homeworkDueDateTime'] ?? '';
    $homeworkDetails = $data['homeworkDetails'] ?? '';
    $homeworkSubmission = normalizeEnum($data['homeworkSubmission'] ?? '', ['N', 'Y'], 'N');
    $homeworkSubmissionRequired = normalizeEnumYesNo($data['homeworkSubmissionRequired'] ?? '', 'Optional', 'Required');
    $homeworkSubmissionType = normalizeEnum($data['homeworkSubmissionType'] ?? '', ['', 'Link', 'File', 'Link/File'], '');
    $viewableStudents = normalizeEnum($data['viewableStudents'] ?? '', ['Y', 'N'], 'Y');
    $viewableParents = normalizeEnum($data['viewableParents'] ?? '', ['Y', 'N'], 'N');

    // --- 5. Insert lesson ---
    try {
        $insertData = [
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'date' => $date,
            'timeStart' => $timeStart,
            'timeEnd' => $timeEnd,
            'name' => $name,
            'description' => $description,
            'summary' => $summary,
            'teachersNotes' => $teachersNotes,
            'gibbonSpaceID' => $gibbonSpaceID,
            'homework' => $homework,
            'homeworkDueDateTime' => $homeworkDueDateTime,
            'homeworkDetails' => $homeworkDetails,
            'homeworkSubmission' => $homeworkSubmission,
            'homeworkSubmissionRequired' => $homeworkSubmissionRequired,
            'homeworkSubmissionType' => $homeworkSubmissionType,
            'viewableStudents' => $viewableStudents,
            'viewableParents' => $viewableParents,
            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
            'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'),
            'fields' => !empty($fieldsData) ? json_encode($fieldsData) : null,
        ];

        $sql = 'INSERT INTO gibbonPlannerEntry SET
            gibbonCourseClassID=:gibbonCourseClassID,
            date=:date,
            timeStart=:timeStart,
            timeEnd=:timeEnd,
            name=:name,
            description=:description,
            summary=:summary,
            teachersNotes=:teachersNotes,
            gibbonSpaceID=:gibbonSpaceID,
            homework=:homework,
            homeworkDueDateTime=:homeworkDueDateTime,
            homeworkDetails=:homeworkDetails,
            homeworkSubmission=:homeworkSubmission,
            homeworkSubmissionRequired=:homeworkSubmissionRequired,
            homeworkSubmissionType=:homeworkSubmissionType,
            viewableStudents=:viewableStudents,
            viewableParents=:viewableParents,
            gibbonPersonIDCreator=:gibbonPersonIDCreator,
            gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit,
            fields=:fields';

        $stmt = $connection2->prepare($sql);
        $stmt->execute($insertData);

        $imported++;

    } catch (PDOException $e) {
        $errors[] = "Row ".($rowNumber+2).": Database error: ".$e->getMessage();
        continue;
    }
}

// --- 6. Report results ---
if (!empty($errors)) {
    $_SESSION['planner_import_errors'] = $errors;
}

$_SESSION['planner_import_message'] = sprintf(__('Imported %1$s lessons.'), $imported);

header('Location: '.$URL);
exit;


// ------------------------------------------------------------
// Helper: normalize enum values to valid DB options
// ------------------------------------------------------------
function normalizeEnum($value, $validOptions = [], $default = '')
{
    if (empty($value)) return $default;
    
    $normalized = strtolower(trim($value));
    foreach ($validOptions as $opt) {
        if (strtolower($opt) === $normalized) {
            return $opt;
        }
    }
    
    // If value doesn't match any option, return default
    return $default;
}

// Helper: map Y/N to custom enum values (e.g., Y → 'Required', N → 'Optional')
function normalizeEnumYesNo($value, $noValue = '', $yesValue = '')
{
    if (empty($value)) return $noValue;
    
    $normalized = strtolower(trim($value));
    if (in_array($normalized, ['y', 'yes', '1', 'true'])) {
        return $yesValue;
    } else {
        return $noValue;
    }
}

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

// Helper: resolve space name to gibbonSpaceID
function resolveSpace($connection2, $spaceName)
{
    if (!$spaceName) {
        return '';
    }

    // If it's already a number, assume it's a gibbonSpaceID
    if (is_numeric($spaceName)) {
        return $spaceName;
    }

    // Look up space by name
    $sql = "SELECT gibbonSpaceID FROM gibbonSpace WHERE name = :name";
    $stmt = $connection2->prepare($sql);
    $stmt->execute(['name' => trim($spaceName)]);

    return $stmt->fetchColumn() ?: '';
}
