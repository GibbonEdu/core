<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('manage activity choices');
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
