<?php 

$I = new AcceptanceTester($scenario);
$I->wantTo('login to Gibbon and logout');
$I->amOnPage('/');

// Fill in Login form
$I->fillField('username', 'testingsupport');
$I->fillField('password', '84BNQAQfNyKa');
$I->click('Login');

// Logged In
$I->see('Logout', 'a');
$I->see('Preferences', 'a');
$I->dontSee('Login');

// Logged back out
$I->click('Logout', 'a');
$I->see('Login');
$I->dontSee('Logout', 'a');
