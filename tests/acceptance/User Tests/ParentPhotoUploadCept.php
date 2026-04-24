<?php
/**
 * @covers index_parentPhotoUploadProcess.php
 * @covers src/UI/Components/Sidebar.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('upload a parent profile photo from the sidebar');

// Get the parent person ID
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingparent']);

// Save the original image_240 value so we can restore it
$originalImage = $I->grabFromDatabase('gibbonPerson', 'image_240', ['gibbonPersonID' => $gibbonPersonID]);

// Clear the parent's photo so the upload form appears in the sidebar
$I->updateInDatabase('gibbonPerson', ['image_240' => ''], ['gibbonPersonID' => $gibbonPersonID]);

// Login as parent and go to the homepage
$I->loginAsParent();
$I->amOnPage('/');

// The sidebar photo upload form should be visible
$I->see('Profile Photo');
$I->see('Please upload a passport photo');

// Upload a photo (240x320 jpg, meets dimension requirements)
$I->attachFile('file1', 'attachment.jpg');
$I->click('Go');
$I->seeSuccessMessage();

// Verify the photo was saved in the database
$uploadedImage = $I->grabFromDatabase('gibbonPerson', 'image_240', ['gibbonPersonID' => $gibbonPersonID]);
$I->assertNotEmpty($uploadedImage);

// Cleanup: restore original image and delete uploaded file
$I->updateInDatabase('gibbonPerson', ['image_240' => $originalImage], ['gibbonPersonID' => $gibbonPersonID]);

if (!empty($uploadedImage)) {
    $I->deleteFile('../'.$uploadedImage);
}
