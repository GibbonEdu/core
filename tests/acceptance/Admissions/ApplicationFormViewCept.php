<?php
/**
 * @covers modules/Admissions/applicationFormView.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check My Application Forms');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'applicationFormView.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
