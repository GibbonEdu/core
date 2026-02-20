<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage library shelves');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'library_manage_shelves.php');
$I->seeBreadcrumb('Manage Library Shelves');
