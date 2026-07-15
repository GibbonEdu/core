<?php
/**
 * @covers modules/Behaviour/behaviour_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Behaviour Records');
$I->loginAsAdmin();

$I->amOnModulePage('Behaviour', 'behaviour_view.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#filter', []);
$I->dontSeeErrors();
