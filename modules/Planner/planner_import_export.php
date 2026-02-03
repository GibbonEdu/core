<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

$_POST['address'] = '/modules/Planner/planner_import.php';

// System-wide include
include '../../gibbon.php';

$action = $_GET['action'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_import.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') == false) {
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
}

if ($action === 'downloadTemplate') {
    try {
        // Load the helper functions from planner_importProcess.php without executing it
        // Extract just the function definitions
        $processFile = file_get_contents(__DIR__ . '/planner_importProcess.php');
        
        // Extract helper function definitions
        preg_match('/function getPlannerColumns\(.*?\n\}/s', $processFile, $matches);
        if (!empty($matches)) eval($matches[0]);
        
        preg_match('/function getCustomFieldMap\(.*?\n\}/s', $processFile, $matches);
        if (!empty($matches)) eval($matches[0]);
        
        preg_match('/function getHookFields\(.*?\n\}/s', $processFile, $matches);
        if (!empty($matches)) eval($matches[0]);
        
        preg_match('/function getHeaderLookup\(.*?\n    \}\n\}/s', $processFile, $matches);
        if (!empty($matches)) eval($matches[0]);
        
        error_log('[Planner Import Export] Starting template generation');
        
        $core = getPlannerColumns($pdo);
        error_log('[Planner Import Export] Got core columns: ' . count($core));
        
        $lookup = getHeaderLookup($container, $pdo);
        error_log('[Planner Import Export] Got header lookup');
        
        $dbToUser = $lookup['dbToUser'];
        $coreOrder = $lookup['coreOrder'];
        $customMap = $lookup['customMap'];
        $hookFields = $lookup['hookFields'];

        // Filter custom fields to only include active ones
        $customFieldGateway = $container->get(\Gibbon\Domain\System\CustomFieldGateway::class);
        $customMap = array_filter($customMap, function($field) {
            return ($field['active'] ?? 'Y') === 'Y';
        });

        // Build ordered headers using friendly labels - match planner_add.php form order
        $headers = [];
        $customInserted = false;
        foreach ($coreOrder as $db) {
            if (in_array($db, $core)) {
                $headers[] = $dbToUser[$db] ?? $db;

                // After the Lesson Name, insert all custom fields
                if (!$customInserted && $db === 'name') {
                    foreach ($customMap as $canonical => $info) {
                        $label = $info['name'] ?? $canonical;
                        $headers[] = $label;
                    }
                    $customInserted = true;
                }
            }
        }
        // Hook fields
        foreach ($hookFields as $header => $label) {
            $headers[] = $label;
        }

        error_log('[Planner Import Export] Generated headers: ' . json_encode($headers));

        // Generate example row
        $examples = [];
        foreach ($headers as $h) {
            switch ($h) {
                case 'Class': $examples[] = 'COURSE1.CLASSA'; break;
                case 'Date': $examples[] = date('Y-m-d'); break;
                case 'Start Time': $examples[] = '09:00'; break;
                case 'End Time': $examples[] = '10:00'; break;
                case 'Lesson Name': $examples[] = 'Introduction to Topic'; break;
                case 'Location': $examples[] = 'Room 101'; break;
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
                default: $examples[] = 'Sample value'; break;
            }
        }

        $filename = 'planner_template_'.date('Y-m-d').'.csv';

        // Output CSV directly
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
            error_log('[Planner Import Export] CSV generated successfully');
        }
        exit;
    } catch (Exception $e) {
        error_log('[Planner Import Export] ERROR: ' . $e->getMessage());
        $URL = $URL.'&return=error1';
        header("Location: {$URL}");
        exit;
    }
}

// If we get here, invalid action
$URL = $URL.'&return=error1';
header("Location: {$URL}");
exit;
