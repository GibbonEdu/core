<?php
// Export planner import template from session (Query Builder style)

include '../../gibbon.php';

$hash = $_GET['hash'] ?? '';
$data = $session->get($hash);
if (empty($data) || !is_array($data)) {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/planner_import.php&return=error1';
    header("Location: {$URL}");
    exit();
}

$headers = $data['headers'] ?? [];
$examples = $data['examples'] ?? [];
$filename = $data['filename'] ?? 'planner_template.csv';

// Remove session entry
$session->remove($hash);

// Stream CSV
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename).'"');

$out = fopen('php://output', 'w');
if ($out) {
    if (!empty($headers)) fputcsv($out, $headers);
    if (!empty($examples)) fputcsv($out, $examples);
    fclose($out);
}

exit;
