<?php
/**
 * @covers modules/Reports/templates_manage.php
 * @covers modules/Reports/templates_manage_add.php
 * @covers modules/Reports/templates_manage_edit.php
 * @covers modules/Reports/templates_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Use template builder with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'templates_manage.php');
$I->seeBreadcrumb('Template Builder');

// Add a new template
$I->clickLink('Add');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Template');
$I->selectFromDropdown('context', 2);
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the template
$gibbonReportTemplateID = $I->grabEditIDFromURL();
$I->amOnModulePage('Reports', 'templates_manage_edit.php', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Template');
$I->fillField('name', 'Updated Template');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the template
$I->amOnModulePage('Reports', 'templates_manage_delete.php', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
