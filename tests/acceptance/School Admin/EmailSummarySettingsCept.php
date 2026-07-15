<?php
/**
 * @covers modules/School Admin/emailSummarySettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update email summary settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'emailSummarySettings.php');
$I->seeBreadcrumb('Email Summary Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values (use array_replace to modify only specific fields)
$formValues = array_replace($originalFormValues, array(
    'parentWeeklyEmailSummaryIncludeBehaviour' => 'Y',
    'parentWeeklyEmailSummaryIncludeMarkbook' => 'Y',
));

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('School Admin', 'emailSummarySettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
