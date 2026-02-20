<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('View catalog summary');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'report_catalogSummary.php');
$I->seeBreadcrumb('Catalog Summary');
