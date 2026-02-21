<?php
/**
 * @covers modules/Students/applicationForm_manage.php
 * @covers modules/Students/applicationForm_manage_add.php
 * @covers modules/Students/applicationForm_manage_accept.php
 * @covers modules/Students/applicationForm_manage_reject.php
 * @covers modules/Students/applicationForm_manage_edit_fee.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage student applications');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Students', 'applicationForm_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Manage Applications');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Test Add Action (leads to application form) --------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Form');
$I->dontSeeErrors();

// Create a test application for accept/reject tests --

$gibbonApplicationFormID = $I->haveInDatabase('gibbonApplicationForm', [
    'gibbonSchoolYearIDEntry' => $gibbonSchoolYearID,
    'gibbonYearGroupIDEntry' => $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []),
    'surname' => 'TestStudent',
    'firstName' => 'Test',
    'preferredName' => 'Test',
    'officialName' => 'Test TestStudent',
    'nameInCharacters' => '',
    'gender' => 'M',
    'dob' => '2010-01-01',
    'email' => 'test.student@test.com',
    'status' => 'Pending',
    'priority' => '0',
    'timestamp' => date('Y-m-d H:i:s'),
    'languageHomePrimary'         => 'English',
    'languageHomeSecondary'       => 'German',
    'languageFirst'               => 'Spanish',
    'languageSecond'              => 'Latin',
    'languageThird'               => 'Turkish',
    'countryOfBirth'              => 'Antarctica',
    'phone1'                      => '12345678',
    'phone1Type'                  => 'Mobile',
    'phone1CountryCode'           => '1',
    'phone2'                      => '87654321',
    'phone2Type'                  => 'Home',
    'phone2CountryCode'           => '1',
    'sen'                         => 'Y',
    'senDetails'                  => 'Testing SEN',
    'medicalInformation'          => 'Testing Medical',
    'dateStart'                   => '2001-01-01',
    'referenceEmail'              => 'reference@testingemail.test',
    'dayType'                     => 'Day Type 1',
    'schoolName1'                 => 'Previous School 1',
    'schoolAddress1'              => 'Previous Address 1',
    'schoolGrades1'               => 'Previous Grade 1',
    'schoolLanguage1'             => 'Language 1',
    'schoolDate1'                 => '2001-01-01',
    'schoolName2'                 => 'Previous School 2',
    'schoolAddress2'              => 'Previous Address 2',
    'schoolGrades2'               => 'Previous Grade 2',
    'schoolLanguage2'             => 'Language 2',
    'schoolDate2'                 => '2001-01-01',
    'homeAddress'                 => '123 Fictitious Lane',
    'homeAddressDistrict'         => 'Nowhere',
    'homeAddressCountry'          => 'Antarctica',
    'parent2title'                => 'Mr.',
    'parent2surname'              => 'McTest',
    'parent2firstName'            => 'Parent 2',
    'parent2preferredName'        => 'Parent 2',
    'parent2officialName'         => 'Parent 2 McTest',
    'parent2nameInCharacters'     => 'P2T',
    'parent2gender'               => 'M',
    'parent2relationship'         => 'Father',
    'parent2languageFirst'        => 'German',
    'parent2languageSecond'       => 'Urdu',
    'parent2email'                => 'parent2.mctest@testingemail.test',
    'parent2phone1'               => '87654321',
    'parent2phone1Type'           => 'Home',
    'parent2phone1CountryCode'    => '1',
    'parent2phone2'               => '19876543',
    'parent2phone2Type'           => 'Work',
    'parent2phone2CountryCode'    => '1',
    'parent2profession'           => 'Thespian',
    'parent2employer'             => 'Parent 2 Employer',
    'siblingName1'                => 'Sibling 1 McTest',
    'siblingDOB1'                 => '2001-01-01',
    'siblingSchool1'              => 'Sibling 1 School',
    'siblingSchoolJoiningDate1'   => '2001-01-01',
    'siblingName2'                => 'Sibling 2 McTest',
    'siblingDOB2'                 => '2001-01-01',
    'siblingSchool2'              => 'Sibling 2 School',
    'siblingSchoolJoiningDate2'   => '2001-01-01',
    'siblingName3'                => 'Sibling 3 McTest',
    'siblingDOB3'                 => '2001-01-01',
    'siblingSchool3'              => 'Sibling 3 School',
    'siblingSchoolJoiningDate3'   => '2001-01-01',
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
    'milestones'           => '',
    'notes'           => '',
    'fields'           => '',
    'parent1fields'           => '',
    'parent2fields'           => '',
]);

// Test Accept Page ------------------------------------

$I->amOnModulePage('Students', 'applicationForm_manage_accept.php', [
    'gibbonApplicationFormID' => $gibbonApplicationFormID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Accept');
$I->dontSeeErrors();

// Test Reject Page ------------------------------------

$I->amOnModulePage('Students', 'applicationForm_manage_reject.php', [
    'gibbonApplicationFormID' => $gibbonApplicationFormID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Reject');
$I->dontSeeErrors();

// Test Edit Fee Page ----------------------------------

$I->amOnModulePage('Students', 'applicationForm_manage_edit_fee.php', [
    'gibbonApplicationFormID' => $gibbonApplicationFormID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Send Payment Request');
$I->dontSeeErrors();

// Cleanup ---------------------------------------------

$I->deleteFromDatabase('gibbonApplicationForm', ['gibbonApplicationFormID' => $gibbonApplicationFormID]);
