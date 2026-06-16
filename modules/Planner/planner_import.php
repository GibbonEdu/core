<?php
/**
 * Planner Import UI
 */
require_once __DIR__ . '/moduleFunctions.php';

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

// Use planner_edit permission as the guard so import follows edit access
if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') == false) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$page->breadcrumbs
    ->add(__('Planner'), 'planner.php') 
    ->add(__('Bulk Import Lessons'));

// Display any import messages or errors stored in session BEFORE the form
$importErrors = $_SESSION['planner_import_errors'] ?? null;
if (!empty($importErrors) && is_array($importErrors)) {
    foreach ($importErrors as $err) {
        $page->addError($err);
    }
    unset($_SESSION['planner_import_errors']);
}

$importMsg = $_SESSION['planner_import_message'] ?? null;
if (!empty($importMsg)) {
    $page->addSuccess($importMsg);
    unset($_SESSION['planner_import_message']);
}

$form = Form::create('plannerImport', $session->get('absoluteURL').'/index.php?q=/modules/Planner/planner_importProcess.php');
$form->setClass('w-full max-w-3xl');
$form->setFactory(DatabaseFormFactory::create($pdo));

$form->addHeaderAction('download', __('Download Template'))
    ->setIcon('download')
    ->setURL('/modules/Planner/planner_import_export.php')
    ->addParam('action', 'downloadTemplate')
    ->directLink()
    ->displayLabel();

$form->addRow()->addHeading(__('Import Lessons from CSV'));

$row = $form->addRow();
    $row->addLabel('file', __('Upload CSV'))->description(__('Select the CSV file containing lesson data to import.'));
    $row->addFileUpload('file')->required();

$row = $form->addRow();
    $row->addFooter();
    $row->addSubmit(__('Import'));

echo $form->getOutput();

