<?php
/**
 * @covers modules/Students/report_familyAddress_byStudent.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Family Address by Student');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_familyAddress_byStudent.php');
$I->seeBreadcrumb('Family Address by Student');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
