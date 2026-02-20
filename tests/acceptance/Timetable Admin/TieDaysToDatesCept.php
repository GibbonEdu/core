<?php
/**
 * @covers modules/Timetable Admin/ttDates.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check tie days to dates');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'ttDates.php');
$I->seeBreadcrumb('Tie Days to Dates');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
