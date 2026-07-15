<?php
/**
 * @covers modules/Staff/jobOpenings_manage.php
 * @covers modules/Staff/jobOpenings_manage_add.php
 * @covers modules/Staff/jobOpenings_manage_edit.php
 * @covers modules/Staff/jobOpenings_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage job openings with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'jobOpenings_manage.php');
$I->seeBreadcrumb('Job Openings');

// Add a new job opening
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->selectFromDropdown('type', 1);
$I->fillField('jobTitle', 'Test Job Title');
$I->fillField('dateOpen', date('Y-m-d'));
$I->fillField('active', 'Y');
$I->fillField('description', 'Test job description');
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the job opening
$gibbonStaffJobOpeningID = $I->grabEditIDFromURL();
$I->amOnModulePage('Staff', 'jobOpenings_manage_edit.php', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('jobTitle', 'Test Job Title');
$I->fillField('jobTitle', 'Updated Job Title');
$I->click('Submit');
$I->seeSuccessMessage();


// Delete the job opening
$I->amOnModulePage('Staff', 'jobOpenings_manage_delete.php', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
$I->click('Delete');
$I->seeSuccessMessage();
