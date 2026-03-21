<?php
/**
 * @covers modules/Planner/planner_view_full.php
 * @covers modules/Planner/planner_view_full_post.php
 * @covers modules/Planner/planner_view_full_submitProcess.php
 * @covers modules/Planner/planner_view_full_submit_edit.php
 * @covers modules/Planner/planner_view_full_submit_editProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Lesson Plan with homework file upload');
$I->loginAsAdmin();

// Use a class from an existing planner entry (ensures valid course/class)
$gibbonCourseClassID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonCourseClassID', []);

// Get a student from this class
$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonCourseClassPerson', 'gibbonPersonID', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'role' => 'Student',
]);

// Enroll the test student (testingstudent = 0000002775) in this class
$testStudentID = '0000002775';
$gibbonCourseClassPersonID = $I->haveInDatabase('gibbonCourseClassPerson', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonPersonID'      => $testStudentID,
    'role'                => 'Student',
    'reportable'          => 'Y',
]);

// Create a planner entry with homework submission enabled (file type, due in the future)
$gibbonPlannerEntryID = $I->haveInDatabase('gibbonPlannerEntry', [
    'gibbonCourseClassID'                      => $gibbonCourseClassID,
    'date'                                     => date('Y-m-d'),
    'timeStart'                                => '08:00:00',
    'timeEnd'                                  => '09:00:00',
    'name'                                     => 'Upload Test Lesson',
    'summary'                                  => 'Test lesson for homework upload',
    'description'                              => 'Test lesson description',
    'teachersNotes'                            => '',
    'homework'                                 => 'Y',
    'homeworkDueDateTime'                      => date('Y-m-d', strtotime('+7 days')).' 23:59:00',
    'homeworkDetails'                          => 'Submit your homework file',
    'homeworkSubmission'                       => 'Y',
    'homeworkSubmissionDateOpen'               => date('Y-m-d', strtotime('-1 day')),
    'homeworkSubmissionDrafts'                 => '0',
    'homeworkSubmissionType'                   => 'File',
    'homeworkSubmissionRequired'               => 'Required',
    'homeworkCrowdAssess'                      => 'N',
    'homeworkCrowdAssessOtherTeachersRead'     => 'N',
    'homeworkCrowdAssessOtherParentsRead'      => 'N',
    'homeworkCrowdAssessClassmatesParentsRead' => 'N',
    'homeworkCrowdAssessSubmitterParentsRead'  => 'N',
    'homeworkCrowdAssessOtherStudentsRead'     => 'N',
    'homeworkCrowdAssessClassmatesRead'        => 'N',
    'viewableStudents'                         => 'Y',
    'viewableParents'                          => 'N',
    'gibbonPersonIDCreator'                    => '0000000001',
    'gibbonPersonIDLastEdit'                   => '0000000001',
]);

// Basic admin check --------------------------------
$I->amOnModulePage('Planner', 'planner_view_full.php', [
    'viewBy'               => 'class',
    'gibbonCourseClassID'  => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
]);
$I->seeBreadcrumb('View Lesson Plan');

// Student Homework Submission --------------------------------
$I->amOnPage('/logout.php');
$I->loginAsStudent();

$I->amOnModulePage('Planner', 'planner_view_full.php', [
    'viewBy'               => 'class',
    'gibbonCourseClassID'  => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'date'                 => date('Y-m-d'),
]);
$I->seeBreadcrumb('View Lesson Plan');

// Submit homework file
$I->selectOption('version', 'Final');
$I->attachFile('file', 'attachment.txt');
$I->click('Submit');
$I->seeSuccessMessage();

// Verify submission was saved
$submissionFile = $I->grabFromDatabase('gibbonPlannerEntryHomework', 'location', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'gibbonPersonID'       => $testStudentID,
]);
$I->assertNotEmpty($submissionFile);

$I->seeInDatabase('gibbonPlannerEntryHomework', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'gibbonPersonID'       => $testStudentID,
    'type'                 => 'File',
    'version'              => 'Final',
]);

// Submission Edit — Teacher adds submission on behalf of another student --------------------------------
$I->amOnPage('/logout.php');
$I->loginAsAdmin();

$I->amOnModulePage('Planner', 'planner_view_full_submit_edit.php', [
    'viewBy'               => 'class',
    'gibbonCourseClassID'  => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'submission'           => 'false',
    'gibbonPersonID'       => $gibbonPersonIDStudent,
]);
$I->seeBreadcrumb('Add Submission');

$I->selectOption('type', 'File');
$I->selectOption('version', 'Final');
$I->attachFile('file', 'attachment.txt');
$I->selectOption('status', 'On Time');
$I->click('Submit');
$I->seeSuccessMessage();

// Verify teacher-added submission was saved
$editFile = $I->grabFromDatabase('gibbonPlannerEntryHomework', 'location', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'gibbonPersonID'       => $gibbonPersonIDStudent,
]);
$I->assertNotEmpty($editFile);

// Cleanup --------------------------------
$I->deleteFromDatabase('gibbonPlannerEntryHomework', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
]);
$I->deleteFromDatabase('gibbonPlannerEntry', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
]);
$I->deleteFromDatabase('gibbonCourseClassPerson', [
    'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID,
]);

if (!empty($submissionFile)) {
    $I->deleteFile('../'.$submissionFile);
}
if (!empty($editFile)) {
    $I->deleteFile('../'.$editFile);
}
