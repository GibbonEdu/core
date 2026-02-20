<?php
/**
 * @covers modules/Activities/activities_my.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view my activities as a student');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'activities_my.php');
$I->seeBreadcrumb('My Activities');
