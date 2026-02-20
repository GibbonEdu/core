<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check Withdraw Student');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'student_withdraw.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
