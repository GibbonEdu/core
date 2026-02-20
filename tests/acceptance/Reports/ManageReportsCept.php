<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage reports');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reports_manage.php');
$I->seeBreadcrumb('Manage Reports');
