<?php
/**
 * @covers modules/Staff/staff_duty.php
 * @covers modules/Staff/staff_duty_add.php
 * @covers modules/Staff/staff_duty_edit.php
 * @covers modules/Staff/staff_duty_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff duty assignments');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'staff_duty.php');
$I->seeBreadcrumb('Duty Schedule');

// Test accessing the edit duty schedule page
$I->click('Edit Duty Schedule');
$I->seeBreadcrumb('Edit Duty Schedule');
$I->dontSeeErrors();
