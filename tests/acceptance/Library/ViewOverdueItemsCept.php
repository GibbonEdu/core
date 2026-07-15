<?php
/**
 * @covers modules/Library/report_viewOverdueItems.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View overdue items');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'report_viewOverdueItems.php');
$I->seeBreadcrumb('View Overdue Items');
