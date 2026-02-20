<?php
/**
 * @covers modules/System Admin/module_manage_uninstall.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Uninstall Module');
$I->loginAsAdmin();

// Get a module ID (we won't actually uninstall it, just check the page loads)
$gibbonModuleID = $I->grabFromDatabase('gibbonModule', 'gibbonModuleID', ['type' => 'Additional']);

$I->amOnModulePage('System Admin', 'module_manage_uninstall.php', [
    'gibbonModuleID' => $gibbonModuleID,
]);
$I->seeBreadcrumb('Uninstall Module');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
