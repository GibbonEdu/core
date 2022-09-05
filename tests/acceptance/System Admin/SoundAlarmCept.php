<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('trigger an alarm');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'alarm.php');

// Fill in Fields --------------------------------------

$I->selectOption('alarm', 'General');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');
$I->seeOptionIsSelected('alarm', 'General');

// Turn off alarm --------------------------------------

$I->selectOption('alarm', 'None');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');
