<?php
/**
 * @covers modules/School Admin/attendanceSettings.php
 * @covers modules/School Admin/attendanceSettings_manage_add.php
 * @covers modules/School Admin/attendanceSettings_manage_edit.php
 * @covers modules/School Admin/attendanceSettings_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update Attendance Settings and manage attendance codes');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'attendanceSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'attendanceReasons'                  => 'Reason1,Reason2,Reason3',
    'countClassAsSchool'                 => 'Y',
    'studentSelfRegistrationIPAddresses' => '127.0.0.1,192.168.0.1',
    'attendanceCLINotifyByFormGroup'     => 'Y',
    'attendanceCLINotifyByClass'         => 'Y',
    'attendanceCLIAdditionalUsers[]'     => '0000000001'
);

$I->selectFromDropdown('defaultFormGroupAttendanceType', 1);
$I->selectFromDropdown('defaultClassAttendanceType', 1);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

// Add Attendance Code ------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Attendance Code');

$uniqueID = uniqid();
$addFormValues = array(
    'name'           => 'Test Code ' . $uniqueID,
    'nameShort'      => 'TC' . substr($uniqueID, -2),
    'direction'      => 'In',
    'scope'          => 'Onsite',
    'sequenceNumber' => '99',
    'active'         => 'Y',
    'reportable'     => 'Y',
    'prefill'        => 'Y',
    'future'         => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonAttendanceCodeID = $I->grabEditIDFromURL();

// Edit Attendance Code -----------------------------------------
$I->amOnModulePage('School Admin', 'attendanceSettings_manage_edit.php', array(
    'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID
));
$I->seeBreadcrumb('Edit Attendance Code');

$I->seeInField('nameText', 'Test Code ' . $uniqueID);

$editFormValues = array(
    'direction'      => 'Out',
    'scope'          => 'Offsite',
    'sequenceNumber' => '98',
    'active'         => 'N',
    'reportable'     => 'N',
    'prefill'        => 'N',
    'future'         => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Attendance Code ---------------------------------------
$I->amOnModulePage('School Admin', 'attendanceSettings_manage_delete.php', array(
    'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID
));

$I->click('Yes');
$I->seeSuccessMessage();

