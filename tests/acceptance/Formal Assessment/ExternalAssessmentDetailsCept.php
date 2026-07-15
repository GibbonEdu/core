<?php
/**
 * @covers modules/Formal Assessment/externalAssessment_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check External Assessment Details');
$I->loginAsAdmin();

// Get a student ID from the database
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->amOnModulePage('Formal Assessment', 'externalAssessment_details.php', [
    'gibbonPersonID' => $gibbonPersonID
]);
$I->seeBreadcrumb('Student Details');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
