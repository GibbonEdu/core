<?php
/**
 * @covers modules/Reports/templates_assets.php
 * @covers modules/Reports/templates_assets_fonts.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates assets pages');
$I->loginAsAdmin();

// Templates Assets ------------------------------------------

$I->amOnModulePage('Reports', 'templates_assets.php');
$I->seeBreadcrumb('Manage Assets');
$I->dontSeeErrors();

// Templates Assets Fonts ------------------------------------

$I->amOnModulePage('Reports', 'templates_assets_fonts.php');
$I->seeBreadcrumb('Manage Fonts');
$I->dontSeeErrors();
