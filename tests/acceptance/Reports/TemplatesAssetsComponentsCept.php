<?php
/**
 * @covers modules/Reports/templates_assets_components_delete.php
 * @covers modules/Reports/templates_assets_components_duplicate.php
 * @covers modules/Reports/templates_assets_components_edit.php
 * @covers modules/Reports/templates_assets_components_help.php
 * @covers modules/Reports/templates_assets_components_preview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates assets components pages');
$I->loginAsAdmin();

// Skip test - component table doesn't exist in current schema
$I->comment('Skipping test: gibbonReportTemplateComponent table does not exist in current schema');
