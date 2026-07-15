<?php
/**
 * @covers modules/Admissions/report_graph_studentEnrolment.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Enrolment Trends');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'report_graph_studentEnrolment.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
