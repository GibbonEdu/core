<?php
/**
 * @covers modules/Reports/templates_assets_components_delete.php
 * @covers modules/Reports/templates_assets_components_duplicate.php
 * @covers modules/Reports/templates_assets_components_edit.php
 * @covers modules/Reports/templates_assets_components_help.php
 * @covers modules/Reports/templates_assets_components_preview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates assets components pages');
$I->loginAsAdmin();

touch('uploads/reports/templates/test.twig.html');

// Create test data
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Component Template',
    'context' => 'Student Enrolment',
]);

$gibbonReportPrototypeSectionID = $I->haveInDatabase('gibbonReportPrototypeSection', [
    'type' => 'Core',
    'name' => 'Test Component',
    'templateFile' => 'test.twig.html'
]);


// Templates Assets Components Edit --------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_edit.php', [
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
]);
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Templates Assets Components Preview -----------------------

$I->amOnModulePage('Reports', 'templates_assets_components_preview.php', [
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
]);
$I->dontSeeErrors();

// Templates Assets Components Help --------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_help.php', [
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
]);
$I->dontSeeErrors();

// Templates Assets Components Duplicate ---------------------

$I->amOnModulePage('Reports', 'templates_assets_components_duplicate.php', [
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
]);
$I->seeBreadcrumb('Duplicate');
$I->dontSeeErrors();

// Templates Assets Components Delete ------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_delete.php', [
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
]);
$I->click('Delete');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportPrototypeSection', ['gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
