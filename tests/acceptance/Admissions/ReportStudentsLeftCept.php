<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check Left Students');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'report_students_left.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
