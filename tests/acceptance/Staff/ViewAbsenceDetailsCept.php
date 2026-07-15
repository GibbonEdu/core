<?php
/**
 * @covers modules/Staff/absences_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View absence details');
$I->loginAsAdmin();

// Get an existing staff absence
$gibbonStaffAbsenceID = $I->grabFromDatabase('gibbonStaffAbsence', 'gibbonStaffAbsenceID', []);

$I->amOnModulePage('Staff', 'absences_view_details.php', ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID]);
