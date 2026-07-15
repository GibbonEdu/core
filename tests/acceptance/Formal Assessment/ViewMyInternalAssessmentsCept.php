<?php
/**
 * @covers modules/Formal Assessment/internalAssessment_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View my internal assessments');
$I->loginAsStudent();
$I->amOnModulePage('Formal Assessment', 'internalAssessment_view.php');
$I->seeBreadcrumb('View My Internal Assessments');
