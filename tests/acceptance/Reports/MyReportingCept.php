<?php
/**
 * @covers modules/Reports/reporting_my.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View my reporting');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_my.php');
$I->seeBreadcrumb('My Reporting');
