<?php
/**
 * @covers modules/Messenger/mailingListRecipients_manage.php
 * @covers modules/Messenger/mailingListRecipients_manage_add.php
 * @covers modules/Messenger/mailingListRecipients_manage_edit.php
 * @covers modules/Messenger/mailingListRecipients_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage mailing list recipients with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'mailingListRecipients_manage.php');
$I->seeBreadcrumb('Manage Mailing List Recipients');

// Add a new recipient
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('email', 'test@example.com');
$I->fillField('name', 'Test Recipient');
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the recipient
$gibbonMessengerMailingListRecipientID = $I->grabEditIDFromURL();
$I->amOnModulePage('Messenger', 'mailingListRecipients_manage_edit.php', ['gibbonMessengerMailingListRecipientID' => $gibbonMessengerMailingListRecipientID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('email', 'test@example.com');
$I->fillField('name', 'Updated Recipient');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the recipient
$I->amOnModulePage('Messenger', 'mailingListRecipients_manage_delete.php', ['gibbonMessengerMailingListRecipientID' => $gibbonMessengerMailingListRecipientID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
