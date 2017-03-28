<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Dashboard Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'dashboardSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'staffDashboardDefaultTab'   => 'Planner',
    'studentDashboardDefaultTab' => 'Planner',
    'parentDashboardDefaultTab'  => 'Timetable',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
