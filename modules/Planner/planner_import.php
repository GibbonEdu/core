<?php
use Gibbon\Forms\Form;

require_once '../../gibbon.php';

if (!isActionAccessible($guid, $connection2, '/modules/Planner/planner_import.php')) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$page->breadcrumbs
    ->add(__('Planner'))
    ->add(__('Bulk Import Lessons'));

$form = Form::create(
    'plannerImport',
    $session->get('absoluteURL').'/modules/Planner/planner_importProcess.php'
);

$form->setClass('w-full max-w-3xl');

// Template download
$row = $form->addRow();
$row->addLabel('template', __('Download Template'));
$row->addContent(
    '<a class="button" href="'.$session->get('absoluteURL').'/modules/Planner/planner_import.php?template=1">'.
    __('Download CSV Template').
    '</a>'
);

// CSV upload
$row = $form->addRow();
$row->addLabel('file', __('Upload CSV File'));
$row->addFileUpload('file')->required();

// Submit
$row = $form->addRow();
$row->addSubmit();

echo $form->getOutput();

// Handle template download
if (isset($_GET['template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="planner_template.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['date', 'timeStart', 'timeEnd', 'course', 'class', 'name', 'description']);
    fclose($out);
    exit;
}
