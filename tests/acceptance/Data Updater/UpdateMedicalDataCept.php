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
    'longTermMedication'        => 'Y',
    'longTermMedicationDetails' => 'Test',
);

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();

$gibbonPersonID = $I->grabValueFromURL('gibbonPersonID');

$I->amOnModulePage('Data Updater', 'data_medical.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeInFormFields('#content form[method="post"]', $editFormValues);

$gibbonPersonMedicalUpdateID = $I->grabValueFrom("input[type='hidden'][name='existing']");

// Accept ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage_edit.php', array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID));
$I->seeBreadcrumb('Edit Request');

$I->see('Y', 'td');
$I->see('Test', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalUpdateID = $I->grabValueFromURL('gibbonPersonMedicalUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage_delete.php', array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID));

$I->click('Delete');
$I->seeSuccessMessage();


// Select ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical.php');
$I->seeBreadcrumb('Update Medical Data');

$I->selectFromDropdown('gibbonPersonID', 2);
$I->click('Submit');

// Update ------------------------------------------------
$I->see('Update Data');

$editFormValues = array(
    'longTermMedication'        => 'N',
    'longTermMedicationDetails' => 'Test2',
);

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();

$gibbonPersonID = $I->grabValueFromURL('gibbonPersonID');

$I->amOnModulePage('Data Updater', 'data_medical.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeInFormFields('#content form[method="post"]', $editFormValues);

$gibbonPersonMedicalUpdateID = $I->grabValueFrom("input[type='hidden'][name='existing']");

// Accept ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage_edit.php', array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID));
$I->seeBreadcrumb('Edit Request');

$I->see('N', 'td');
$I->see('Test2', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalUpdateID = $I->grabValueFromURL('gibbonPersonMedicalUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_medical_manage_delete.php', array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID));

$I->click('Delete');
$I->seeSuccessMessage();
