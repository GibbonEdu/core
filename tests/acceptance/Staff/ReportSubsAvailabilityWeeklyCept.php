<?php
/**
 * @covers modules/Staff/report_subs_availabilityWeekly.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view the weekly substitute availability report');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'report_subs_availabilityWeekly.php');

// Check page loads
$I->seeBreadcrumb('Substitute Availability');

// Check form elements exist
$I->seeElement('#dateStart');

// Check DataTable exists
$I->seeElement('.dataTable');
