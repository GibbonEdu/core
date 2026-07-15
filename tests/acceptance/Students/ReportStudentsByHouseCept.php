<?php
/**
 * @covers modules/Students/report_students_byHouse.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Students by House');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_students_byHouse.php');
$I->seeBreadcrumb('Students by House');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
