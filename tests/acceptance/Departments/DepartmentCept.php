<?php
/**
 * @covers modules/Departments/department.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view a specific department');
$I->loginAsAdmin();

// Get a department ID from the database
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', []);

$I->amOnModulePage('Departments', 'department.php', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$I->seeBreadcrumb('Departments');
$I->see('Staff', 'h2');
