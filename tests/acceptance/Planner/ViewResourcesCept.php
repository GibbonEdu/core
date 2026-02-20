<?php
/**
 * @covers modules/Planner/resources_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View resources');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'resources_view.php');
$I->seeBreadcrumb('View Resources');
