<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/applicationFormSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Application Form Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('applicationFormSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationFormSettingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('General Options'));

    $setting = getSettingByScope($connection2, 'Application Form', 'introduction', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(8);

    $setting = getSettingByScope($connection2, 'Application Form', 'postscript', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(8);

    $setting = getSettingByScope($connection2, 'Application Form', 'agreement', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'publicApplications', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Application Form', 'milestones', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'howDidYouHear', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'enableLimitedYearsOfEntry', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('yearsOfEntry')->onSelect('enableLimitedYearsOfEntry')->when('Y');

    $setting = getSettingByScope($connection2, 'Application Form', 'availableYearsOfEntry', true);
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

    $row = $form->addRow()->addHeading(__('Application Fee'));

    $setting = getSettingByScope($connection2, 'Application Form', 'applicationFee', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))
            ->description(__($setting['description']));
        $row->addCurrency($setting['name'])
            ->setValue($setting['value'])
            ->required();

    $setting = getSettingByScope($connection2, 'Application Form', 'applicationProcessFee', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))
            ->description(__($setting['description']));
        $row->addCurrency($setting['name'])
            ->setValue($setting['value'])
            ->required();

    $setting = getSettingByScope($connection2, 'Application Form', 'applicationProcessFeeText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('References'));

    $setting = getSettingByScope($connection2, 'Students', 'applicationFormRefereeRequired', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('referee')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink', true);
    $row = $form->addRow()->addClass('referee');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Required Documents Options'));

    $setting = getSettingByScope($connection2, 'Application Form', 'requiredDocuments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'internalDocuments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsCompulsory', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading(__('Language Learning Options'))->append(__('Set values for applicants to specify which language they wish to learn.'));

    $setting = getSettingByScope($connection2, 'Application Form', 'languageOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('languageOptions')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'languageOptionsLanguageList', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Sections'));

    $setting = getSettingByScope($connection2, 'Application Form', 'senOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('senOptions')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Students', 'applicationFormSENText', true);
    $row = $form->addRow()->addClass('senOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'scholarshipOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('scholarshipOptions')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Application Form', 'scholarships', true);
    $row = $form->addRow()->addClass('scholarshipOptions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'paymentOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading(__('Acceptance Options'));

    $setting = getSettingByScope($connection2, 'Application Form', 'usernameFormat', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'notificationStudentMessage', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'notificationStudentDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Application Form', 'notificationParentsMessage', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'notificationParentsDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Application Form', 'studentDefaultEmail', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'studentDefaultWebsite', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Application Form', 'autoHouseAssign', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
        $row->addSubmit();

    echo $form->getOutput();
}
