<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_edit_targets.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view personalised attainment targets page');
$I->loginAsAdmin();

// Navigate to Markbook View
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#searchForm');

$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

// Navigate to Set Targets page
$I->amOnModulePage('Markbook', 'markbook_edit_targets.php', array('gibbonCourseClassID' => $gibbonCourseClassID));
$I->seeBreadcrumb('Set Personalised Attainment Targets');

// Verify the page loads correctly
$I->see('Target Scale');
$I->see('Student');
$I->see('Attainment Target');
$I->seeElement('select[name="gibbonScaleIDTarget"]');

