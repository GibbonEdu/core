<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('ensure the Student Application Form is only accessible when public');
$I->loginAsAdmin();

// Change Application Settings ---------------------------------

$I->amOnModulePage('User Admin', 'applicationFormSettings.php');
$originalApplicationSettings = $I->grabAllFormValues();

$newApplicationSettings = array_replace($originalApplicationSettings, array(
    'publicApplications' => 'N',
));

$I->submitForm('#content form', $newApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newApplicationSettings);

// Go To Application  ------------------------------------------

$I->click('Logout', 'a');
$I->amOnModulePage('Students', 'applicationForm.php');
$I->see('You do not have access to this action.', '.error');

// Change Application Settings ---------------------------------

$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'applicationFormSettings.php');

$newApplicationSettings = array_replace($originalApplicationSettings, array(
    'publicApplications' => 'Y',
));

$I->submitForm('#content form', $newApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newApplicationSettings);

// Go To Application  ------------------------------------------

$I->click('Logout', 'a');
$I->amOnModulePage('Students', 'applicationForm.php');
$I->seeBreadcrumb('Application Form');

// Restore Original Settings -----------------------------------

$I->loginAsAdmin();

$I->amOnModulePage('User Admin', 'applicationFormSettings.php');
$I->submitForm('#content form', $originalApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalApplicationSettings);
