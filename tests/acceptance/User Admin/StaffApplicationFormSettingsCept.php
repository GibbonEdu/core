<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Staff Application Form Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'staffApplicationFormSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'staffApplicationFormIntroduction'                => 'Intro Test',
    'staffApplicationFormQuestions'                   => 'Question Test',
    'staffApplicationFormPostscript'                  => 'Postscript Test',
    'staffApplicationFormAgreement'                   => 'Agreement Test',
    'staffApplicationFormPublicApplications'          => 'Y',
    'staffApplicationFormMilestones'                  => 'Mile,Stone,Test',
    'staffApplicationFormRequiredDocuments'           => 'Doc 1,Doc 2,Doc 3',
    'staffApplicationFormRequiredDocumentsText'       => 'Required Docs Test',
    'staffApplicationFormRequiredDocumentsCompulsory' => 'Y',
    'staffApplicationFormUsernameFormat'              => '[surname]testing[preferredName]',
    'staffApplicationFormNotificationMessage'         => 'Notification Test',
    'staffApplicationFormNotificationDefault'         => 'Y',
    'staffApplicationFormDefaultEmail'                => '[username]@staff.test',
    'staffApplicationFormDefaultWebsite'              => 'http://staff.test',
);

$I->fillField('#refereeLink0', 'http://referee.test');

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
