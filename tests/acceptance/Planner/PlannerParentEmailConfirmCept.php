<?php
/**
 * @covers modules/Planner/planner_parentWeeklyEmailSummaryConfirm.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Parent Weekly Email Summary Confirmation');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get a student
$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

// Create a test email summary record
$key = uniqid();
$I->haveInDatabase('gibbonPlannerParentWeeklyEmailSummary', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonPersonIDParent' => $gibbonPersonIDStudent,
    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
    'weekOfYear' => date('W'),
    'key' => $key,
    'confirmed' => 'N'
]);

$I->amOnModulePage('Planner', 'planner_parentWeeklyEmailSummaryConfirm.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
    'gibbonPersonIDParent' => $gibbonPersonIDStudent,
    'key' => $key
]);

// Basic Check -----------------------------------------

$I->see('Thank you for confirming receipt and reading of this email.');
