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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/dashboardSettings.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Dashboard Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('dashboardSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dashboardSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $staffDashboardDefaultTabTypes = array(
        '' => '',
        'Planner' => __('Planner')
    );
    $setting = getSettingByScope($connection2, 'School Admin', 'staffDashboardDefaultTab', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($staffDashboardDefaultTabTypes)
            ->fromQuery($pdo, "SELECT name, name AS value FROM gibbonHook WHERE type='Staff Dashboard'")
            ->selected($setting['value']);

    $studentDashboardDefaultTabTypes = array(
        '' => '',
        'Planner' => __('Planner')
    );
    $setting = getSettingByScope($connection2, 'School Admin', 'studentDashboardDefaultTab', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($studentDashboardDefaultTabTypes)
            ->fromQuery($pdo, "SELECT name, name AS value FROM gibbonHook WHERE type='Student Dashboard'")
            ->selected($setting['value']);

    $parentDashboardDefaultTabTypes = array(
        '' => '',
        'Learning Overview' => __('Learning Overview'),
        'Timetable' => __('Timetable'),
        'Activities' => __('Activities')
    );
    $setting = getSettingByScope($connection2, 'School Admin', 'parentDashboardDefaultTab', true);
    $row = $form->addRow();
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
