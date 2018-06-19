<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('create a markbook column and enter data');
$I->loginAsAdmin();


// Change User Settings ---------------------------------
$I->amOnModulePage('School Admin', 'markbookSettings.php');
$originalMarkbookSettings = $I->grabAllFormValues();

$newMarkbookSettings = array_replace($originalMarkbookSettings, array(
    'enableEffort'          => 'Y',
    'enableRubrics'         => 'Y',
    'enableColumnWeighting' => 'Y',
    'enableRawAttainment'   => 'Y',
    'enableGroupByTerm'     => 'Y',
));

$I->submitForm('#content form', $newMarkbookSettings, 'Submit');
$I->seeSuccessMessage();
$I->seeInFormFields('#content form', $newMarkbookSettings);


// Select Markbook ------------------------------------------------
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#search');


// Add Column ------------------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Column');

$formValues = array(
    'name'                     => 'Test Column',
    'description'              => 'This is a test.',
    'type'                     => 'Homework',
    'attainment'               => 'N',
    'effort'                   => 'N',
    'comment'                  => 'N',
    'uploadedResponse'         => 'N',
    'viewableStudents'         => 'N',
    'viewableParents'          => 'N',
    'completeDate'             => '01/01/2021',
);

$I->attachFile('file', 'attachment.jpg');

$date = $I->grabAttributeFrom('#date', 'value');

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonMarkbookColumnID = $I->grabEditIDFromURL();
$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

// Edit ------------------------------------------------
$I->amOnModulePage('Markbook', 'markbook_edit_edit.php', array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID));
$I->seeBreadcrumb('Edit Column');

$I->seeInFormFields('#content form', $formValues);
$I->seeInField('date', $date);
$I->seeFieldIsNotEmpty('#attachment');

$editFormValues = array(
    'name'                     => 'Test Column!',
    'description'              => 'This is also a test.',
    'type'                     => 'Essay',
    'attainment'               => 'Y',
    'attainmentRawMax'         => '42',
    'attainmentWeighting'      => '2.0',
    'effort'                   => 'Y',
    'comment'                  => 'Y',
    'uploadedResponse'         => 'Y',
    'viewableStudents'         => 'Y',
    'viewableParents'          => 'Y',
    'completeDate'             => '02/02/2022',
);

$I->selectOption('gibbonScaleIDAttainment', '00007');
$I->selectOption('gibbonScaleIDEffort', '00009');
$I->selectOption('gibbonRubricIDAttainment', '00000159');
$I->selectOption('gibbonRubricIDEffort', '00000159');

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Verify Column ------------------------------------------------

$I->seeInFormFields('#content form', $editFormValues);

$I->seeOptionIsSelected('gibbonScaleIDAttainment', 'International College HK');
$I->seeOptionIsSelected('gibbonScaleIDEffort', 'Completion');
$I->seeFieldIsNotEmpty('#gibbonRubricIDAttainment');
$I->seeFieldIsNotEmpty('#gibbonRubricIDEffort');

$I->clickNavigation('Enter Data');

// Enter Data ------------------------------------------------

$I->seeBreadcrumb('Enter Marks');

$I->see('More info');

$I->fillField('1-attainmentValueRaw', '21');
$I->selectOption('1-attainmentValue', '4');
$I->selectOption('1-effortValue', 'Late');
$I->fillField('comment1', 'Test comment.');
$I->attachFile('response1', 'attachment.jpg');

$I->click('Submit');

// Verify Data ------------------------------------------------

$I->seeInField('1-attainmentValueRaw', '21');
$I->seeOptionIsSelected('1-attainmentValue', '4');
$I->seeOptionIsSelected('1-effortValue', 'Late');
$I->seeInField('comment1', 'Test comment.');
$I->seeFieldIsNotEmpty('#attachment1');

$I->seeInField('completeDate', '02/02/2022');

// Delete Markbook -----------------------------------------------

$urlParams = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
$I->amOnModulePage('Markbook', 'markbook_edit_delete.php', $urlParams );

$I->seeBreadcrumb('Delete Column');
$I->click('Yes');
$I->seeSuccessMessage();

// Force Cleanup (for failed tests) ------------------------------

$I->deleteFromDatabase('gibbonMarkbookEntry', ['gibbonMarkbookColumnID' => $gibbonMarkbookColumnID]);

// Restore Original Settings -----------------------------------

$I->amOnModulePage('School Admin', 'markbookSettings.php');
$I->submitForm('#content form', $originalMarkbookSettings, 'Submit');
$I->seeSuccessMessage();
$I->seeInFormFields('#content form', $originalMarkbookSettings);
