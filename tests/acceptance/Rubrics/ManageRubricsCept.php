<?php
/**
 * @covers modules/Rubrics/rubrics.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage rubrics');
$I->loginAsAdmin();
$I->amOnModulePage('Rubrics', 'rubrics.php');
$I->seeBreadcrumb('Manage Rubrics');
