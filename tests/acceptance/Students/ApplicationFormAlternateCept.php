<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('submit a student application form with most settings disabled');
$I->loginAsAdmin();

// Change Application Settings ---------------------------------

$I->amOnModulePage('User Admin', 'applicationFormSettings.php');
$originalApplicationSettings = $I->grabAllFormValues();

$newApplicationSettings = array_replace($originalApplicationSettings, array(
    'howDidYouHear'              => '',
    'senOptionsActive'           => 'N',
    'scholarshipOptionsActive'   => 'N',
    'paymentOptionsActive'       => 'N',
    'languageOptionsActive'      => 'N',
    'applicationFormRefereeLink' => '',
    'agreement'                  => '',
));

$I->submitForm('#content form', $newApplicationSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newApplicationSettings);

// Change User Settings ---------------------------------

$I->amOnModulePage('User Admin', 'userSettings.php');
$originalUserSettings = $I->grabAllFormValues();

$newUserSettings = array_replace($originalUserSettings, array(
    'nationality'        => '',
    'residencyStatus'    => '',
    'privacy'            => 'N',
    'dayTypeOptions'     => '',
    'dayTypeText'        => '',
));

$I->submitForm('#content form', $newUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newUserSettings);

// Go To Application  ------------------------------------------

$I->click('Logout', 'a');
$I->amOnModulePage('Students', 'applicationForm.php');
$I->seeBreadcrumb('Application Form');

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
    'citizenship1'                => 'Antarctica',
    'citizenship1Passport'        => 'ABC12345',
    'nationalIDCardNumber'        => 'DEF12345',
    'residencyStatus'             => 'Resident',
    'visaExpiryDate'              => '01/01/2020',
    'email'                       => 'testing.mctest@testingemail.test',
    'phone1'                      => '12345678',
    'phone1Type'                  => 'Mobile',
    'phone1CountryCode'           => '1',
    'phone2'                      => '87654321',
    'phone2Type'                  => 'Home',
    'phone2CountryCode'           => '1',
    'medicalInformation'          => 'Testing Medical',
    'dateStart'                   => '01/01/2020',
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
    'homeAddress'                 => '123 Fictitious Lane',
    'homeAddressDistrict'         => 'Nowhere',
    'homeAddressCountry'          => 'Antarctica',
    'parent1title'                => 'Ms.',
    'parent1surname'              => 'McTest',
    'parent1firstName'            => 'Parent 1',
    'parent1preferredName'        => 'Parent 1',
    'parent1officialName'         => 'Parent 1 McTest',
    'parent1nameInCharacters'     => 'P1T',
    'parent1gender'               => 'F',
    'parent1relationship'         => 'Mother',
    'parent1languageFirst'        => 'Latin',
    'parent1languageSecond'       => 'English',
    'parent1citizenship1'         => 'Antarctica',
    'parent1nationalIDCardNumber' => 'GHI12345',
    'parent1residencyStatus'      => 'Non-Resident',
    'parent1visaExpiryDate'       => '02/02/2020',
    'parent1email'                => 'parent1.mctest@testingemail.test',
    'parent1phone1'               => '34567890',
    'parent1phone1Type'           => 'Mobile',
    'parent1phone1CountryCode'    => '1',
    'parent1phone2'               => '23456789',
    'parent1phone2Type'           => 'Work',
    'parent1phone2CountryCode'    => '1',
    'parent1profession'           => 'Neurosurgeon',
    'parent1employer'             => 'Parent 1 Employer',
    'siblingName1'                => 'Sibling 1 McTest',
    'siblingDOB1'                 => '01/01/2001',
    'siblingSchool1'              => 'Sibling 1 School',
    'siblingSchoolJoiningDate1'   => '01/01/2017',
    'siblingName2'                => 'Sibling 2 McTest',
    'siblingDOB2'                 => '02/02/2002',
    'siblingSchool2'              => 'Sibling 2 School',
    'siblingSchoolJoiningDate2'   => '02/02/2017',
    'siblingName3'                => 'Sibling 3 McTest',
    'siblingDOB3'                 => '03/03/2003',
    'siblingSchool3'              => 'Sibling 3 School',
    'siblingSchoolJoiningDate3'   => '03/03/2017',
    'howDidYouHear'               => 'Something Random',
    'gibbonFamily'                => 'FALSE',
);

// Maually select some items (relative values)
$I->selectFromDropdown('gibbonSchoolYearIDEntry', 2);
$I->selectFromDropdown('gibbonYearGroupIDEntry', 2);

// No second parent
$I->selectOption('secondParent', 'No');

$I->submitForm('#content form', $formValues, 'Submit');

$I->see('Your application was successfully submitted', '.success');

$applicationFormHash = $I->grabValueFromURL('id');
$gibbonApplicationFormID = $I->grabTextFrom('.success b u');
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonApplicationForm', 'gibbonSchoolYearIDEntry', array('gibbonApplicationFormID' => $gibbonApplicationFormID));

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
