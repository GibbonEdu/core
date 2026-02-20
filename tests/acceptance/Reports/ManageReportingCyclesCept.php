<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage reporting cycles');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_cycles_manage.php');
$I->seeBreadcrumb('Manage Reporting Cycles');
