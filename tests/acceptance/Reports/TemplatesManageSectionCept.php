<?php
/**
 * @covers modules/Reports/templates_manage_section_delete.php
 * @covers modules/Reports/templates_manage_section_edit.php
 * @covers modules/Reports/templates_manage_section_editProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('edit a report template section with file upload');
$I->loginAsAdmin();

// Create test data: prototype section with image config (matching reports/misc/image.twig.html)
$gibbonReportPrototypeSectionID = $I->haveInDatabase('gibbonReportPrototypeSection', [
    'name'           => 'Test Image Section',
    'type'           => 'Additional',
    'category'       => 'Test',
    'active'         => 'Y',
    'types'          => 'Body',
    'config'         => json_encode([
        'image' => [
            'label' => 'Image',
            'type'  => 'image',
        ],
        'height' => [
            'label'   => 'Height',
            'type'    => 'words',
            'default' => '30',
        ],
        'align' => [
            'label'   => 'Alignment',
            'type'    => 'select',
            'options' => 'Left, Center, Right',
            'default' => 'center',
        ],
    ]),
    'templateParams' => json_encode([]),
    'templateFile'   => 'test/testImageSection.twig.html',
]);

$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name'    => 'Test Template',
    'context' => 'Student Enrolment',
]);

$gibbonReportTemplateSectionID = $I->haveInDatabase('gibbonReportTemplateSection', [
    'gibbonReportTemplateID'        => $gibbonReportTemplateID,
    'gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID,
    'name'   => 'Test Section',
    'type'   => 'Body',
    'page'   => 1,
    'config' => json_encode([]),
]);

// Templates Manage Section Edit - File Upload ---------------

$I->amOnModulePage('Reports', 'templates_manage_section_edit.php', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    'gibbonReportTemplateID'        => $gibbonReportTemplateID,
]);
$I->seeBreadcrumb('Edit Section');
$I->dontSeeErrors();

$I->attachFile('input[type="file"][name="config[image]"]', 'attachment.jpg');
$I->submitForm('#content form', ['config' => ['image' => '']]);

$I->seeSuccessMessage();

$configJSON = $I->grabFromDatabase('gibbonReportTemplateSection', 'config', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
]);
$config = json_decode($configJSON, true);
$I->assertNotEmpty($config['image'] ?? '', 'Config image path should not be empty after upload');
$file = $config['image'];

// Templates Manage Section Delete ---------------------------

$I->amOnModulePage('Reports', 'templates_manage_section_delete.php', [
    'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    'gibbonReportTemplateID'        => $gibbonReportTemplateID,
]);
$I->click('Delete');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportPrototypeSection', ['gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
$I->deleteFile('../'.$file);
