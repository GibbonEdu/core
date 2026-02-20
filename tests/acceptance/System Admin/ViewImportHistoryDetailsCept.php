<?php
/**
 * @covers modules/System Admin/import_history_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View import history details');
$I->loginAsAdmin();

// Get an existing import ID
$gibbonImportID = $I->grabFromDatabase('gibbonImport', 'gibbonImportID', []);

$I->amOnModulePage('System Admin', 'import_history_view.php', ['gibbonImportID' => $gibbonImportID]);
$I->seeBreadcrumb('Import History');
