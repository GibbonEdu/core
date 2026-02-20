<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage student alerts');
$I->loginAsAdmin();
$I->amOnModulePage('Student Alerts', 'studentAlerts_manage.php');
$I->seeBreadcrumb('Manage Student Alerts');
