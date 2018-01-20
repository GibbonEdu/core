<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('submit a student application form logged in as a parent');
$I->loginAsAdmin();

// Change Application Settings ---------------------------------

$I->amOnModulePage('User Admin', 'applicationFormSettings.php');
$originalApplicationSettings = $I->grabAllFormValues();

$newApplicationSettings = array_replace($originalApplicationSettings, array(
    'howDidYouHear'               => 'Advertisement, Personal Recommendation, World Wide Web, Others',
    'senOptionsActive'            => 'Y',
    'scholarshipOptionsActive'    => 'Y',
    'paymentOptionsActive'        => 'Y',
    'languageOptionsActive'       => 'Y',
    'languageOptionsBlurb'        => 'Language Blurb Test',
    'languageOptionsLanguageList' => 'Esperanto,Latin,Klingon',
    'applicationFormRefereeLink'  => 'http://referee.test',
    'requiredDocuments'           => 'FileUpload0,FileUpload1',
    'agreement'                   => 'Agreement Test',
));

$I->submitForm('#content form', $newApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newApplicationSettings);

// Change User Settings ---------------------------------

$I->amOnModulePage('User Admin', 'userSettings.php');
$originalUserSettings = $I->grabAllFormValues();

$newUserSettings = array_replace($originalUserSettings, array(
    'nationality'        => 'Nationality 1, Nationality 2, Nationality 3',
    'residencyStatus'    => 'Status 1, Status 2, Status 3',
    'privacy'            => 'Y',
    'privacyBlurb'       => 'Privacy Blurb Test',
    'privacyOptions'     => 'Privacy 1, Privacy 2, Privacy 3',
    'dayTypeOptions'     => 'Day Type 1, Day Type 2',
    'dayTypeText'        => 'Day-Type Test',
));

$I->submitForm('#content form', $newUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newUserSettings);

// Go To Application  ------------------------------------------

$I->click('Logout', 'a');
$I->loginAsParent();

$I->amOnModulePage('Students', 'applicationForm.php');
$I->seeBreadcrumb('Application Form');

// Verify logged-in parent data
$I->see('Choose the family you wish to associate this application with');
$I->see('Testing Family');
$I->selectOption('select[name$="relationships[]"]', 'Other');

// Fill in Form ------------------------------------------------
$formValues = array(
    'surname'                     => 'McTest',
    'firstName'                   => 'Testing',
    'preferredName'               => 'Testing',
    'officialName'                => 'Testing McTest',
    'nameInCharacters'            => 'TT',
    'gender'                      => 'M',
    'dob'                         => '01/01/2000',
    'languageHomePrimary'         => 'English',
    'languageHomeSecondary'       => 'German',
    'languageFirst'               => 'Spanish',
    'languageSecond'              => 'Latin',
    'languageThird'               => 'Turkish',
    'countryOfBirth'              => 'Antarctica',
    'citizenship1'                => 'Nationality 1',
    'citizenship1Passport'        => 'ABC12345',
    'nationalIDCardNumber'        => 'DEF12345',
    'residencyStatus'             => 'Status 1',
    'visaExpiryDate'              => '01/01/2020',
    'email'                       => 'testing.mctest@testingemail.test',
    'phone1'                      => '12345678',
    'phone1Type'                  => 'Mobile',
    'phone1CountryCode'           => '1',
    'phone2'                      => '87654321',
    'phone2Type'                  => 'Home',
    'phone2CountryCode'           => '1',
    'sen'                         => 'Y',
    'senDetails'                  => 'Testing SEN',
    'medicalInformation'          => 'Testing Medical',
    'dateStart'                   => '01/01/2020',
    'referenceEmail'              => 'reference@testingemail.test',
    'dayType'                     => 'Day Type 1',
    'schoolName1'                 => 'Previous School 1',
    'schoolAddress1'              => 'Previous Address 1',
    'schoolGrades1'               => 'Previous Grade 1',
    'schoolLanguage1'             => 'Language 1',
    'schoolDate1'                 => '01/01/2015',
    'schoolName2'                 => 'Previous School 2',
    'schoolAddress2'              => 'Previous Address 2',
    'schoolGrades2'               => 'Previous Grade 2',
    'schoolLanguage2'             => 'Language 2',
    'schoolDate2'                 => '01/01/2011',
    'languageChoice'              => 'Latin',
    'languageChoiceExperience'    => 'Language Choice Test',
    'scholarshipInterest'         => 'Y',
    'scholarshipRequired'         => 'Y',
    'payment'                     => 'Company',
    'companyName'                 => 'Testing Company',
    'companyContact'              => 'Mr. Test McTesting',
    'companyAddress'              => '1234 Company Address',
    'companyEmail'                => 'testing.company@testingemail.test',
    'companyCCFamily'             => 'Y',
    'companyPhone'                => '54329876',
    'howDidYouHear'               => 'Others',
    'howDidYouHearMore'           => 'Testing',
    'gibbonFamily'                => 'TRUE',
);

// Maually select some items (relative values)
$I->selectFromDropdown('gibbonSchoolYearIDEntry', 2);
$I->selectFromDropdown('gibbonYearGroupIDEntry', 2);

// Check the agreement
$I->checkOption('agreement');

$I->submitForm('#content form', $formValues, 'Submit');

$I->see('Your application was successfully submitted', '.success');

$applicationFormHash = $I->grabValueFromURL('id');
$gibbonApplicationFormID = $I->grabTextFrom('.success b u');
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonApplicationForm', 'gibbonSchoolYearIDEntry', array('gibbonApplicationFormID' => $gibbonApplicationFormID));

$I->click('Logout', 'a');

// Verify ------------------------------------------------
$I->loginAsAdmin();
$urlParams = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
$I->amOnModulePage('Students', 'applicationForm_manage_edit.php', $urlParams );
$I->seeBreadcrumb('Edit Form');

$I->seeInFormFields('#content form', $formValues);

// Cleanup ------------------------------------------------

$urlParams = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
$I->amOnModulePage('Students', 'applicationForm_manage_delete.php', $urlParams );
$I->seeBreadcrumb('Delete Form');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Restore Original Settings -----------------------------------

$I->amOnModulePage('User Admin', 'applicationFormSettings.php');
$I->submitForm('#content form', $originalApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalApplicationSettings);

$I->amOnModulePage('User Admin', 'userSettings.php');
$I->submitForm('#content form', $originalUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalUserSettings);
