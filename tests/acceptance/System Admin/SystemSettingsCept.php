<?php
/**
 * @covers modules/System Admin/systemSettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update System Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'systemSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

$I->updateInDatabase('gibbonSetting', ['value' => ''], ['scope' => 'System', 'name' => 'organisationLogo']);
$I->amOnModulePage('System Admin', 'systemSettings.php');

// Make Changes ------------------------------------------------

$newFormValues = array(
    'systemName'            => 'Gibbon Test',
    'indexText'             => 'The following is a test of the Emergency Testing System. Beware! The gibbons may escape ...',
    'installType'           => 'Testing',
    'statsCollection'       => 'N',
    'organisationName'      => 'Syndicate of Wordwide Gibbon Testers',
    'organisationNameShort' => 'SWGT',
    'organisationEmail'     => 'test@testing.test',
    'country'               => 'Antarctica',
    'firstDayOfTheWeek'     => 'Sunday',
    'timezone'              => 'UTC',
    'timeFormatPHP'         => 'H:i',
    'currency'              => 'BTC',
    'emailLink'             => 'http://email.test',
    'webLink'               => 'http://web.test',
    'pagination'            => '100',
    'analytics'             => '<script></script>',
);

$I->attachFile('organisationLogoFile', 'attachment.jpg');

$I->selectFromDropdown('organisationAdministrator', 2);
$I->selectFromDropdown('organisationDBA', 2);
$I->selectFromDropdown('organisationAdmissions', 2);
$I->selectFromDropdown('organisationHR', 2);
$I->selectFromDropdown('defaultAssessmentScale', 1);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->seeSuccessMessage();
$I->seeInFormFields('#content form', $newFormValues);

$file = $I->grabFromDatabase('gibbonSetting', 'value', ['scope' => 'System', 'name' => 'organisationLogo']);
$I->assertNotEmpty($file);

// Test File Upload ----------------------------------------------

// $I->attachFile('organisationLogoFile', 'attachment.jpg');
// $I->submitForm('#content form', [], 'Submit');

// $I->seeSuccessMessage();




// Restore Original Settings -----------------------------------

$I->updateInDatabase('gibbonSetting', ['value' => $originalFormValues['organisationLogo']], ['scope' => 'System', 'name' => 'organisationLogo']);
$I->amOnModulePage('System Admin', 'systemSettings.php');

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
$I->seeInFormFields('#content form', $originalFormValues);

// Cleanup ------------------------------------------------
$I->deleteFile('../'.$file);
