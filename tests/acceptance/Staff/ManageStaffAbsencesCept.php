<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage staff absences');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_manage.php');
$I->seeBreadcrumb('Manage Staff Absences');
