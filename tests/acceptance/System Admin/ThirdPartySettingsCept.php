<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Third Party Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'thirdPartySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'googleOAuth'        => 'Y',
    'googleClientName'   => 'Gibbon Test',
    'googleClientID'     => '1234',
    'googleClientSecret' => '5678',
    'googleRedirectUri'  => 'http://test.test',
    'googleDeveloperKey' => '1234-5678-90',
    'calendarFeed'       => 'http://calendar.test',
    'enablePayments'     => 'Y',
    'paypalAPIUsername'  => 'paypalUsernameTest',
    'paypalAPIPassword'  => 'paypalPasswordTest',
    'paypalAPISignature' => 'signatureTest',
    'smsUsername'        => 'smsUsernameTest',
    'smsPassword'        => 'smsPasswordTest',
    'smsURL'             => 'http://sms.test',
    'smsURLCredit'       => 'http://credit.test',
    'enableMailerSMTP'   => 'Y',
    'mailerSMTPHost'     => 'http://mail.test',
    'mailerSMTPPort'     => '42',
    'mailerSMTPUsername' => 'smtpUsernameTest',
    'mailerSMTPPassword' => 'smtpPasswordTest',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
