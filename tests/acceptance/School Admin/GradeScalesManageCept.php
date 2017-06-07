<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete grade scales');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'gradeScales_manage.php');

// Add Scale -------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Grade Scale');

$addFormValues = array(
    'name'      => 'Test Scale 1',
    'nameShort' => 'TS1',
    'usage'     => 'For testing.',
    'active'    => 'Y',
    'numeric'   => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonScaleID = $I->grabEditIDFromURL();

// Edit Scale -------------------------------------------
$I->amOnModulePage('School Admin', 'gradeScales_manage_edit.php', array('gibbonScaleID' => $gibbonScaleID));
$I->seeBreadcrumb('Edit Grade Scale');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'      => 'Test Scale 2',
    'nameShort' => 'TS2',
    'usage'     => 'Also for testing.',
    'active'    => 'N',
    'numeric'   => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Add Grade ---------------------------------------------
$I->amOnModulePage('School Admin', 'gradeScales_manage_edit_grade_add.php', array('gibbonScaleID' => $gibbonScaleID));
$I->seeBreadcrumb('Add Grade');

$addFormValues = array(
    'value'          => '42',
    'descriptor'     => '42!',
    'sequenceNumber' => '4200',
    'isDefault'      => 'N',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonScaleGradeID = $I->grabEditIDFromURL();

// Edit Grade --------------------------------------------
$I->amOnModulePage('School Admin', 'gradeScales_manage_edit_grade_edit.php', array('gibbonScaleID' => $gibbonScaleID, 'gibbonScaleGradeID' => $gibbonScaleGradeID));
$I->seeBreadcrumb('Edit Grade');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'value'          => '24',
    'descriptor'     => '24!',
    'sequenceNumber' => '2400',
    'isDefault'      => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Grade ------------------------------------------
$I->amOnModulePage('School Admin', 'gradeScales_manage_edit_grade_delete.php', array('gibbonScaleID' => $gibbonScaleID, 'gibbonScaleGradeID' => $gibbonScaleGradeID));
$I->seeBreadcrumb('Delete Grade');

$I->click('Yes');
$I->seeSuccessMessage();

// Delete Scale ------------------------------------------
$I->amOnModulePage('School Admin', 'gradeScales_manage_delete.php', array('gibbonScaleID' => $gibbonScaleID));
$I->seeBreadcrumb('Delete Grade Scale');

$I->click('Yes');
$I->seeSuccessMessage();
