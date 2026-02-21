<?php
/**
 * @covers modules/Staff/applicationForm.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check staff application form page');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'applicationForm.php');
$I->seeBreadcrumb('Staff Application Form');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
