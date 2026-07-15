<?php
/**
 * @covers modules/Activities/explore.php
 * @covers modules/Activities/explore_activity.php
 * @covers modules/Activities/explore_activity_signUp.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('explore activities and view activity details with sign-up');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'explore.php');
$I->seeBreadcrumb('Explore Activities');

// Test Activity View (if there are activities available) 
$gibbonActivityID = $I->grabFromDatabase('gibbonActivity', 'gibbonActivityID', [
    'active' => 'Y'
]);

if ($gibbonActivityID) {
    $I->amOnModulePage('Activities', 'explore_activity.php', [
        'gibbonActivityID' => $gibbonActivityID
    ]);
    $I->seeBreadcrumb('Activity');
    $I->dontSeeErrors();
    
    // Test Sign Up Action (if available) --------------------
    $I->amOnModulePage('Activities', 'explore_activity_signUp.php', [
        'gibbonActivityID' => $gibbonActivityID
    ]);
    $I->dontSeeErrors();
}
