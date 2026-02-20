<?php
/**
 * @covers modules/Planner/units.php
 * @covers modules/Planner/units_add.php
 * @covers modules/Planner/units_edit.php
 * @covers modules/Planner/units_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage planner units');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'units.php');
$I->seeBreadcrumb('Unit Planner');
$I->dontSeeErrors();
