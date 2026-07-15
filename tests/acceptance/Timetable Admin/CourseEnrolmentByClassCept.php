<?php
/**
 * @covers modules/Timetable Admin/courseEnrolment_manage.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_class_edit.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_class_edit_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage course enrolment by class');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'courseEnrolment_manage.php');
$I->seeBreadcrumb('Course Enrolment by Class');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Edit Action (DataTable action) ----------------

$I->click('Edit');
$I->seeInCurrentUrl('courseEnrolment_manage_class_edit.php');
$I->dontSeeErrors();

// Add a member to the class --------------------------

$I->selectFromDropdown('Members', 1);
$I->click('Submit');
$I->see('Your request was completed successfully.', '.success');

// Nested Edit action (DataTable) -----------------

$I->click('Edit', '.dataTable');
$I->seeInCurrentUrl('courseEnrolment_manage_class_edit_edit.php');
$I->dontSeeErrors();

$I->selectFromDropdown('role', 2);
$I->click('Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete action (DataTable) ---------------

$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');
$gibbonCourseID = $I->grabValueFromURL('gibbonCourseID');
$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

$I->amOnModulePage('Timetable Admin', 'courseEnrolment_manage_class_edit.php', array(
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));

$I->click('Delete', '#enrolment');
$I->seeInCurrentUrl('courseEnrolment_manage_class_edit_delete.php');
$I->dontSeeErrors();
