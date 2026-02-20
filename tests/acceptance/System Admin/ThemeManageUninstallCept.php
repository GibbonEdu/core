<?php
/**
 * @covers modules/System Admin/theme_manage_uninstall.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Uninstall Theme');
$I->loginAsAdmin();

// Get an inactive theme ID (we won't actually uninstall it, just check the page loads)
$gibbonThemeID = $I->grabFromDatabase('gibbonTheme', 'gibbonThemeID', ['active' => 'N']);

if ($gibbonThemeID) {
    $I->amOnModulePage('System Admin', 'theme_manage_uninstall.php', [
        'gibbonThemeID' => $gibbonThemeID,
    ]);
    $I->seeBreadcrumb('Uninstall Theme');

    // Basic Check -----------------------------------------

    $I->dontSeeErrors();
} else {
    $I->comment('No inactive theme found to test uninstall page');
}
