<?php
/**
 * @covers modules/Activities/activities_view.php
 * @covers modules/Activities/activities_view_full.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view activities as a student');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'activities_view.php');
$I->seeBreadcrumb('View Activities');

// Test search form
$I->submitForm('#searchForm', [
    'search' => 'Test',
]);
$I->seeInCurrentUrl('search=Test');

// Test Full View (if there are activities available) ----
$gibbonActivityID = $I->grabFromDatabase('gibbonActivity', 'gibbonActivityID', [
    'active' => 'Y'
]);

if ($gibbonActivityID) {
    $I->amOnModulePage('Activities', 'activities_view_full.php', [
        'gibbonActivityID' => $gibbonActivityID
    ]);
    $I->dontSeeErrors();
}
