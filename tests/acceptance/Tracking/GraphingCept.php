<?php
/**
 * @covers modules/Tracking/graphing.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View graphing');
$I->loginAsAdmin();
$I->amOnModulePage('Tracking', 'graphing.php');
$I->seeBreadcrumb('Graphing');
