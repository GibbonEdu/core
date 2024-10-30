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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/dashboardSettings.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Dashboard Settings'));

    $form = Form::create('dashboardSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/dashboardSettingsProcess.php' );

    $form->addHiddenValue('address', $session->get('address'));

    // Staff dashboard
    $form->addRow()->addHeading('Staff Dashboard', __('Staff Dashboard'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('School Admin', 'staffDashboardEnable', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('staffDashboardEnable')->onSelect('staffDashboardEnable')->when('Y');

    $staffDashboardDefaultTabTypes = array(
        '' => '',
        'Planner' => __('Planner')
    );
    $setting = $settingGateway->getSettingByScope('School Admin', 'staffDashboardDefaultTab', true);
    $row = $form->addRow()->addClass('staffDashboardEnable');
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($staffDashboardDefaultTabTypes)
            ->fromQuery($pdo, "SELECT name, name AS value FROM gibbonHook WHERE type='Staff Dashboard'")
            ->selected($setting['value']);


    // Student dashboard
    $form->addRow()->addHeading('Student Dashboard', __('Student Dashboard'));

    $setting = $settingGateway->getSettingByScope('School Admin', 'studentDashboardEnable', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('studentDashboardEnable')->onSelect('studentDashboardEnable')->when('Y');

    $studentDashboardDefaultTabTypes = array(
        '' => '',
        'Planner' => __('Planner')
    );
    $setting = $settingGateway->getSettingByScope('School Admin', 'studentDashboardDefaultTab', true);
    $row = $form->addRow()->addClass('studentDashboardEnable');;
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($studentDashboardDefaultTabTypes)
            ->fromQuery($pdo, "SELECT name, name AS value FROM gibbonHook WHERE type='Student Dashboard'")
            ->selected($setting['value']);

    // Parent dashboard
    $form->addRow()->addHeading('Parent Dashboard', __('Parent Dashboard'));
    $setting = $settingGateway->getSettingByScope('School Admin', 'parentDashboardEnable', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('parentDashboardEnable')->onSelect('parentDashboardEnable')->when('Y');

    $parentDashboardDefaultTabTypes = array(
        '' => '',
        'Learning Overview' => __('Learning Overview'),
        'Timetable' => __('Timetable'),
        'Activities' => __('Activities')
    );
    $setting = $settingGateway->getSettingByScope('School Admin', 'parentDashboardDefaultTab', true);
    $row = $form->addRow()->addClass('parentDashboardEnable');;
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($parentDashboardDefaultTabTypes)
            ->fromQuery($pdo, "SELECT name, name AS value FROM gibbonHook WHERE type='Parental Dashboard'")
            ->selected($setting['value']);

    $row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();
}
