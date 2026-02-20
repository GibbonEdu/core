<?php
/**
 * @covers modules/Timetable Admin/ttSettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update timetable settings');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'ttSettings.php');
$I->seeBreadcrumb('Timetable Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values
$formValues = array(
    'enrolmentMinDefault' => '5',
    'enrolmentMaxDefault' => '35',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Restore original settings
$I->amOnModulePage('Timetable Admin', 'ttSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
