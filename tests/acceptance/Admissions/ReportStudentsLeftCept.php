<?php
/**
 * @covers modules/Admissions/report_students_left.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Left Students');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'report_students_left.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

$I->click('Go');

// Report Print Test -----------------------------------

$I->click('Print');
$I->seeInCurrentUrl('format=print');
$I->dontSeeErrors();
