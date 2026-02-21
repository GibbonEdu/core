<?php
/**
 * @covers modules/Reports/templates_manage_section_delete.php
 * @covers modules/Reports/templates_manage_section_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates manage section pages');
$I->loginAsAdmin();

// Create test data
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Template',
    'context' => 'Student Enrolment',
]);

$gibbonReportTemplateSectionID = $I->haveInDatabase('gibbonReportTemplateSection', [
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
    'name' => 'Test Section',
    'page' => 1,
]);

// Templates Manage Section Edit -----------------------------

$I->amOnModulePage('Reports', 'templates_manage_section_edit.php', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
]);
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Templates Manage Section Delete ---------------------------

$I->amOnModulePage('Reports', 'templates_manage_section_delete.php', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
]);
$I->click('Delete');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
