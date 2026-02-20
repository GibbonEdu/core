<?php
/**
 * @covers modules/Calendar/calendar_viewInternal.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View internal calendar');
$I->loginAsAdmin();

// This is an API endpoint that returns JSON, so we just check it doesn't error
$I->amOnModulePage('Calendar', 'calendar_viewInternal.php');
$I->dontSeeErrors();
