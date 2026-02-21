<?php
/**
 * @covers modules/System Admin/theme_manage_uninstall.php
 * @covers modules/System Admin/theme_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Theme Management');
$I->loginAsAdmin();

// Test Theme Manage Page ------------------------------

$I->amOnModulePage('System Admin', 'theme_manage.php');
$I->seeBreadcrumb('Manage Themes');
$I->dontSeeErrors();

// Test Uninstall Theme Page ---------------------------

// Get an inactive theme ID (we won't actually uninstall it, just check the page loads)
$gibbonThemeID = $I->grabFromDatabase('gibbonTheme', 'gibbonThemeID', ['active' => 'N']);

if ($gibbonThemeID) {
    $I->amOnModulePage('System Admin', 'theme_manage_uninstall.php', [
        'gibbonThemeID' => $gibbonThemeID,
    ]);
    $I->seeBreadcrumb('Uninstall Theme');
    $I->dontSeeErrors();
} else {
    $I->comment('No inactive theme found to test uninstall page');
}
