<?php
/**
 * @covers modules/Messenger/messenger_post.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('create a new message');
$I->loginAsAdmin();

// Set temporary email for admin user ------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);
$originalEmail = $I->grabFromDatabase('gibbonPerson', 'email', ['gibbonPersonID' => $gibbonPersonID]);

$I->updateInDatabase('gibbonPerson', ['email' => 'testingadmin@example.com'], ['gibbonPersonID' => $gibbonPersonID]);

// Re-login to refresh session with new email ---------

$I->amOnPage('/logout.php');
$I->loginAsAdmin();

// Test New Message Page -------------------------------

$I->amOnModulePage('Messenger', 'messenger_post.php');
$I->seeBreadcrumb('New Message');

$I->dontSeeErrors();

// Restore original email ------------------------------

$I->updateInDatabase('gibbonPerson', ['email' => $originalEmail], ['gibbonPersonID' => $gibbonPersonID]);
