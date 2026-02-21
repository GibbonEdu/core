<?php
/**
 * @covers modules/Markbook/markbook_viewExportContents.php
 * @covers modules/Markbook/markbook_viewExportAllContents.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('export markbook data');
$I->loginAsAdmin();

// Get a course class ID from the database
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);

if (empty($gibbonCourseClassID)) {
    $I->comment('No course classes found, skipping export tests');
    return;
}

// Get a markbook column ID from the database
$gibbonMarkbookColumnID = $I->grabFromDatabase('gibbonMarkbookColumn', 'gibbonMarkbookColumnID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

// Test Export Single Column --------------------------

if (!empty($gibbonMarkbookColumnID)) {
    $I->amOnModulePage('Markbook', 'markbook_viewExport.php', [
        'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID,
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'return' => 'markbook_view.php'
    ]);
    
    // This page loads the export contents
    $I->dontSeeErrors();
}

// Test Export All Columns ----------------------------

$I->amOnModulePage('Markbook', 'markbook_viewExportAll.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'return' => 'markbook_view.php'
]);

// This page loads the export all contents
$I->dontSeeErrors();

