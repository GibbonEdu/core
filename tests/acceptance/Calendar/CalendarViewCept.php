<?php
/**
 * @covers modules/Calendar/calendar_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Calendar');
$I->loginAsAdmin();

$I->amOnModulePage('Calendar', 'calendar_view.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
