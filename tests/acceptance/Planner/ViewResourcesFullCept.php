<?php
/**
 * @covers modules/Planner/resources_view_full.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View full resource details');
$I->loginAsAdmin();

// Get an existing resource ID
$gibbonResourceID = $I->grabFromDatabase('gibbonResource', 'gibbonResourceID', []);

$I->amOnModulePage('Planner', 'resources_view_full.php', ['gibbonResourceID' => $gibbonResourceID]);
