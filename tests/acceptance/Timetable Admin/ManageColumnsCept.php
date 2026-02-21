<?php
/**
 * @covers modules/Timetable Admin/ttColumn.php
 * @covers modules/Timetable Admin/ttColumn_add.php
 * @covers modules/Timetable Admin/ttColumn_edit.php
 * @covers modules/Timetable Admin/ttColumn_edit_row_add.php
 * @covers modules/Timetable Admin/ttColumn_edit_row_edit.php
 * @covers modules/Timetable Admin/ttColumn_edit_row_delete.php
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

// Add Row (nested management) ---------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_edit.php', array('gibbonTTColumnID' => $gibbonTTColumnID));
$I->seeBreadcrumb('Edit Column');

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Column Row');

$rowFormValues = array(
    'name' => 'Period 1',
    'nameShort' => 'P1',
    'timeStart' => '08:00',
    'timeEnd' => '09:00',
);

$I->selectFromDropdown('type', 1);

$I->submitForm('#content form', $rowFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonTTColumnRowID = $I->grabEditIDFromURL();

// Edit Row (nested management) --------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_edit_row_edit.php', array(
    'gibbonTTColumnRowID' => $gibbonTTColumnRowID,
    'gibbonTTColumnID' => $gibbonTTColumnID
));
$I->seeBreadcrumb('Edit Column Row');

$I->seeInFormFields('#content form', array(
    'name' => 'Period 1',
    'nameShort' => 'P1',
));

$updatedRowFormValues = array(
    'name' => 'Period 1 Updated',
    'nameShort' => 'P1U',
    'timeStart' => '08:30',
    'timeEnd' => '09:30',
);

$I->selectFromDropdown('type', 2);

$I->submitForm('#content form', $updatedRowFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete Row (nested management) ------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_edit_row_delete.php', array(
    'gibbonTTColumnRowID' => $gibbonTTColumnRowID,
    'gibbonTTColumnID' => $gibbonTTColumnID
));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Timetable Admin', 'ttColumn_delete.php', array('gibbonTTColumnID' => $gibbonTTColumnID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
