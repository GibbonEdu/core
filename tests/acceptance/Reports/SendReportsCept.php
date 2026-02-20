<?php
/**
 * @covers modules/Reports/reports_send.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Send reports');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reports_send.php');
$I->seeBreadcrumb('Send Reports');
