<?php 

$I = new AcceptanceTester($scenario);
$I->wantTo('login to Gibbon as an admin');
$I->amOnPage('/');

// Fill in Login form
$I->fillField('username', 'testingadmin');
$I->fillField('password', '7SSbB9FZN24Q');
$I->click('Login');

// Logged In
$I->see('System Admin', 'a');

// $I->seeInDatabase('gibbonPerson', ['gibbonPersonID' => 1]);
