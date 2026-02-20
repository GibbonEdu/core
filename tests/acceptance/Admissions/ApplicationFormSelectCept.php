<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check Admissions Welcome');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'applicationFormSelect.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
