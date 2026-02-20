<?php
/**
 * @covers modules/Planner/planner_deadlines.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View planner deadlines');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'planner_deadlines.php');
$I->seeBreadcrumb('Homework + Due Dates');
