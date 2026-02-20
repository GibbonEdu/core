<?php
/**
 * @covers modules/Staff/absences_add.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Create a new absence');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_add.php');
$I->seeBreadcrumb('New Absence');
