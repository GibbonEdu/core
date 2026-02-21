<?php
/**
 * @covers modules/Reports/notification_send.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check notification send page');
$I->loginAsAdmin();

// Create test data
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Reporting Cycle',
    'nameShort' => 'Test',
    'sequenceNumber' => 1,
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
]);

// Notification Send -----------------------------------------

$I->amOnModulePage('Reports', 'notification_send.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
]);
$I->seeBreadcrumb('Send Notifications');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportingCycle', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
