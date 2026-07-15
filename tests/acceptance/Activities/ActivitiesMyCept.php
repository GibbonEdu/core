<?php
/**
 * @covers modules/Activities/activities_my.php
 * @covers modules/Activities/activities_my_full.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view my activities as a student');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'activities_my.php');
$I->seeBreadcrumb('My Activities');

// Test Full View (if there are activities enrolled) -----
// Get an activity ID that the student is enrolled in
$gibbonActivityID = $I->grabFromDatabase('gibbonActivityStudent', 'gibbonActivityID', [
    'gibbonPersonID' => $_SESSION['gibbonPersonID'] ?? null
]);

if ($gibbonActivityID) {
    $I->amOnModulePage('Activities', 'activities_my_full.php', [
        'gibbonActivityID' => $gibbonActivityID
    ]);
    $I->dontSeeErrors();
}
