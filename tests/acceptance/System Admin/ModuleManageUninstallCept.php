<?php
/**
 * @covers modules/System Admin/module_manage_uninstall.php
 * @covers modules/System Admin/module_manage.php
 * @covers modules/System Admin/module_manage_edit.php
 * @covers modules/System Admin/module_manage_update.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Module Management');
$I->loginAsAdmin();

// Test Module Manage Page -----------------------------

$I->amOnModulePage('System Admin', 'module_manage.php');
$I->seeBreadcrumb('Manage Modules');
$I->dontSeeErrors();

// Get a module ID
$gibbonModuleID = $I->grabFromDatabase('gibbonModule', 'gibbonModuleID', ['type' => 'Core']);

// Test Module Edit Page -------------------------------

$I->amOnModulePage('System Admin', 'module_manage_edit.php', [
    'gibbonModuleID' => $gibbonModuleID,
]);
$I->seeBreadcrumb('Edit Module');
$I->dontSeeErrors();

// Test Module Update Page -----------------------------

$I->amOnModulePage('System Admin', 'module_manage_update.php', [
    'gibbonModuleID' => $gibbonModuleID,
]);
$I->dontSeeErrors();

// Test Uninstall Module Page --------------------------

$gibbonModuleID = $I->grabFromDatabase('gibbonModule', 'gibbonModuleID', ['type' => 'Additional']);

$I->amOnModulePage('System Admin', 'module_manage_uninstall.php', [
    'gibbonModuleID' => $gibbonModuleID,
]);
$I->seeBreadcrumb('Uninstall Module');
$I->dontSeeErrors();
