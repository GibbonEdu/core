<?php
/**
 * @covers modules/System Admin/emailTemplates_manage.php
 * @covers modules/System Admin/emailTemplates_manage_edit.php
 * @covers modules/System Admin/emailTemplates_manage_delete.php
 * @covers modules/System Admin/emailTemplates_manage_duplicate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage email templates');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'emailTemplates_manage.php');
$I->seeBreadcrumb('Email Templates');

// Get an existing email template
$gibbonEmailTemplateID = $I->haveInDatabase('gibbonEmailTemplate', [
    'type' => 'Core',
    'templateType' => 'Testing Template',
    'moduleName' => 'System',
    'templateName' => 'Testing Template',
    'templateSubject' => 'Test Subject',
    'templateBody' => 'Test',
    'variables' => '{}',
]);

// Edit ------------------------------------------------
$I->amOnModulePage('System Admin', 'emailTemplates_manage_edit.php', [
    'gibbonEmailTemplateID' => $gibbonEmailTemplateID
]);
$I->seeBreadcrumb('Edit Email Template');

$I->seeInField('templateName', 'Testing Template');

$formValues = [
    'templateName' => 'Updated Template Name',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Duplicate --------------------------------------------
$I->amOnModulePage('System Admin', 'emailTemplates_manage_duplicate.php', [
    'gibbonEmailTemplateID' => $gibbonEmailTemplateID
]);
$I->seeBreadcrumb('Duplicate Email Template');

$formValues = [
    'templateName' => 'Duplicated Template',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$duplicatedID = $I->grabEditIDFromURL();

// Delete Duplicated Template --------------------------
$I->amOnModulePage('System Admin', 'emailTemplates_manage_delete.php', [
    'gibbonEmailTemplateID' => $duplicatedID
]);

$I->click('Delete');
$I->seeSuccessMessage();
