<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage facility changes');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'spaceChange_manage.php');
$I->seeBreadcrumb('Manage Facility Changes');
