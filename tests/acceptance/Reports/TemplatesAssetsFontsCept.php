<?php
/**
 * @covers modules/Reports/templates_assets_fonts_edit.php
 * @covers modules/Reports/templates_assets_fonts_preview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check templates assets fonts management');
$I->loginAsAdmin();

// Create test font
$gibbonReportTemplateFontID = $I->haveInDatabase('gibbonReportTemplateFont', [
    'fontName' => 'Test Font',
    'fontFamily' => 'TestFont',
    'fontType' => 'R',
]);

// Templates Assets Fonts Edit -------------------------------

$I->amOnModulePage('Reports', 'templates_assets_fonts_edit.php', [
    'gibbonReportTemplateFontID' => $gibbonReportTemplateFontID,
]);
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Templates Assets Fonts Preview ----------------------------

$I->amOnModulePage('Reports', 'templates_assets_fonts_preview.php', [
    'gibbonReportTemplateFontID' => $gibbonReportTemplateFontID,
]);
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportTemplateFont', ['gibbonReportTemplateFontID' => $gibbonReportTemplateFontID]);
