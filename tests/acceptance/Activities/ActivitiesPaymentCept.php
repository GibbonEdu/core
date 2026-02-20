<?php
/**
 * @covers modules/Activities/activities_payment.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('generate activity invoices');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'activities_payment.php');
$I->seeBreadcrumb('Generate Invoices');
