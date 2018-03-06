<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a family');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'family_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Family');

$addFormValues = array(
    'name'                  => 'Test Family',
    'status'                => 'De Facto',
    'languageHomePrimary'   => 'Swedish',
    'languageHomeSecondary' => 'Hindi',
    'nameAddress'           => 'Mr. & Mrs. Test Family',
    'homeAddress'           => '1 2 3 Ficticious Lane',
    'homeAddressDistrict'   => 'Testing',
    'homeAddressCountry'    => 'Hong Kong',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFamilyID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('User Admin', 'family_manage_edit.php', array('gibbonFamilyID' => $gibbonFamilyID));
$I->seeBreadcrumb('Edit Family');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'                  => 'Test Family Too',
    'status'                => 'Other',
    'languageHomePrimary'   => 'Mongolian',
    'languageHomeSecondary' => 'Latin',
    'nameAddress'           => 'Mr. & Mrs. Test Family Too',
    'homeAddress'           => '123 Nowhere St.',
    'homeAddressDistrict'   => 'Testland',
    'homeAddressCountry'    => 'Antarctica',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('User Admin', 'family_manage_delete.php', array('gibbonFamilyID' => $gibbonFamilyID));
$I->seeBreadcrumb('Delete Family');

$I->click('Yes');
$I->seeSuccessMessage();