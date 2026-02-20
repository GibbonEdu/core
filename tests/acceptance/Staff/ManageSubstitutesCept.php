<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage substitutes');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'substitutes_manage.php');
$I->seeBreadcrumb('Manage Substitutes');
