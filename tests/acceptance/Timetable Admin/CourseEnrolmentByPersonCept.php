<?php
/**
 * @covers modules/Timetable Admin/courseEnrolment_manage_byPerson.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php
 * @covers modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check course enrolment by person');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'courseEnrolment_manage_byPerson.php');
$I->seeBreadcrumb('Course Enrolment by Person');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// All Users filter -------------------------------

$I->fillField('search', 'test');
$I->checkOption('allUsers');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

$I->fillField('search', '');
$I->uncheckOption('allUsers');
$I->submitForm('#searchForm', []);

// Edit Action (DataTable action) ----------------

$I->click('Edit', '#courseEnrolmentByPerson');
$I->seeInCurrentUrl('courseEnrolment_manage_byPerson_edit.php');
$I->dontSeeErrors();

$I->selectFromDropdown('Members', 1);
$I->click('Submit');
$I->see('Your request was completed successfully.', '.success');

// Nested Edit action (DataTable) -----------------

$I->click('Edit', '.dataTable');
$I->seeInCurrentUrl('courseEnrolment_manage_byPerson_edit_edit.php');
$I->dontSeeErrors();

$I->selectFromDropdown('role', 2);
$I->click('Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete action (DataTable) ---------------

$gibbonPersonID = $I->grabValueFromURL('gibbonPersonID');
$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');
$type = $I->grabValueFromURL('type');

$I->amOnModulePage('Timetable Admin', 'courseEnrolment_manage_byPerson_edit.php', array(
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'type' => $type
));

$I->click('Delete', '.dataTable');
$I->seeInCurrentUrl('courseEnrolment_manage_byPerson_edit_delete.php');
$I->dontSeeErrors();
