<?php
/**
 * @covers modules/Tracking/dataPoints.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View data points');
$I->loginAsAdmin();
$I->amOnModulePage('Tracking', 'dataPoints.php');
$I->seeBreadcrumb('Data Points');
