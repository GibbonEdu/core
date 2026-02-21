<?php
/**
 * @covers modules/System Admin/import_run.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Import Run');
$I->loginAsAdmin();

// Note: This page requires specific parameters and file upload
// Testing the full import workflow would require complex setup
// This test verifies the page is accessible with proper permissions

$I->amOnModulePage('System Admin', 'import_run.php', [
    'type' => 'studentEnrolment'
]);
$I->dontSeeErrors();
