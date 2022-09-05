<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete an activity as an admin and test the enrollment and view of a student');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'activities_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Activity');

$I->fillField('name', 'T1 Test Activity');
$I->click('Submit');
$I->seeSuccessMessage();
$ID = $I->grabValueFromURL('editID');

// Edit ------------------------------------------------
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->click("Edit", "//td[contains(text(),'T1 Test Activity')]//..");
$I->seeBreadcrumb('Edit Activity');
$I->fillField('name', 'T2 Test Activity');
$I->click('Submit');
$I->seeSuccessMessage();

// Enroll ------------------------------------------------
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->click("Enrolment", "//td[contains(text(),'T2 Test Activity')]//..");
$I->seeBreadcrumb('Activity Enrolment');
$I->clickNavigation('Add');
$I->selectOption('Members[]', 'TestUser, Student (testingstudent)');
$I->click('Submit');
$I->seeSuccessMessage();

// View ------------------------------------------------
$I->click('Logout');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'activities_my.php');
$I->see('T2 Test Activity', 'td');


// Delete ------------------------------------------------
$I->click('Logout');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->click("Delete", "//td[contains(text(),'T2 Test Activity')]//..");
$I->click('Yes');
$I->seeSuccessMessage();


