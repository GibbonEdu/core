<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('update System Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'systemSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'systemName'                    => 'Gibbon Test',
    'indexText'                     => 'The following is a test of the Emergency Testing System. Beware! The gibbons may escape ...',
    'installType'                   => 'Testing',
    'statsCollection'               => 'N',
    'organisationName'              => 'Syndicate of Wordwide Gibbon Testers',
    'organisationNameShort'         => 'SWGT',
    'organisationEmail'             => 'test@testing.test',
    'country'                       => 'Antarctica',
    'firstDayOfTheWeek'             => 'Sunday',
    'timezone'                      => 'UTC',
    'currency'                      => 'BTC',
    'emailLink'                     => 'http://email.test',
    'webLink'                       => 'http://web.test',
    'pagination'                    => '100',
    'analytics'                     => '<script></script>',
);

$I->selectFromDropdown('organisationAdministrator', 2);
$I->selectFromDropdown('organisationDBA', 2);
$I->selectFromDropdown('organisationAdmissions', 2);
$I->selectFromDropdown('organisationHR', 2);
$I->selectFromDropdown('defaultAssessmentScale', 1);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
