<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check Manage Applications');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'applications_manage.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
