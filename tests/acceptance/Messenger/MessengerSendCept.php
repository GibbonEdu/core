<?php
/**
 * @covers modules/Messenger/messenger_send.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('preview and send a message');
$I->loginAsAdmin();

// Create test message ---------------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$gibbonMessengerID = $I->haveInDatabase('gibbonMessenger', [
    'gibbonPersonID' => $gibbonPersonID,
    'subject' => 'Test Send Message',
    'body' => 'This is a test message for send testing.',
    'messageWall' => 'Y',
    'messageWall_dateStart' => date('Y-m-d'),
    'messageWall_dateEnd' => date('Y-m-d', strtotime('+7 days')),
    'email' => 'N',
    'emailReport' => '',
    'sms' => 'N',
    'smsReport' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'Draft',
]);

// Preview & Send --------------------------------------

$I->amOnModulePage('Messenger', 'messenger_send.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);
$I->seeBreadcrumb('Preview & Send');

$I->dontSeeErrors();

// Clean up --------------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_delete.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
