<?php
/**
 * @covers modules/Admissions/studentEnrolment_manage.php
 * @covers modules/Admissions/studentEnrolment_manage_add.php
 * @covers modules/Admissions/studentEnrolment_manage_edit.php
 * @covers modules/Admissions/studentEnrolment_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a student enrolment');
$I->loginAsAdmin();
$I->amOnModulePage('Admissions', 'studentEnrolment_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Student Enrolment');

$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->selectFromDropdown('gibbonFormGroupID', 1);

$formValues = array(
    'gibbonPersonID'   => '0000002775',
    'rollOrder'        => '1',
    'autoEnrolStudent' => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonStudentEnrolmentID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Admissions', 'studentEnrolment_manage_edit.php', array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Edit Student Enrolment');

$I->seeInFormFields('#content form', array(
    'rollOrder' => '1',
));

$I->selectFromDropdown('gibbonYearGroupID', 2);
$I->selectFromDropdown('gibbonFormGroupID', 2);

$formValues = array(
    'rollOrder'           => '2',
    'autoEnrolStudent'    => 'Y',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Admissions', 'studentEnrolment_manage_delete.php', array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
