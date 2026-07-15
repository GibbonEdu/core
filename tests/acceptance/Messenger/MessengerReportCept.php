<?php
/**
 * @covers modules/Messenger/messenger_manage_report.php
 * @covers modules/Messenger/messenger_manage_report_addRecipients.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view messenger send report and add recipients');
$I->loginAsAdmin();

// Create test message ---------------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$gibbonMessengerID = $I->haveInDatabase('gibbonMessenger', [
    'gibbonPersonID' => $gibbonPersonID,
    'subject' => 'Test Report Message',
    'body' => 'This is a test message for report testing.',
    'messageWall' => 'Y',
    'messageWall_dateStart' => date('Y-m-d'),
    'messageWall_dateEnd' => date('Y-m-d', strtotime('+7 days')),
    'email' => 'Y',
    'emailReport' => '',
    'emailReceipt' => 'Y',
    'sms' => 'N',
    'smsReport' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'Sent',
]);

// View Report -----------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_report.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);
$I->seeBreadcrumb('View Send Report');

$I->dontSeeErrors();

// Add Recipients --------------------------------------

$I->click('Add Recipients');
$I->seeInCurrentUrl('messenger_manage_report_addRecipients.php');

$I->dontSeeErrors();

// Clean up --------------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_delete.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
