<?php
/**
 * @covers modules/System Admin/file_upload.php
 * @covers modules/System Admin/file_uploadPreview.php
 * @covers modules/System Admin/file_uploadProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('upload a ZIP of user photos');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'file_upload.php');
$I->seeBreadcrumb('Upload Photos & Files');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Save original photo for cleanup
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);
$originalPhoto = $I->grabFromDatabase('gibbonPerson', 'image_240', ['gibbonPersonID' => $gibbonPersonID]);

// Step 1 - Upload ZIP ---------------------------------

$I->attachFile('file', 'test_photos.zip');
$I->selectOption('type', 'userPhotos');

$I->submitForm('#content form', [], 'Submit');

// Step 2 - Preview and confirm ------------------------

$I->seeBreadcrumb('Step 2');
$I->dontSeeErrors();
$I->see('testingadmin');

$I->submitForm('#content form', [], 'Submit');

// Step 3 - Verify success -----------------------------

$I->seeBreadcrumb('Step 3');
$I->see('Import successful', '.success');

// Verify the photo was updated in DB
$newPhoto = $I->grabFromDatabase('gibbonPerson', 'image_240', ['gibbonPersonID' => $gibbonPersonID]);
$I->assertNotEmpty($newPhoto);

// Restore original photo and cleanup
$I->updateInDatabase('gibbonPerson', ['image_240' => $originalPhoto], ['gibbonPersonID' => $gibbonPersonID]);
$I->deleteFile('../'.$newPhoto);
