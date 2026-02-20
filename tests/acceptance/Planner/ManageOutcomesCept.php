<?php
/**
 * @covers modules/Planner/outcomes.php
 * @covers modules/Planner/outcomes_add.php
 * @covers modules/Planner/outcomes_edit.php
 * @covers modules/Planner/outcomes_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a learning outcome');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'outcomes.php');
$I->seeBreadcrumb('Manage Outcomes');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Outcome');

$formValues = array(
    'scope' => 'School',
    'name' => 'Test Learning Outcome',
    'nameShort' => 'TLO',
    'active' => 'Y',
    'category' => 'Knowledge',
    'description' => 'Students will demonstrate understanding of test concepts',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonOutcomeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Planner', 'outcomes_edit.php', array('gibbonOutcomeID' => $gibbonOutcomeID));
$I->seeBreadcrumb('Edit Outcome');

$I->seeInFormFields('#content form', array(
    'name' => 'Test Learning Outcome',
    'nameShort' => 'TLO',
));

$editFormValues = array(
    'name' => 'Updated Learning Outcome',
    'nameShort' => 'ULO',
    'active' => 'N',
    'category' => 'Skills',
    'description' => 'Updated description for testing',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Planner', 'outcomes_delete.php', array('gibbonOutcomeID' => $gibbonOutcomeID));

$I->click('Delete');
$I->seeSuccessMessage();
