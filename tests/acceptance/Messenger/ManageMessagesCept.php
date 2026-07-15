<?php
/**
 * @covers modules/Messenger/messenger_manage.php
 * @covers modules/Messenger/messenger_manage_edit.php
 * @covers modules/Messenger/messenger_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage messages');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'messenger_manage.php');
$I->seeBreadcrumb('Manage Messages');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Create test message ---------------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$gibbonMessengerID = $I->haveInDatabase('gibbonMessenger', [
    'gibbonPersonID' => $gibbonPersonID,
    'subject' => 'Test Message',
    'body' => 'This is a test message for acceptance testing.',
    'messageWall' => 'Y',
    'messageWall_dateStart' => date('Y-m-d'),
    'messageWall_dateEnd' => date('Y-m-d', strtotime('+7 days')),
    'email' => 'N',
    'emailReport' => '',
    'sms' => 'N',
    'smsReport' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'Sent',
]);

// Edit ------------------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_edit.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);
$I->seeBreadcrumb('Edit Message');

$I->seeInField('subject', 'Test Message');

$formValues = [
    'subject' => 'Updated Test Message',
    'body' => 'This is an updated test message.',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ----------------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_delete.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
