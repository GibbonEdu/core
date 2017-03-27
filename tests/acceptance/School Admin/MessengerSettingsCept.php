<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Messenger Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'messengerSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'smsUsername'            => 'usernameTest',
    'smsPassword'            => 'passwordTest',
    'smsURL'                 => 'http://url.test',
    'smsURLCredit'           => 'http://credit.test',
    'messageBubbleWidthType' => 'Wide',
    'messageBubbleBGColor'   => '1A2B3C',
    'messageBubbleAutoHide'  => 'Y',
    'enableHomeScreenWidget' => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
