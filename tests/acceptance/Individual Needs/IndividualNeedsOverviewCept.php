<?php
/**
 * @covers modules/Individual Needs/report_graph_overview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View individual needs overview');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'report_graph_overview.php');
$I->seeBreadcrumb('Individual Needs Overview');
