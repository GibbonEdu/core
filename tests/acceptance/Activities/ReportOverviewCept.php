<?php
/**
 * @covers modules/Activities/report_overview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view activities overview report');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'report_overview.php');
$I->seeBreadcrumb('Activities Overview');

// Select a category if available
$categoryCount = $I->grabMultiple('#content select[name=gibbonActivityCategoryID] option:not([value=""])');
if (count($categoryCount) > 0) {
    $I->selectFromDropdown('gibbonActivityCategoryID', 1);
    $I->submitForm('#content form', []);
    $I->seeInCurrentUrl('gibbonActivityCategoryID=');
    
    // Test Print button if category is selected
    $I->click('Print All');
}
