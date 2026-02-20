<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage staff');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'staff_manage.php');
$I->seeBreadcrumb('Manage Staff');
