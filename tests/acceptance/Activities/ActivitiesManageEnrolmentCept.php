<?php
/**
 * @covers modules/Activities/activities_manage.php
 * @covers modules/Activities/activities_manage_add.php
 * @covers modules/Activities/activities_manage_enrolment.php
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

// Clean up - delete the activity
$I->amOnModulePage('Activities', 'activities_manage.php');
$I->click('Delete', "//td[contains(text(),'Test Activity for Enrolment')]/..");
$I->click('Delete');
$I->seeSuccessMessage();
