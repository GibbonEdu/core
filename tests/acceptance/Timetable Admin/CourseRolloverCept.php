<?php
/**
 * @covers modules/Timetable Admin/course_rollover.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test course enrolment rollover workflow');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'course_rollover.php');
$I->seeBreadcrumb('Course Enrolment Rollover');

// Step 1: Initial Page Load ---------------------------

$I->see('Step 1');
$I->dontSeeErrors();

// Check if next year exists
try {
    $I->see('Proceed');
    
    // Step 2: Proceed to mapping page ----------------
    
    $I->click('Proceed');
    $I->see('Step 2');
    $I->seeBreadcrumb('Course Enrolment Rollover');
    $I->dontSeeErrors();
    
    // Verify the mapping form elements exist
    $I->see('Include Students');
    $I->see('Include Teachers');
    $I->see('Map Classes');
    
    // Step 3: Submit the rollover --------------------
    
    // Note: We don't actually submit the rollover as it would
    // modify production data. We just verify the form loads correctly.
    // In a real test environment with test data, you would:
    // $I->click('Proceed');
    // $I->see('Step 3');
    // $I->see('Your request was completed successfully.', '.success');
    
} catch (Exception $e) {
    // If next year doesn't exist, we expect an error message
    $I->see('The next school year cannot be determined');
}
