<?php
/**
 * @covers modules/System Admin/file_upload.php
 * @covers modules/System Admin/file_uploadPreview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Upload Photos & Files');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'file_upload.php');
$I->seeBreadcrumb('Upload Photos & Files');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
