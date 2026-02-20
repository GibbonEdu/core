<?php
/**
 * @covers modules/Students/report_students_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Students by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_students_byFormGroup.php');
$I->seeBreadcrumb('Students by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
