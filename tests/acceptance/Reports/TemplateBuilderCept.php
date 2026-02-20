<?php
/**
 * @covers modules/Reports/templates_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Use template builder');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'templates_manage.php');
$I->seeBreadcrumb('Template Builder');
