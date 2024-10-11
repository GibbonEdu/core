<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Data Updater Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'dataUpdaterSettings.php');

$originalFormValues = $I->grabAllFormValues('#dataUpdaterSettingsFields');

// Make Changes ------------------------------------------------

$I->selectOption('settings[Staff][title]', 'required');
$I->selectOption('settings[Staff][surname]', 'required');
$I->selectOption('settings[Staff][firstName]', 'required');
$I->selectOption('settings[Staff][preferredName]', 'required');
$I->selectOption('settings[Staff][officialName]', 'required');
$I->selectOption('settings[Staff][nameInCharacters]', 'required');
$I->selectOption('settings[Staff][dob]', 'required');
$I->selectOption('settings[Staff][email]', 'required');
$I->selectOption('settings[Staff][emailAlternate]', 'required');
$I->selectOption('settings[Staff][phone1]', 'required');
$I->selectOption('settings[Staff][phone2]', 'required');
$I->selectOption('settings[Staff][phone3]', 'required');
$I->selectOption('settings[Staff][phone4]', 'required');
$I->selectOption('settings[Staff][languageFirst]', 'required');
$I->selectOption('settings[Staff][languageSecond]', 'required');
$I->selectOption('settings[Staff][languageThird]', 'required');
$I->selectOption('settings[Staff][countryOfBirth]', 'required');
$I->selectOption('settings[Staff][ethnicity]', 'required');
$I->selectOption('settings[Staff][religion]', 'required');
$I->selectOption('settings[Staff][profession]', 'required');
$I->selectOption('settings[Staff][employer]', 'required');
$I->selectOption('settings[Staff][jobTitle]', 'required');
$I->selectOption('settings[Staff][emergency1Name]', 'required');
$I->selectOption('settings[Staff][emergency1Number1]', 'required');
$I->selectOption('settings[Staff][emergency1Number2]', 'required');
$I->selectOption('settings[Staff][emergency1Relationship]', 'required');
$I->selectOption('settings[Staff][emergency2Name]', 'required');
$I->selectOption('settings[Staff][emergency2Number1]', 'required');
$I->selectOption('settings[Staff][emergency2Number2]', 'required');
$I->selectOption('settings[Staff][emergency2Relationship]', 'required');
$I->selectOption('settings[Staff][vehicleRegistration]', 'required');

$I->click('#dataUpdaterSettingsFields [type=submit]');

// Verify Results ----------------------------------------------

$I->seeInField('settings[Staff][title]', 'required');
$I->seeInField('settings[Staff][surname]', 'required');
$I->seeInField('settings[Staff][firstName]', 'required');
$I->seeInField('settings[Staff][preferredName]', 'required');
$I->seeInField('settings[Staff][officialName]', 'required');
$I->seeInField('settings[Staff][nameInCharacters]', 'required');
$I->seeInField('settings[Staff][dob]', 'required');
$I->seeInField('settings[Staff][email]', 'required');
$I->seeInField('settings[Staff][emailAlternate]', 'required');
$I->seeInField('settings[Staff][phone1]', 'required');
$I->seeInField('settings[Staff][phone2]', 'required');
$I->seeInField('settings[Staff][phone3]', 'required');
$I->seeInField('settings[Staff][phone4]', 'required');
$I->seeInField('settings[Staff][languageFirst]', 'required');
$I->seeInField('settings[Staff][languageSecond]', 'required');
$I->seeInField('settings[Staff][languageThird]', 'required');
$I->seeInField('settings[Staff][countryOfBirth]', 'required');
$I->seeInField('settings[Staff][ethnicity]', 'required');
$I->seeInField('settings[Staff][religion]', 'required');
$I->seeInField('settings[Staff][profession]', 'required');
$I->seeInField('settings[Staff][employer]', 'required');
$I->seeInField('settings[Staff][jobTitle]', 'required');
$I->seeInField('settings[Staff][emergency1Name]', 'required');
$I->seeInField('settings[Staff][emergency1Number1]', 'required');
$I->seeInField('settings[Staff][emergency1Number2]', 'required');
$I->seeInField('settings[Staff][emergency1Relationship]', 'required');
$I->seeInField('settings[Staff][emergency2Name]', 'required');
$I->seeInField('settings[Staff][emergency2Number1]', 'required');
$I->seeInField('settings[Staff][emergency2Number2]', 'required');
$I->seeInField('settings[Staff][emergency2Relationship]', 'required');
$I->seeInField('settings[Staff][vehicleRegistration]', 'required');

// Cleanup -----------------------------------------------------
$I->submitForm('#dataUpdaterSettingsFields', $originalFormValues);
$I->see('Your request was completed successfully.', '.success');
