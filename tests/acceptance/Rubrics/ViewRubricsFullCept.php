<?php
/**
 * @covers modules/Rubrics/rubrics_view_full.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View full rubric details');
$I->loginAsAdmin();

// Get an existing rubric ID
$gibbonRubricID = $I->grabFromDatabase('gibbonRubric', 'gibbonRubricID', []);

$I->amOnModulePage('Rubrics', 'rubrics_view_full.php', ['gibbonRubricID' => $gibbonRubricID]);

