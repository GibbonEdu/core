<?php
/**
 * @covers modules/Admissions/report_students_new.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check New Students');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'report_students_new.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

$I->click('Go');

// Report Print Test -----------------------------------

$I->click('Print');
$I->seeInCurrentUrl('format=print');
$I->dontSeeErrors();
