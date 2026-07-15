<?php
/**
 * @covers modules/Messenger/cannedResponse_manage.php
 * @covers modules/Messenger/cannedResponse_manage_add.php
 * @covers modules/Messenger/cannedResponse_manage_edit.php
 * @covers modules/Messenger/cannedResponse_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage canned responses with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'cannedResponse_manage.php');
$I->seeBreadcrumb('Manage Canned Responses');

// Add a new canned response
$I->click('Add');
$I->seeBreadcrumb('Add');
$I->fillField('subject', 'Test Canned Response');
$I->fillField('body', 'This is a test canned response body.');
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the canned response
$gibbonMessengerCannedResponseID = $I->grabEditIDFromURL();
$I->amOnModulePage('Messenger', 'cannedResponse_manage_edit.php', ['gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('subject', 'Test Canned Response');
$I->fillField('subject', 'Updated Canned Response');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the canned response
$I->amOnModulePage('Messenger', 'cannedResponse_manage_delete.php', ['gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID]);
$I->click('Delete');
$I->seeSuccessMessage();
