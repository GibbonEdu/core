<?php
/**
 * @covers modules/Students/report_student_personalDocumentSummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Personal Document Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_student_personalDocumentSummary.php');
$I->seeBreadcrumb('Personal Document Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
