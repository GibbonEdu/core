<?php
/**
 * @covers modules/Students/report_emergencySMS_byTransport.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Emergency SMS by Transport');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_emergencySMS_byTransport.php');
$I->seeBreadcrumb('Emergency SMS by Transport');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
