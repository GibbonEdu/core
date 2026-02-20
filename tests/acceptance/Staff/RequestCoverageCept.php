<?php
/**
 * @covers modules/Staff/coverage_request.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Request coverage');
$I->loginAsAdmin();

// Get an existing staff absence
$gibbonStaffAbsenceID = $I->grabFromDatabase('gibbonStaffAbsence', 'gibbonStaffAbsenceID', []);

$I->amOnModulePage('Staff', 'coverage_request.php', ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID]);

