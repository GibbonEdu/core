<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('reset my password');
$I->loginAsAdmin();

$I->amOnPage('/index.php?q=preferences.php');

// Change Password
$I->fillField('password', '7SSbB9FZN24Q');
$I->fillField('passwordNew', 'UnFZLTtJ9!');
$I->fillField('passwordConfirm', 'UnFZLTtJ9!');
$I->click('Submit');

$I->seeSuccessMessage();

// Logout
$I->click('Logout', 'a');
$I->see('Login');

// Try new password
$I->fillField('username', 'testingadmin');
$I->fillField('password', 'UnFZLTtJ9!');
$I->click('Login');

// Logged In
$I->see('Staff Dashboard', 'h2');
$I->see('System Admin', 'a');

// Restore original password
$I->updateFromDatabase('gibbonPerson', array(
    'passwordStrong' => '015261d879c7fc2789d19b9193d189364baac34a98561fa205cd5f37b313cdb0', 
    'passwordStrongSalt' => '/aBcEHKLnNpPrsStTUyz47',
    'failCount' => '0',
), array('username' => 'testingadmin'));