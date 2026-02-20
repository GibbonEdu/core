<?php
/**
 * @covers modules/Staff/absences_approval.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Approve staff absences');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_approval.php');
$I->seeBreadcrumb('Approve Staff Absences');
