<?php 

$I = new AcceptanceTester($scenario);
$I->wantTo('login to Gibbon as an admin');
$I->loginAsAdmin();

// Logged In
$I->see('Staff Dashboard', 'h2');
$I->see('System Admin', 'a');
