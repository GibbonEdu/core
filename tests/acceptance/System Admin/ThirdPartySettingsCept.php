<?php 
/**
 * @covers modules/System Admin/thirdPartySettings.php
 * @covers modules/System Admin/thirdPartySettings_ssoEdit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update Third Party Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'thirdPartySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'enablePayments'     => 'Y',
    'paymentGateway'     => 'PayPal',
    'paymentAPIUsername'  => 'paypalUsernameTest',
    'paymentAPIPassword'  => 'paypalPasswordTest',
    'paymentAPISignature' => 'signatureTest',
    'smsGateway'         => 'OneWaySMS',
    'smsSenderID'        => 'smsSenderIDTest',
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

// Reset ----------------------------------------------

$resetFormValues = $originalFormValues;
$resetFormValues['googleOAuth'] = 'Y';
$resetFormValues['enablePayments'] = 'Y';
$resetFormValues['enableMailerSMTP'] = 'Y';
$resetFormValues['smsGateway'] = 'OneWaySMS';
$I->submitForm('#content form', $resetFormValues, 'Submit');

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

// Test SSO Edit Page (DataTable action) -------------------

$I->amOnModulePage('System Admin', 'thirdPartySettings_ssoEdit.php');
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();
