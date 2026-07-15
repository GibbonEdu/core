<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_view_myMarks.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view my marks as a student');
$I->loginAsStudent();

// Navigate to the page
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

// Verify the page loads without errors
$I->dontSeeErrors();

// Verify we can see the filter form
$I->seeElement('select[name="gibbonDepartmentID"]');
$I->seeElement('select[name="gibbonSchoolYearID"]');

// Test the filter
$I->selectFromDropdown('gibbonSchoolYearID', 1);
$I->submitForm('#filter', []);
$I->dontSeeErrors();
