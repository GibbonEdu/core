<?php
/**
 * @covers modules/Individual Needs/in_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student records');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'in_view.php');
$I->seeBreadcrumb('View Student Records');
