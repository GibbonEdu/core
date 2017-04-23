<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Application Form Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'applicationFormSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'introduction'                => 'Intro Test',
    'applicationFormSENText'      => 'SEN Test',
    'applicationFormRefereeLink'  => 'http://referee.test',
    'postscript'                  => 'Postscript Test',
    'scholarships'                => 'Scholarship Test',
    'agreement'                   => 'Agreement Test',
    'applicationFee'              => '1024.00',
    'publicApplications'          => 'Y',
    'milestones'                  => 'Mile,Stone,Test',
    'howDidYouHear'               => 'Telepathy,Osmosis,Premonition',
    'requiredDocuments'           => 'Required 1,Required 2,Required 3',
    'internalDocuments'           => 'Internal 1,Internal 2',
    'requiredDocumentsText'       => 'Requires Test',
    'requiredDocumentsCompulsory' => 'Y',
    'languageOptionsActive'       => 'Y',
    'languageOptionsBlurb'        => 'Language Blurb Test',
    'languageOptionsLanguageList' => 'Esperanto,Latin,Klingon',
    'usernameFormat'              => '[surname]test[preferredNameInitial]',
    'notificationStudentMessage'  => 'Student Message Test',
    'notificationStudentDefault'  => 'Y',
    'notificationParentsMessage'  => 'Parent Message Test',
    'notificationParentsDefault'  => 'Y',
    'studentDefaultEmail'         => '[username]@test.com',
    'studentDefaultWebsite'       => 'http://student.test',
    'autoHouseAssign'             => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

