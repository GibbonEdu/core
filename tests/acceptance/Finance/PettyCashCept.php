<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('view petty cash');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'pettyCash.php');
$I->seeBreadcrumb('Petty Cash');
