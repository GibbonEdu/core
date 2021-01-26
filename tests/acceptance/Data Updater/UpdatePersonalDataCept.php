<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('submit and approve a personal data update');
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
));

$I->submitForm('#content form', $newUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');


// Change Students Settings ---------------------------------

$I->amOnModulePage('User Admin', 'studentsSettings.php');
$originalStudentsSettings = $I->grabAllFormValues();

$newStudentsSettings = array_replace($originalStudentsSettings, array(
    'dayTypeOptions'     => 'Day Type 1, Day Type 2',
    'dayTypeText'        => 'Day-Type Test',
));

$I->submitForm('#content form', $newStudentsSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newStudentsSettings);


$I->click('Logout', 'a');

// Select ------------------------------------------------
$I->loginAsParent();
$I->amOnModulePage('Data Updater', 'data_personal.php');
$I->seeBreadcrumb('Update Personal Data');

$I->selectFromDropdown('gibbonPersonID', 3);
$I->click('Submit');

// Update ------------------------------------------------
$I->see('Update Data');

$gibbonPersonID = $I->grabValueFromURL('gibbonPersonID');

$editFormValues = array(
    'title'                     => 'Ms.',
    'surname'                   => 'TestUser',
    'firstName'                 => 'Student',
    'preferredName'             => 'Student',
    'officialName'              => 'Student A. TestUser',
    'nameInCharacters'          => 'TM',
    'dob'                       => '30/01/2001',

    'emergency1Name'            => 'Emergency Person 1',
    'emergency1Relationship'    => 'Doctor',
    'emergency1Number1'         => '12345678',
    'emergency1Number2'         => '23456789',
    'emergency2Name'            => 'Emergency Person 2',
    'emergency2Relationship'    => 'Other',
    'emergency2Number1'         => '87654321',
    'emergency2Number2'         => '98765432',

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
    'visaExpiryDate'            => '30/01/2001',
    'residencyStatus'           => 'Status 3',
);

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();

$gibbonPersonID = $I->grabValueFromURL('gibbonPersonID');

$I->amOnModulePage('Data Updater', 'data_personal.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeInFormFields('#content form[method="post"]', $editFormValues);

$gibbonPersonUpdateID = $I->grabValueFrom("input[type='hidden'][name='existing']");

$I->click('Logout', 'a');
$I->loginAsAdmin();

// Accept ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_personal_manage_edit.php', array('gibbonPersonUpdateID' => $gibbonPersonUpdateID));
$I->seeBreadcrumb('Edit Request');

$I->see('TestUser', 'td');
$I->see('Student A. TestUser', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonPersonUpdateID = $I->grabValueFromURL('gibbonPersonUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_personal_manage_delete.php', array('gibbonPersonUpdateID' => $gibbonPersonUpdateID));

$I->click('Yes');
$I->seeSuccessMessage();

// Restore Original Settings -----------------------------------

$I->amOnModulePage('User Admin', 'userSettings.php');
$I->submitForm('#content form', $originalUserSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalUserSettings);

$I->amOnModulePage('User Admin', 'studentsSettings.php');
$I->submitForm('#content form', $originalStudentsSettings, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalStudentsSettings);


// Reset Data ------------------------------------------------
$I->amOnModulePage('User Admin', 'user_manage_edit.php', array('gibbonPersonID' => $gibbonPersonID, 'search' => ''));


$editFormValues = array(
    'title'                     => 'Ms.',
    'surname'                   => 'TestUser',
    'firstName'                 => 'Student',
    'preferredName'             => 'Student',
    'officialName'              => 'Student B. TestUser',
    'nameInCharacters'          => 'TM',
    'dob'                       => '30/01/2001',

    'emergency1Name'            => 'Emergency Person 1',
    'emergency1Relationship'    => 'Doctor',
    'emergency1Number1'         => '12345678',
    'emergency1Number2'         => '23456789',
    'emergency2Name'            => 'Emergency Person 2',
    'emergency2Relationship'    => 'Other',
    'emergency2Number1'         => '87654321',
    'emergency2Number2'         => '98765432',

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
    'visaExpiryDate'            => '30/01/2001',
    'residencyStatus'           => 'Status 3',
);
$I->submitForm('#content form', $editFormValues, 'Submit');
