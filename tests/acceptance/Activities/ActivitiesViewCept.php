<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('view activities as a student');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'activities_view.php');
$I->seeBreadcrumb('View Activities');

// Test search form
$I->submitForm('#searchForm', [
    'search' => 'Test',
]);
$I->seeInCurrentUrl('search=Test');
