<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete courses and classes');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'course_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Course');

$addFormValues = array(
    'name'               => 'Test Course',
    'nameShort'          => 'TEST01',
    'orderBy'            => '1',
    'description'        => 'This is a test.',
    'map'                => 'Y',
);

$I->selectFromDropdown('gibbonDepartmentID', 2);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonCourseID = $I->grabEditIDFromURL();
$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

// Edit ------------------------------------------------
$I->amOnModulePage('Timetable Admin', 'course_manage_edit.php', array('gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Edit Course');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'               => 'Test Course Too',
    'nameShort'          => 'TEST02',
    'orderBy'            => '2',
    'description'        => 'This is also a test.',
    'map'                => 'N',
);

$I->selectFromDropdown('gibbonDepartmentID', 3);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Add Class ---------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Class');

$addFormValues = array(
    'name'       => 'C-1',
    'nameShort'  => '1',
    'reportable' => 'Y',
    'attendance' => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonCourseClassID = $I->grabEditIDFromURL();

// Edit Class ---------------------------------------------
$I->amOnModulePage('Timetable Admin', 'course_manage_class_edit.php', array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Edit Class');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'       => 'C-2',
    'nameShort'  => '2',
    'reportable' => 'N',
    'attendance' => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete Class -------------------------------------------
$I->amOnModulePage('Timetable Admin', 'course_manage_class_delete.php', array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Delete Class');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Timetable Admin', 'course_manage_delete.php', array('gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Delete Course');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

