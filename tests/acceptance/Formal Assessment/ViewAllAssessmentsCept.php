<?php
/**
 * @covers modules/Formal Assessment/externalAssessment.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View all assessments');
$I->loginAsAdmin();
$I->amOnModulePage('Formal Assessment', 'externalAssessment.php');
$I->seeBreadcrumb('View All Assessments');
