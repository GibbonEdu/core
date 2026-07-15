<?php
/**
 * @covers modules/Messenger/messenger_emailReceiptConfirm.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('confirm email receipt');
$I->loginAsAdmin();

// Create test message and receipt --------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$gibbonMessengerID = $I->haveInDatabase('gibbonMessenger', [
    'gibbonPersonID' => $gibbonPersonID,
    'subject' => 'Test Receipt Message',
    'body' => 'This is a test message for receipt testing.',
    'messageWall' => 'N',
    'email' => 'Y',
    'emailReport' => '',
    'emailReceipt' => 'Y',
    'sms' => 'N',
    'smsReport' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'Sent',
]);

$receiptKey = bin2hex(random_bytes(16));

$gibbonMessengerReceiptID = $I->haveInDatabase('gibbonMessengerReceipt', [
    'gibbonMessengerID' => $gibbonMessengerID,
    'gibbonPersonID' => $gibbonPersonID,
    'contactType' => 'Email',
    'contactDetail' => 'test@example.com',
    'key' => $receiptKey,
    'confirmed' => 'N',
    'sent' => 'Y',
    'targetType' => 'Role',
    'targetID' => '001',
]);

// Test Email Receipt Confirmation --------------------

$I->amOnModulePage('Messenger', 'messenger_emailReceiptConfirm.php', [
    'key' => 'test',
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonMessengerID' => $gibbonMessengerID,
]);

$I->see('Test Email');
$I->see('Thank you for confirming receipt and reading of this email');

// Clean up --------------------------------------------

$I->amOnModulePage('Messenger', 'messenger_manage_delete.php', [
    'gibbonMessengerID' => $gibbonMessengerID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
