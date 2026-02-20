<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check New Students');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'report_students_new.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
