<?php
/**
 * @covers modules/System Admin/services_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Services Management');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'services_manage.php');
$I->seeBreadcrumb('Manage Services');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
