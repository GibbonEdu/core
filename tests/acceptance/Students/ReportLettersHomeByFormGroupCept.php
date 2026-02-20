<?php
/**
 * @covers modules/Students/report_lettersHome_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Letters Home by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_lettersHome_byFormGroup.php');
$I->seeBreadcrumb('Letters Home by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
