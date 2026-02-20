<?php
/**
 * @covers modules/Students/firstAidRecord.php
 * @covers modules/Students/firstAidRecord_add.php
 * @covers modules/Students/firstAidRecord_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add and edit a first aid record');
$I->loginAsAdmin();
$I->amOnModulePage('Students', 'firstAidRecord.php');
$I->seeBreadcrumb('First Aid Records');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonPersonID', 1);

$formValues = array(
    'date' => date('d/m/Y'),
    'timeIn' => '10:00',
    'description' => 'Minor cut on finger during class',
    'actionTaken' => 'Applied bandage and antiseptic',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFirstAidID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Students', 'firstAidRecord_edit.php', array('gibbonFirstAidID' => $gibbonFirstAidID));
$I->seeBreadcrumb('Edit');

$I->seeInField('description', 'Minor cut on finger during class');

$editFormValues = array(
    'timeOut' => '10:30',
    'followUp' => 'Student returned to class. No further issues reported.',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();
