<?php
/**
 * @covers modules/Formal Assessment/externalAssessment_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View External Assessments');
$I->loginAsAdmin();

$I->amOnModulePage('Formal Assessment', 'externalAssessment_view.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
