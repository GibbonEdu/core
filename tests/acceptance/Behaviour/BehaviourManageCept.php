<?php
/**
 * @covers modules/Behaviour/behaviour_manage.php
 * @covers modules/Behaviour/behaviour_manage_add.php
 * @covers modules/Behaviour/behaviour_manage_edit.php
 * @covers modules/Behaviour/behaviour_manage_delete.php
 * @covers modules/Behaviour/behaviour_manage_addMulti.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a behaviour record');
$I->loginAsAdmin();
$I->amOnModulePage('Behaviour', 'behaviour_manage.php');

// Search Test -----------------------------------------

$I->selectFromDropdown('gibbonPersonID', 1);
$I->submitForm('#search', []);
$I->dontSeeErrors();

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonPersonID', 1);
$I->selectFromDropdown('type', 1);
$I->selectFromDropdown('descriptor', 1);

$formValues = array(
    'date'    => date('Y-m-d'),
    'comment' => 'Test behaviour incident',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonBehaviourID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Behaviour', 'behaviour_manage_edit.php', array('gibbonBehaviourID' => $gibbonBehaviourID));
$I->seeBreadcrumb('Edit');

$I->seeInField('comment', 'Test behaviour incident');

$I->selectFromDropdown('descriptor', 1);

$formValues = array(
    'comment' => 'Updated test behaviour incident',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Behaviour', 'behaviour_manage_delete.php', array('gibbonBehaviourID' => $gibbonBehaviourID));

$I->fillField('confirm', 'Delete');
$I->click('Yes');

$I->see('Your request was completed successfully.', '.success');

// Add Multiple ----------------------------------------
$I->amOnModulePage('Behaviour', 'behaviour_manage_addMulti.php', array(
    'gibbonPersonID' => '',
    'gibbonFormGroupID' => '',
    'gibbonYearGroupID' => '',
    'type' => ''
));
$I->seeBreadcrumb('Add Multiple');
$I->dontSeeErrors();

