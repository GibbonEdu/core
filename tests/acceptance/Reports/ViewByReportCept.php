<?php
/**
 * @covers modules/Reports/archive_byReport.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View by report');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'archive_byReport.php');
$I->seeBreadcrumb('View by Report');
