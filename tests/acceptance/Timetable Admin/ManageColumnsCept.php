<?php
/**
 * @covers modules/Timetable Admin/ttColumn.php
 * @covers modules/Timetable Admin/ttColumn_add.php
 * @covers modules/Timetable Admin/ttColumn_edit.php
 * @covers modules/Timetable Admin/ttColumn_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a timetable column');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'ttColumn.php');
$I->seeBreadcrumb('Manage Columns');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Column');

$formValues = array(
    'name' => 'Test Column',
    'nameShort' => 'TC',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonTTColumnID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_edit.php', array('gibbonTTColumnID' => $gibbonTTColumnID));
$I->seeBreadcrumb('Edit Column');

$I->seeInFormFields('#content form', array(
    'name' => 'Test Column',
    'nameShort' => 'TC',
));

$formValues = array(
    'name' => 'Updated Test Column',
    'nameShort' => 'UTC',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_delete.php', array('gibbonTTColumnID' => $gibbonTTColumnID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
