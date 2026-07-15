<?php
/**
 * @covers modules/Behaviour/behaviour_pattern.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Find Behaviour Patterns');
$I->loginAsAdmin();

$I->amOnModulePage('Behaviour', 'behaviour_pattern.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('type', 1);

$I->submitForm('#filter', []);

$I->dontSeeErrors();

// Report Print Test -----------------------------------------

$I->click('Print');

$I->seeInCurrentUrl('format=print');

$I->dontSeeErrors();


