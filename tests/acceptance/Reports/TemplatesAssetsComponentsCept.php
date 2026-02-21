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

// Create test data
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Component Template',
    'context' => 'Student Enrolment',
]);

$gibbonReportTemplateSectionID = $I->haveInDatabase('gibbonReportTemplateSection', [
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
    'type' => 'Core',
    'name' => 'Test Section',
    'page' => 1,
]);

$gibbonReportTemplateComponentID = $I->haveInDatabase('gibbonReportTemplateComponent', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    'type' => 'Text',
    'name' => 'Test Component',
    'x' => 10,
    'y' => 10,
    'w' => 100,
    'h' => 50,
]);

// Templates Assets Components Edit --------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_edit.php', [
    'gibbonReportTemplateComponentID' => $gibbonReportTemplateComponentID,
]);
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Templates Assets Components Preview -----------------------

$I->amOnModulePage('Reports', 'templates_assets_components_preview.php', [
    'gibbonReportTemplateComponentID' => $gibbonReportTemplateComponentID,
]);
$I->dontSeeErrors();

// Templates Assets Components Help --------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_help.php', [
    'gibbonReportTemplateComponentID' => $gibbonReportTemplateComponentID,
]);
$I->seeBreadcrumb('Help');
$I->dontSeeErrors();

// Templates Assets Components Duplicate ---------------------

$I->amOnModulePage('Reports', 'templates_assets_components_duplicate.php', [
    'gibbonReportTemplateComponentID' => $gibbonReportTemplateComponentID,
]);
$I->seeBreadcrumb('Duplicate');
$I->dontSeeErrors();

// Templates Assets Components Delete ------------------------

$I->amOnModulePage('Reports', 'templates_assets_components_delete.php', [
    'gibbonReportTemplateComponentID' => $gibbonReportTemplateComponentID,
]);
$I->click('Delete');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportTemplateSection', ['gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
