<?php
/**
 * @covers modules/Staff/staff_duty.php
 * @covers modules/Staff/staff_duty_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view and edit duty schedule');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'staff_duty.php');
$I->seeBreadcrumb('Duty Schedule');

// Test accessing the edit duty schedule page
$I->click('Edit Duty Schedule');
$I->seeBreadcrumb('Edit Duty Schedule');
$I->dontSeeErrors();
