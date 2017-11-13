<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('submit and approve a medical data update');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_medical.php');

// Select ------------------------------------------------
$I->seeBreadcrumb('Update Medical Data');

$I->selectFromDropdown('gibbonPersonID', 2);
$I->click('Submit');

// Update ------------------------------------------------
$I->see('Update Data');

$editFormValues = array(
    'bloodType'                 => 'AB+',
    'longTermMedication'        => 'Y',
    'longTermMedicationDetails' => 'Test',
    'tetanusWithin10Years'      => 'Y',
);

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();
$I->seeInFormFields('#content form[method="post"]', $editFormValues);


// Accept ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage.php');
$I->seeBreadcrumb('Medical Data Updates');

$I->click('Edit');

$I->see('AB+', 'td');
$I->see('Y', 'td');
$I->see('Test', 'td');
$I->see('Y', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalUpdateID = $I->grabValueFromURL('gibbonPersonMedicalUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage_delete.php', array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID));
$I->seeBreadcrumb('Delete Request');

$I->click('Yes');
$I->seeSuccessMessage();