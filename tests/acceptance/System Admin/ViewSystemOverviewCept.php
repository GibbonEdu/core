<?php
/**
 * @covers modules/System Admin/systemOverview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View system overview');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'systemOverview.php');
$I->seeBreadcrumb('System Overview');
