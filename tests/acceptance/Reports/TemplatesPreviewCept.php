<?php
/**
 * @covers modules/Reports/templates_preview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates preview page');
$I->loginAsAdmin();

// Create test template
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Template',
    'context' => 'Student Enrolment',
]);

// Templates Preview -----------------------------------------

$I->amOnModulePage('Reports', 'templates_preview.php', [
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
]);
$I->seeBreadcrumb('Preview');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
