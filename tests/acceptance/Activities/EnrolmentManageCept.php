<?php
/**
 * @covers modules/Activities/enrolment_manage.php
 * @covers modules/Activities/enrolment_manage_staffing.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage activity enrolment and staffing');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'enrolment_manage.php');
$I->seeBreadcrumb('Manage Enrolment');

// Only test filter if there are categories available
$categoryCount = $I->grabMultiple('#content select[name=gibbonActivityCategoryID] option:not([value=""])');
if (count($categoryCount) > 0) {
    $I->selectFromDropdown('gibbonActivityCategoryID', 1);
    $I->submitForm('#content form', []);
    $I->seeInCurrentUrl('gibbonActivityCategoryID=');
}

// Test Staffing Action ----------------------------------
$I->amOnModulePage('Activities', 'enrolment_manage.php');
$I->click('Staffing');
$I->seeBreadcrumb('Manage Staffing');
$I->dontSeeErrors();
