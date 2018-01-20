<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a user');
$I->loginAsAdmin();

// Change User Settings ---------------------------------
$I->amOnModulePage('User Admin', 'userSettings.php');
$originalUserSettings = $I->grabAllFormValues();

$newUserSettings = array_replace($originalUserSettings, array(
    'nationality'     => 'Nationality 1, Nationality 2, Nationality 3',
    'ethnicity'       => 'Ethnicity 1, Ethnicity 2, Ethnicity 3',
    'religions'       => 'Religion 1, Religion 2, Religion 3',
    'residencyStatus' => 'Status 1, Status 2, Status 3',
    'privacy'         => 'Y',
    'privacyBlurb'    => 'Privacy Blurb Test',
    'privacyOptions'  => 'Privacy 1, Privacy 2, Privacy 3',
    'dayTypeOptions'  => 'Day Type 1, Day Type 2',
    'dayTypeText'     => 'Day-Type Test',
));

$I->submitForm('#content form', $newUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newUserSettings);


// Add ------------------------------------------------
$I->amOnModulePage('User Admin', 'user_manage.php');
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add User');

$formValues = array(
    'title'                     => 'Ms.',
    'surname'                   => 'McTest',
    'firstName'                 => 'Test',
    'preferredName'             => 'Test',
    'officialName'              => 'Test E. McTest',
    'nameInCharacters'          => 'TM',
    'gender'                    => 'F',
    'dob'                       => '30/01/2001',
    'status'                    => 'Full',
    'canLogin'                  => 'N',
    'passwordForceReset'        => 'N',
    'email'                     => 'testmctest@gibbon.test',
    'emailAlternate'            => 'testmctest2@gibbon.test',
    'address1'                  => '123 Ficticious Lane',
    'address1District'          => 'Nowhere',
    'address1Country'           => 'Antarctica',
    'address2'                  => '234 No Place',
    'address2District'          => 'Somewhere',
    'address2Country'           => 'Antarctica',
    'phone1'                    => '12345678',
    'phone1CountryCode'         => '1',
    'phone1Type'                => 'Home',
    'phone2'                    => '23456789',
    'phone2CountryCode'         => '1',
    'phone2Type'                => 'Mobile',
    'phone3'                    => '34567890',
    'phone3CountryCode'         => '1',
    'phone3Type'                => 'Work',
    'website'                   => 'http://gibbon.test',
    'dayType'                   => 'Day Type 1',
    'lastSchool'                => 'Testing',
    'dateStart'                 => '30/01/2001',
    'languageFirst'             => 'Albanian',
    'languageSecond'            => 'Bulgarian',
    'languageThird'             => 'Cambodian',
    'countryOfBirth'            => 'Antarctica',
    'ethnicity'                 => 'Ethnicity 2',
    'religion'                  => 'Religion 3',
    'citizenship1'              => 'Nationality 1',
    'citizenship1Passport'      => '1234ABC',
    'citizenship2'              => 'Nationality 2',
    'citizenship2Passport'      => 'ABC1234',
    'nationalIDCardNumber'      => '1234-5678',
    'visaExpiryDate'            => '30/01/2100',
    'residencyStatus'           => 'Status 3',
    // 'profession'                => 'Student',
    // 'employer'                  => 'None',
    // 'jobTitle'                  => 'Student',
    'emergency1Name'            => 'Emergency Person 1',
    'emergency1Relationship'    => 'Doctor',
    'emergency1Number1'         => '12345678',
    'emergency1Number2'         => '23456789',
    'emergency2Name'            => 'Emergency Person 2',
    'emergency2Relationship'    => 'Other',
    'emergency2Number1'         => '87654321',
    'emergency2Number2'         => '98765432',
    'studentID'                 => 'testmctest',
    'transport'                 => 'ABC123',
    'transportNotes'            => 'Teleportation',
    'lockerNumber'              => '123',
    'vehicleRegistration'       => '1234',
);

// Non-editable Values
$I->fillField('username', 'testmctest');
$I->fillField('passwordNew', 'ZY6pfPBb');
$I->fillField('passwordConfirm', 'ZY6pfPBb');

// Drop-downs
$I->selectOption('gibbonRoleIDPrimary', 'Student');
$I->selectFromDropdown('gibbonSchoolYearIDClassOf', 2);
$I->selectFromDropdown('gibbonHouseID', 2);

// Handle privacy and student agreements?

// File Uploads
$I->attachFile('file1', 'attachment.jpg');
$I->attachFile('birthCertificateScan', 'attachment.jpg');
$I->attachFile('citizenship1PassportScan', 'attachment.jpg');
$I->attachFile('nationalIDCardScan', 'attachment.jpg');

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonPersonID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('User Admin', 'user_manage_edit.php', array('gibbonPersonID' => $gibbonPersonID, 'search' => ''));
$I->seeBreadcrumb('Edit User');

$I->seeInFormFields('#content form', $formValues);
$I->seeFieldIsNotEmpty('input[name="attachment1"]');
$I->seeFieldIsNotEmpty('input[name="birthCertificateScanCurrent"]');
$I->seeFieldIsNotEmpty('input[name="citizenship1PassportScanCurrent"]');
$I->seeFieldIsNotEmpty('input[name="nationalIDCardScanCurrent"]');

$formValues = array(
    'title'                     => 'Mr.',
    'surname'                   => 'McTesting',
    'firstName'                 => 'Testing',
    'preferredName'             => 'Testing',
    'officialName'              => 'Test E. McTesting',
    'nameInCharacters'          => 'TST',
    'gender'                    => 'M',
    'dob'                       => '10/10/2010',
    'status'                    => 'Left',
    'canLogin'                  => 'Y',
    'passwordForceReset'        => 'Y',
    'email'                     => 'testmctesting@gibbon.test',
    'emailAlternate'            => 'testmctest2ing@gibbon.test',
    'address1'                  => '321 Ficticious Lane',
    'address1District'          => 'Somewhere',
    'address1Country'           => 'Zimbabwe',
    'address2'                  => '4321 No Place',
    'address2District'          => 'Nowhere',
    'address2Country'           => 'Zimbabwe',
    'phone1'                    => '87654321',
    'phone1CountryCode'         => '',
    'phone1Type'                => 'Pager',
    'phone2'                    => '98765432',
    'phone2CountryCode'         => '',
    'phone2Type'                => 'Other',
    'phone3'                    => '09876543',
    'phone3CountryCode'         => '',
    'phone3Type'                => 'Fax',
    'website'                   => 'http://testing.gibbon.test',
    'dayType'                   => 'Day Type 2',
    'lastSchool'                => 'Testing Also',
    'dateStart'                 => '10/10/2010',
    'languageFirst'             => 'Xhosa',
    'languageSecond'            => 'Welsh',
    'languageThird'             => 'Uzbek',
    'countryOfBirth'            => 'Zimbabwe',
    'ethnicity'                 => 'Ethnicity 1',
    'religion'                  => 'Religion 2',
    'citizenship1'              => 'Nationality 2',
    'citizenship1Passport'      => '4321ABC',
    'citizenship2'              => 'Nationality 3',
    'citizenship2Passport'      => 'ABC4321',
    'nationalIDCardNumber'      => '4321-5678',
    'visaExpiryDate'            => '10/10/2110',
    'residencyStatus'           => 'Status 1',
    'emergency1Name'            => 'Emergency Person 1 Also',
    'emergency1Relationship'    => 'Friend',
    'emergency1Number1'         => '87654321',
    'emergency1Number2'         => '98765432',
    'emergency2Name'            => 'Emergency Person 2 Also',
    'emergency2Relationship'    => 'Spouse',
    'emergency2Number1'         => '12345678',
    'emergency2Number2'         => '23456789',
    'studentID'                 => 'testmctesting',
    'transport'                 => 'ABC321',
    'transportNotes'            => 'Tardis',
    'lockerNumber'              => '321',
    'vehicleRegistration'       => '4321',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Cleanup Files ------------------------------------------------

$I->deleteFile('../'.$I->grabValueFrom('input[name="attachment1"]'));
$I->deleteFile('../'.$I->grabValueFrom('input[name="birthCertificateScanCurrent"]'));
$I->deleteFile('../'.$I->grabValueFrom('input[name="citizenship1PassportScanCurrent"]'));
$I->deleteFile('../'.$I->grabValueFrom('input[name="nationalIDCardScanCurrent"]'));

// Delete ------------------------------------------------
$I->amOnModulePage('User Admin', 'user_manage_delete.php', array('gibbonPersonID' => $gibbonPersonID, 'search' => ''));
$I->seeBreadcrumb('Delete User');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Restore Original Settings -----------------------------------

$I->amOnModulePage('User Admin', 'userSettings.php');
$I->submitForm('#content form', $originalUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalUserSettings);
