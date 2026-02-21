<?php
/**
 * @covers modules/Staff/report_subs_availability.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view the substitute availability report');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'report_subs_availability.php');

// Check page loads
$I->seeBreadcrumb('Substitute Availability');

// Check form elements exist
$I->seeElement('#date');
$I->seeElement('#allDay');

