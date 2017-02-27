<?php 

$I = new AcceptanceTester($scenario);
$I->wantTo('login to Gibbon as a student');
$I->loginAsStudent();

// Logged In
$I->see('Student Dashboard', 'h2');
