<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_view_allClassesAllData.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view all classes all data markbook page');
$I->loginAsAdmin();

// Navigate to the page
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

// Select a class
$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#searchForm');

// Verify the page loads without errors
$I->dontSeeErrors();

// Verify we can see markbook content
$I->see('Markbook');
