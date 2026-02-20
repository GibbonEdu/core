<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('View duty schedule');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'staff_duty.php');
$I->seeBreadcrumb('Duty Schedule');
