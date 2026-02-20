<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage archives');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'archive_manage.php');
$I->seeBreadcrumb('Manage Archives');
