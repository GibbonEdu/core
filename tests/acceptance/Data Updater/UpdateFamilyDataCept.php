<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('submit and approve a family data update');
$I->loginAsParent();
$I->amOnModulePage('Data Updater', 'data_family.php');

// Select ------------------------------------------------
$I->seeBreadcrumb('Update Family Data');

$I->selectFromDropdown('gibbonFamilyID', 2);
$I->click('Submit');

// Update ------------------------------------------------
$I->see('Update Data');

$editFormValues = array(
    'nameAddress'           => '234',
    'homeAddress'           => '234 Ficticious Ave.',
    'homeAddressDistrict'   => 'Somewhere',
    'homeAddressCountry'    => 'Antarctica',
    'languageHomePrimary'   => 'English',
    'languageHomeSecondary' => 'Latin',
);
// $I->fillField('existing', 'N');

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();

$gibbonFamilyID = $I->grabValueFromURL('gibbonFamilyID');

$I->amOnModulePage('Data Updater', 'data_family.php', ['gibbonFamilyID' => $gibbonFamilyID]);
$I->seeInFormFields('#content form[method="post"]', $editFormValues);

$gibbonFamilyUpdateID = $I->grabValueFrom("input[type='hidden'][name='existing']");

$I->click('Logout', 'a');

// Accept ------------------------------------------------
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_family_manage_edit.php', array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID));
$I->seeBreadcrumb('Edit Request');

$I->see('234', 'td');
$I->see('234 Ficticious Ave.', 'td');
$I->see('Somewhere', 'td');
$I->see('Antarctica', 'td');
$I->see('English', 'td');
$I->see('Latin', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonFamilyUpdateID = $I->grabValueFromURL('gibbonFamilyUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_family_manage_delete.php', array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID));

$I->click('Yes');
$I->seeSuccessMessage();

// Reset Data ------------------------------------------------
$I->amOnModulePage('User Admin', 'family_manage_edit.php', array('gibbonFamilyID' => $gibbonFamilyID));

$editFormValues = array(
    'name'                  => 'Testing Family',
    'status'                => 'Other',
    'languageHomePrimary'   => 'Mongolian',
    'languageHomeSecondary' => 'Latin',
    'nameAddress'           => 'Mr. & Mrs. Test Family Too',
    'homeAddress'           => '123 Nowhere St.',
    'homeAddressDistrict'   => 'Testland',
    'homeAddressCountry'    => 'Antarctica',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
