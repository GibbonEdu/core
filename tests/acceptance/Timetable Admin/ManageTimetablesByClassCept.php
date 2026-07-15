<?php
/**
 * @covers modules/Timetable Admin/tt.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check timetable editing by class');
$I->loginAsAdmin();

// First navigate to the manage timetables page
$I->amOnModulePage('Timetable Admin', 'tt.php');
$I->seeBreadcrumb('Manage Timetables');

// Basic Check - verify the page loads without errors
$I->dontSeeErrors();
