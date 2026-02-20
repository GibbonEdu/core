<?php
/**
 * @covers modules/Behaviour/behaviour_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View behaviour details');
$I->loginAsAdmin();

// Get an existing behaviour record
$gibbonBehaviourID = $I->grabFromDatabase('gibbonBehaviour', 'gibbonBehaviourID', []);

$I->amOnModulePage('Behaviour', 'behaviour_view_details.php', ['gibbonBehaviourID' => $gibbonBehaviourID]);
$I->seeBreadcrumb('View Behaviour Records');
