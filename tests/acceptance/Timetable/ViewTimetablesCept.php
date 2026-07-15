<?php
/**
 * @covers modules/Timetable/tt.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view timetables by person');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'tt.php');
$I->seeBreadcrumb('View Timetable by Person');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#ttView', []);
$I->dontSeeErrors();
