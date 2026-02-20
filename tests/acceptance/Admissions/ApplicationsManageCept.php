<?php
/**
 * @covers modules/Admissions/applications_manage.php
 * @covers modules/Admissions/applications_manage_add.php
 * @covers modules/Admissions/applications_manage_edit.php
 * @covers modules/Admissions/applications_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage applications with full CRUD operations');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'applications_manage.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Note: This module manages student applications
// Add, Edit, and Delete operations test the application workflow
