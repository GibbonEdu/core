<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Attendance Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'attendanceSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'attendanceReasons'                  => 'Reason1,Reason2,Reason3',
    'prefillRollGroup'                   => 'Y',
    'prefillClass'                       => 'Y',
    'prefillPerson'                      => 'Y',
    'studentSelfRegistrationIPAddresses' => '127.0.0.1,192.168.0.1',
    'attendanceCLINotifyByRollGroup'     => 'Y',
    'attendanceCLINotifyByClass'         => 'Y',
    'attendanceCLIAdditionalUsers[]'     => '1'
);

$I->selectFromDropdown('defaultRollGroupAttendanceType', 1);
$I->selectFromDropdown('defaultClassAttendanceType', 1);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
