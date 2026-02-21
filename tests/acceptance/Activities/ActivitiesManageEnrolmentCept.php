<?php
/**
 * @covers modules/Activities/activities_manage.php
 * @covers modules/Activities/activities_manage_add.php
 * @covers modules/Activities/activities_manage_enrolment.php
 * @covers modules/Activities/activities_manage_enrolment_edit.php
 * @covers modules/Activities/activities_manage_enrolment_delete.php
 * @covers modules/Activities/activities_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage activity enrolment for a specific activity');
$I->loginAsAdmin();

// First create an activity to test enrolment
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->clickNavigation('Add');
$I->fillField('name', 'Test Activity for Enrolment');
$I->click('Submit');
$I->seeSuccessMessage();
$gibbonActivityID = $I->grabValueFromURL('editID');

// Now go to the enrolment page
$I->amOnModulePage('Activities', 'activities_manage_enrolment.php', ['gibbonActivityID' => $gibbonActivityID]);
$I->seeBreadcrumb('Activity Enrolment');

// Add a student to test edit and delete
$I->clickNavigation('Add');
$I->selectOption('Members[]', 'TestUser, Student (testingstudent)');
$I->click('Submit');
$I->seeSuccessMessage();

// Get the student's gibbonPersonID for edit/delete operations
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', [
    'username' => 'testingstudent'
]);

// Get the enrolment ID for edit operations
$gibbonActivityStudentID = $I->grabFromDatabase('gibbonActivityStudent', 'gibbonActivityStudentID', [
    'gibbonActivityID' => $gibbonActivityID,
    'gibbonPersonID' => $gibbonPersonID
]);

// Test Edit Enrolment -----------------------------------
if ($gibbonActivityStudentID) {
    $I->amOnModulePage('Activities', 'activities_manage_enrolment_edit.php', [
        'gibbonActivityID' => $gibbonActivityID,
        'gibbonActivityStudentID' => $gibbonActivityStudentID
    ]);
    $I->seeBreadcrumb('Edit Enrolment');
    $I->dontSeeErrors();
}
    
// Test Delete Enrolment ---------------------------------
if ($gibbonPersonID) {
    $I->amOnModulePage('Activities', 'activities_manage_enrolment_delete.php', [
        'gibbonActivityID' => $gibbonActivityID,
        'gibbonPersonID' => $gibbonPersonID,
        'search' => '',
        'gibbonSchoolYearTermID' => ''
    ]);
    $I->dontSeeErrors();
    // Note: Not actually deleting to avoid breaking the test activity
}

// Clean up - delete the activity
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->click('Delete', "//td[contains(text(),'Test Activity for Enrolment')]/..");
$I->click('Delete');
$I->seeSuccessMessage();
