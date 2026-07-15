<?php
/**
 * @covers modules/School Admin/markbookSettings.php
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/weighting_manage.php
 * @covers modules/Markbook/weighting_manage_add.php
 * @covers modules/Markbook/weighting_manage_edit.php
 * @covers modules/Markbook/weighting_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage markbook weightings');
$I->loginAsAdmin();

// Enable Column Weighting Setting ---------------------------------
$I->amOnModulePage('School Admin', 'markbookSettings.php');
$originalMarkbookSettings = $I->grabAllFormValues();

$newMarkbookSettings = array_replace($originalMarkbookSettings, array(
    'enableColumnWeighting' => 'Y',
));

$I->submitForm('#content form', $newMarkbookSettings, 'Submit');
$I->seeSuccessMessage();

// Navigate to Markbook View
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#searchForm');

$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

// Navigate to Manage Weightings
$I->amOnModulePage('Markbook', 'weighting_manage.php', array('gibbonCourseClassID' => $gibbonCourseClassID));
$I->seeBreadcrumb('Weightings');

// Add Weighting ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Weighting');

$formValues = array(
    'type'                     => 'Test Weighting',
    'description'              => 'This is a test weighting.',
    'weighting'                => '50.00',
    'calculate'                => 'term',
    'reportable'               => 'Y',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonMarkbookWeightID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Markbook', 'weighting_manage_edit.php', array(
    'gibbonMarkbookWeightID' => $gibbonMarkbookWeightID,
    'gibbonCourseClassID' => $gibbonCourseClassID
));
$I->seeBreadcrumb('Edit Weighting');

$I->seeInFormFields('#content form', array(
    'description' => 'This is a test weighting.',
    'calculate' => 'term',
));

$editFormValues = array(
    'type'                     => 'Updated Weighting',
    'description'              => 'This is an updated weighting.',
    'weighting'                => '60',
    'calculate'                => 'year',
    'reportable'               => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Markbook', 'weighting_manage_delete.php', array(
    'gibbonMarkbookWeightID' => $gibbonMarkbookWeightID,
    'gibbonCourseClassID' => $gibbonCourseClassID
));

$I->click('Delete');
$I->seeSuccessMessage();

// Restore Original Settings -----------------------------------
$I->amOnModulePage('School Admin', 'markbookSettings.php');
$I->submitForm('#content form', $originalMarkbookSettings, 'Submit');
$I->seeSuccessMessage();

