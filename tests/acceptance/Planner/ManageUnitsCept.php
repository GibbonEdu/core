<?php
/**
 * @covers modules/Planner/units.php
 * @covers modules/Planner/units_add.php
 * @covers modules/Planner/units_edit.php
 * @covers modules/Planner/units_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage planner units');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'units.php');
$I->seeBreadcrumb('Unit Planner');
$I->dontSeeErrors();

// Get a course to work with
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);

if (empty($gibbonCourseID)) {
    $I->comment('No course found for current year, skipping unit test');
    return;
}

// Add ------------------------------------------------
$I->amOnModulePage('Planner', 'units_add.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
]);
$I->seeBreadcrumb('Add Unit');

$addFormValues = [
    'name'        => 'Test Unit Upload',
    'description' => 'Unit for testing file upload.',
];

$I->attachFile('file', 'attachment.txt');
$I->submitForm('#content form', $addFormValues, 'Submit');
$I->see('success', '.success');

$gibbonUnitID = $I->grabValueFromURL('gibbonUnitID');
$file = $I->grabFromDatabase('gibbonUnit', 'attachment', ['gibbonUnitID' => $gibbonUnitID]);
$I->assertNotEmpty($file);

// Edit ------------------------------------------------
$I->amOnModulePage('Planner', 'units_edit.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonUnitID' => $gibbonUnitID,
]);
$I->seeBreadcrumb('Edit Unit');

$I->fillField('attachment', '');
$I->submitForm('#content form', ['name' => 'Test Unit Upload Updated'], 'Submit');
$I->seeSuccessMessage();

$I->seeInDatabase('gibbonUnit', ['gibbonUnitID' => $gibbonUnitID, 'attachment' => '']);

// Edit - File Upload ------------------------------------------------
$I->amOnModulePage('Planner', 'units_edit.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonUnitID' => $gibbonUnitID,
]);

$I->attachFile('file', 'attachment2.png');
$I->submitForm('#content form', ['name' => 'Test Unit Upload Updated'], 'Submit');
$I->seeSuccessMessage();

$file2 = $I->grabFromDatabase('gibbonUnit', 'attachment', ['gibbonUnitID' => $gibbonUnitID]);
$I->assertNotEmpty($file2);

// Delete ------------------------------------------------
$I->amOnModulePage('Planner', 'units_delete.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonUnitID' => $gibbonUnitID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
