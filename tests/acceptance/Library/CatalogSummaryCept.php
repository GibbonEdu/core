<?php
/**
 * @covers modules/Library/report_catalogSummary.php
 * @covers modules/Library/report_catalogSummaryExportContents.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View catalog summary');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'report_catalogSummary.php');
$I->seeBreadcrumb('Catalog Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Export Test -----------------------------------------

$I->click('Export');
$I->seeInCurrentUrl('format=export');
$I->dontSeeErrors();
