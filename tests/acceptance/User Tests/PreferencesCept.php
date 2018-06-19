<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update my preferences');
$I->loginAsAdmin();

$I->amOnPage('/index.php?q=preferences.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues('#preferences');
$I->seeInFormFields('#preferences', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'calendarFeedPersonal' => 'testing@testing.test',
    'personalBackground' => 'http://testing.test/personalBackground',
);

$I->selectOption('gibbonThemeIDPersonal', '0013');
$I->selectOption('gibboni18nIDPersonal', '0002');
$I->selectOption('receiveNotificationEmails', 'N');
$I->selectOption('smartWorkflowHelp', 'N');

$I->submitForm('#preferences', $newFormValues, 'Submit');


// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#preferences', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#preferences', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#preferences', $originalFormValues);