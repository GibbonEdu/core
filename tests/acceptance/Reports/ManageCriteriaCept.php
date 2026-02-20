<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage criteria');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_criteria_manage.php');
$I->seeBreadcrumb('Manage Criteria');
