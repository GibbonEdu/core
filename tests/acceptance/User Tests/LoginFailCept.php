<?php 

$I = new AcceptanceTester($scenario);
$I->wantTo('fail to login to Gibbon');
$I->amOnPage('/');

// Fill in Login form - wrong username
$I->fillField('username', 'testingbogus');
$I->fillField('password', 'SomethingRandom');
$I->click('Login');

// Not Logged In
$I->see('Login');
$I->dontSee('Logout', 'a');

// Fill in Login form - wrong password
$I->fillField('username', 'testingsupport');
$I->fillField('password', 'SomethingRandom');
$I->click('Login');

// Not Logged In
$I->see('Login');
$I->dontSee('Logout', 'a');
