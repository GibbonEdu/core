<?php
/**
 * @covers modules/Individual Needs/investigations_submit_detail.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View investigation details');
$I->loginAsAdmin();

// Get an existing investigation
$gibbonINInvestigationID = $I->grabFromDatabase('gibbonINInvestigation', 'gibbonINInvestigationID', []);

$I->amOnModulePage('Individual Needs', 'investigations_submit_detail.php', ['gibbonINInvestigationID' => $gibbonINInvestigationID]);
$I->seeBreadcrumb('Submit Contribution');
