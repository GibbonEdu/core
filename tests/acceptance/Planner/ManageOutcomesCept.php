<?php
/**
 * @covers modules/Planner/outcomes.php
 * @covers modules/Planner/outcomes_add.php
 * @covers modules/Planner/outcomes_edit.php
 * @covers modules/Planner/outcomes_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage learning outcomes');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'outcomes.php');
$I->seeBreadcrumb('Manage Outcomes');

// Add a new outcome
$I->click('Add', 'a');
$I->seeBreadcrumb('Add Outcome');
$I->dontSeeErrors();
