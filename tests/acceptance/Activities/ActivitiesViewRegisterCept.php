<?php
/**
 * @covers modules/Activities/activities_view_register.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view activity register');
$I->loginAsAdmin();

// Get an active activity to test the register view
$gibbonActivityID = $I->grabFromDatabase('gibbonActivity', 'gibbonActivityID', [
    'active' => 'Y'
]);

if ($gibbonActivityID) {
    $I->amOnModulePage('Activities', 'activities_view_register.php', [
        'gibbonActivityID' => $gibbonActivityID
    ]);
    $I->dontSeeErrors();
}
