<?php
/**
 * @covers modules/Staff/absences_view_byPerson.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view staff absences by person');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_view_byPerson.php');
$I->seeBreadcrumb('View Absences');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonPersonID', 1);
$I->submitForm('#filter', []);
$I->dontSeeErrors();
