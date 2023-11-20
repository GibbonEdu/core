<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/applicationFormSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Application Form Settings'));

    $form = Form::create('applicationFormSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationFormSettingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('General Options', __('General Options'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('Application Form', 'introduction', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(8);

    $setting = $settingGateway->getSettingByScope('Application Form', 'postscript', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(8);

    $setting = $settingGateway->getSettingByScope('Application Form', 'agreement', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'publicApplications', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Application Form', 'milestones', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'howDidYouHear', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'enableLimitedYearsOfEntry', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('yearsOfEntry')->onSelect('enableLimitedYearsOfEntry')->when('Y');

    $setting = $settingGateway->getSettingByScope('Application Form', 'availableYearsOfEntry', true);
    $row = $form->addRow()->addClass('yearsOfEntry');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $years = $row->addSelectSchoolYear($setting['name'], 'Active')
            ->setSize(3)
            ->selectMultiple()
            ->selected(explode(',', $setting['value']))
            ->required();

        if (empty($setting['value'])) {
            $years->selectAll();
        }

    $row = $form->addRow()->addHeading('Application Fee', __('Application Fee'));

    $setting = $settingGateway->getSettingByScope('Application Form', 'applicationFee', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))
            ->description(__($setting['description']));
        $row->addCurrency($setting['name'])
            ->setValue($setting['value'])
            ->required();

    $setting = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))
            ->description(__($setting['description']));
        $row->addCurrency($setting['name'])
            ->setValue($setting['value'])
            ->required();

    $setting = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFeeText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('References', __('References'));

    $setting = $settingGateway->getSettingByScope('Students', 'applicationFormRefereeRequired', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('referee')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Students', 'applicationFormRefereeLink', true);
    $row = $form->addRow()->addClass('referee');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Required Documents Options', __('Required Documents Options'));

    $setting = $settingGateway->getSettingByScope('Application Form', 'requiredDocuments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'internalDocuments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsCompulsory', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading('Language Learning Options', __('Language Learning Options'))->append(__('Set values for applicants to specify which language they wish to learn.'));

    $setting = $settingGateway->getSettingByScope('Application Form', 'languageOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('languageOptions')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Application Form', 'languageOptionsBlurb', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'languageOptionsLanguageList', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Sections', __('Sections'));

    $setting = $settingGateway->getSettingByScope('Application Form', 'senOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('senOptions')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Students', 'applicationFormSENText', true);
    $row = $form->addRow()->addClass('senOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'scholarshipOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('scholarshipOptions')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Application Form', 'scholarships', true);
    $row = $form->addRow()->addClass('scholarshipOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'paymentOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading('Acceptance Options', __('Acceptance Options'));

    $setting = $settingGateway->getSettingByScope('Application Form', 'usernameFormat', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'notificationStudentMessage', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'notificationStudentDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Application Form', 'notificationParentsMessage', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'notificationParentsDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Application Form', 'studentDefaultEmail', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'studentDefaultWebsite', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'autoHouseAssign', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
        $row->addSubmit();

    echo $form->getOutput();
}
