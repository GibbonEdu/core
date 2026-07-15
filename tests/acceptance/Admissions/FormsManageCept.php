<?php
/**
 * @covers modules/Admissions/forms_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Manage Other Forms');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'forms_manage.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
