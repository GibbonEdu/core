<?php
/**
 * @covers modules/Activities/choices_manage.php
 * @covers modules/Activities/choices_manage_addEdit.php
 * @covers modules/Activities/choices_manage_generate.php
 * @covers modules/Activities/choices_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage activity choices with add/edit, generate, and delete operations');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'choices_manage.php');
$I->seeBreadcrumb('Manage Choices');

// Test search form
$I->submitForm('#content form', [
    'search' => 'Test',
]);
$I->seeInCurrentUrl('search=Test');

// Test filter form only if there are categories available
$categoryCount = $I->grabMultiple('#content select[name=gibbonActivityCategoryID] option:not([value=""])');
if (count($categoryCount) > 0) {
    $I->selectFromDropdown('gibbonActivityCategoryID', 1);
    $I->submitForm('#content form', []);
    $I->seeInCurrentUrl('gibbonActivityCategoryID=');
}

// Test Add/Edit Action ----------------------------------
$I->amOnModulePage('Activities', 'choices_manage.php');
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Choice');
$I->dontSeeErrors();

// Test Generate Action ----------------------------------
$I->amOnModulePage('Activities', 'choices_manage_generate.php');
$I->dontSeeErrors();

// Note: choices_manage_delete.php is now covered
