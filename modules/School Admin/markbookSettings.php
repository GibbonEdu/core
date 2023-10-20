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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/markbookSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Markbook Settings'));

    $form = Form::create('markbookSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/markbookSettingsProcess.php' );

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Features', __('Features'));

    $settingGateway = $container->get(SettingGateway::class);
    $setting = $settingGateway->getSettingByScope('Markbook', 'enableEffort', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'enableRubrics', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'enableColumnWeighting', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('columnWeighting')->onSelect('enableColumnWeighting')->when('Y');

    $defaultAssessmentScale = $settingGateway->getSettingByScope('System', 'defaultAssessmentScale');
    if (intval($defaultAssessmentScale) != 4) {
        $row = $form->addRow()->addClass('columnWeighting');
            $row->addAlert(__('Calculation of cumulative marks and weightings is currently only available when using Percentage as the Default Assessment Scale. This value can be changed in System Settings.'));
    }
    
    $setting = $settingGateway->getSettingByScope('Markbook', 'enableDisplayCumulativeMarks', true);
    $row = $form->addRow()->addClass('columnWeighting');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Markbook', 'enableRawAttainment', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();
    
    $setting = $settingGateway->getSettingByScope('Markbook', 'enableModifiedAssessment', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading('Interface', __('Interface'));

    $setting = $settingGateway->getSettingByScope('Markbook', 'markbookType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'enableGroupByTerm', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Warnings', __('Warnings'));

    $setting = $settingGateway->getSettingByScope('Markbook', 'showStudentAttainmentWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'showStudentEffortWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Markbook', 'personalisedWarnings', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();
}
