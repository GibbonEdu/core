<?php
/**
 * @covers modules/Planner/scopeAndSequence.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View scope and sequence');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Planner', 'scopeAndSequence.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Scope And Sequence');
