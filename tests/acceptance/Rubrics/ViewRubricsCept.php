<?php
/**
 * @covers modules/Rubrics/rubrics_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View rubrics');
$I->loginAsAdmin();
$I->amOnModulePage('Rubrics', 'rubrics_view.php');
$I->seeBreadcrumb('View Rubrics');
