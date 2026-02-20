<?php
/**
 * @covers modules/Reports/reports_generate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Generate reports');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reports_generate.php');
$I->seeBreadcrumb('Generate Reports');
