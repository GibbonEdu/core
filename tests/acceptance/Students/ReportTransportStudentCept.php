<?php
/**
 * @covers modules/Students/report_transport_student.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Transport');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_transport_student.php');
$I->seeBreadcrumb('Student Transport');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
