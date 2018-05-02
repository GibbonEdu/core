<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Data Updater Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'dataUpdaterSettings.php');

$originalFormValues = $I->grabAllFormValues('#dataUpdaterSettingsFields');

// Make Changes ------------------------------------------------
$I->checkOption('title');
$I->checkOption('surname');
$I->checkOption('firstName');
$I->checkOption('preferredName');
$I->checkOption('officialName');
$I->checkOption('nameInCharacters');
$I->checkOption('dob');
$I->checkOption('email');
$I->checkOption('emailAlternate');
$I->checkOption('phone1');
$I->checkOption('phone2');
$I->checkOption('phone3');
$I->checkOption('phone4');
$I->checkOption('languageFirst');
$I->checkOption('languageSecond');
$I->checkOption('languageThird');
$I->checkOption('countryOfBirth');
$I->checkOption('ethnicity');
$I->checkOption('citizenship1');
$I->checkOption('citizenship1Passport');
$I->checkOption('citizenship2');
$I->checkOption('citizenship2Passport');
$I->checkOption('religion');
$I->checkOption('nationalIDCardNumber');
$I->checkOption('residencyStatus');
$I->checkOption('visaExpiryDate');
$I->checkOption('profession');
$I->checkOption('employer');
$I->checkOption('jobTitle');
$I->checkOption('emergency1Name');
$I->checkOption('emergency1Number1');
$I->checkOption('emergency1Number2');
$I->checkOption('emergency1Relationship');
$I->checkOption('emergency2Name');
$I->checkOption('emergency2Number1');
$I->checkOption('emergency2Number2');
$I->checkOption('emergency2Relationship');
$I->checkOption('vehicleRegistration');

$I->click('Submit');

// Verify Results ----------------------------------------------

$I->seeCheckboxIsChecked('title');
$I->seeCheckboxIsChecked('surname');
$I->seeCheckboxIsChecked('firstName');
$I->seeCheckboxIsChecked('preferredName');
$I->seeCheckboxIsChecked('officialName');
$I->seeCheckboxIsChecked('nameInCharacters');
$I->seeCheckboxIsChecked('dob');
$I->seeCheckboxIsChecked('email');
$I->seeCheckboxIsChecked('emailAlternate');
$I->seeCheckboxIsChecked('phone1');
$I->seeCheckboxIsChecked('phone2');
$I->seeCheckboxIsChecked('phone3');
$I->seeCheckboxIsChecked('phone4');
$I->seeCheckboxIsChecked('languageFirst');
$I->seeCheckboxIsChecked('languageSecond');
$I->seeCheckboxIsChecked('languageThird');
$I->seeCheckboxIsChecked('countryOfBirth');
$I->seeCheckboxIsChecked('ethnicity');
$I->seeCheckboxIsChecked('citizenship1');
$I->seeCheckboxIsChecked('citizenship1Passport');
$I->seeCheckboxIsChecked('citizenship2');
$I->seeCheckboxIsChecked('citizenship2Passport');
$I->seeCheckboxIsChecked('religion');
$I->seeCheckboxIsChecked('nationalIDCardNumber');
$I->seeCheckboxIsChecked('residencyStatus');
$I->seeCheckboxIsChecked('visaExpiryDate');
$I->seeCheckboxIsChecked('profession');
$I->seeCheckboxIsChecked('employer');
$I->seeCheckboxIsChecked('jobTitle');
$I->seeCheckboxIsChecked('emergency1Name');
$I->seeCheckboxIsChecked('emergency1Number1');
$I->seeCheckboxIsChecked('emergency1Number2');
$I->seeCheckboxIsChecked('emergency1Relationship');
$I->seeCheckboxIsChecked('emergency2Name');
$I->seeCheckboxIsChecked('emergency2Number1');
$I->seeCheckboxIsChecked('emergency2Number2');
$I->seeCheckboxIsChecked('emergency2Relationship');
$I->seeCheckboxIsChecked('vehicleRegistration');

// Cleanup -----------------------------------------------------
$I->submitForm('#content form', $originalFormValues);
$I->see('Your request was completed successfully.', '.success');
