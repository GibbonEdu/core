<?php
/**
 * @covers modules/Formal Assessment/internalAssessment_write.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Write internal assessments');
$I->loginAsAdmin();
$I->amOnModulePage('Formal Assessment', 'internalAssessment_write.php');
$I->seeBreadcrumb('Write Internal Assessments');
