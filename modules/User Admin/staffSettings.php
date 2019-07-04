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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Staff Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $settingGateway = $container->get(SettingGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);
    $absoluteURL = $gibbon->session->get('absoluteURL');

    // QUERY
    $criteria = $staffAbsenceTypeGateway->newQueryCriteria()
        ->sortBy(['sequenceNumber'])
        ->fromArray($_POST);

    $absenceTypes = $staffAbsenceTypeGateway->queryAbsenceTypes($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('staffAbsenceTypes', $criteria);
    $table->setTitle(__('Staff Absence Types'));

    $table->modifyRows(function ($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/staffSettings_manage_add.php')
        ->displayLabel();
    
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('name', __('Name'));
    $table->addColumn('reasons', __('Reasons'));
    $table->addColumn('requiresApproval', __('Requires Approval'))->format(Format::using('yesNo', 'requiresApproval'));
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonStaffAbsenceTypeID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/staffSettings_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/User Admin/staffSettings_manage_delete.php');
        });

    echo $table->render($absenceTypes);

    // FORM
    $form = Form::create('staffSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staffSettingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Staff Absence'));

    $setting = $settingGateway->getSettingByScope('Staff', 'absenceApprovers', true);

    $approverList = !empty($setting['value'])? explode(',', $setting['value']) : [];
    $approvers = $container->get(UserGateway::class)->selectNotificationDetailsByPerson($approverList)->fetchGroupedUnique();
    $approvers = array_map(function ($token) use ($absoluteURL) {
        return [
            'id'       => $token['gibbonPersonID'],
            'name'     => Format::name('', $token['preferredName'], $token['surname'], 'Staff', false, true),
            'jobTitle' => !empty($token['jobTitle']) ? $token['jobTitle'] : $token['type'],
            'image'    => $absoluteURL.'/'.$token['image_240'],
        ];
    }, $approvers);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addFinder($setting['name'])
            ->fromAjax($absoluteURL.'/modules/Staff/staff_searchAjax.php')
            ->selected($approvers)
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 rounded-full bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.image + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.jobTitle + "</span></div></li>"; }');

    $setting = $settingGateway->getSettingByScope('Staff', 'absenceFullDayThreshold', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->required()->onlyInteger(false)->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Staff', 'absenceHalfDayThreshold', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->required()->onlyInteger(false)->setValue($setting['value']);
                
    $form->addRow()->addHeading(__('Staff Coverage'));

    $setting = $settingGateway->getSettingByScope('Staff', 'substituteTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(3)->required()->setValue($setting['value']);

    $form->addRow()->addHeading(__('Notifications'));

    $setting = $settingGateway->getSettingByScope('Staff', 'absenceNotificationGroups', true);
    $notificationList = $container->get(GroupGateway::class)->selectGroupsByIDList($setting['value'])->fetchKeyPair();

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addFinder($setting['name'])
            ->fromAjax($absoluteURL.'/modules/User Admin/staffSettings_groupsAjax.php')
            ->selected($notificationList)
            ->setParameter('resultsLimit', 10);

    $smsGatewaySetting = $settingGateway->getSettingByScope('Messenger', 'smsGateway');
    if ($smsGatewaySetting) {
        $setting = $settingGateway->getSettingByScope('Staff', 'urgentNotifications', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addYesNo($setting['name'])->required()->selected($setting['value']);
            
        $form->toggleVisibilityByClass('urgency')->onSelect('urgentNotifications')->when('Y');

        $thresholds = array_map(function ($count) {
            return __n('{count} Day', '{count} Days', $count);
        }, array_combine(range(1, 14), range(1, 14)));

        $setting = $settingGateway->getSettingByScope('Staff', 'urgencyThreshold', true);
        $row = $form->addRow()->addClass('urgency');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray($thresholds)->required()->selected($setting['value']);
    }

    $row = $form->addRow()->addHeading(__('Field Values'));

    $setting = getSettingByScope($connection2, 'Staff', 'salaryScalePositions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Staff', 'responsibilityPosts', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Staff', 'jobOpeningDescriptionTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Name Formats'))->append(__('How should staff names be formatted?').' '.__('Choose from [title], [preferredName], [surname].').' '.__('Use a colon to limit the number of letters, for example [preferredName:1] will use the first initial.'));

    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
    $sql = "SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
    $result = $pdo->executeQuery($data, $sql);
    if ($result->rowCount() > 0) {
        list($title, $preferredName, $surname) = array_values($result->fetch());
    }

    $setting = getSettingByScope($connection2, 'System', 'nameFormatStaffFormal', true);
    $settingRev = getSettingByScope($connection2, 'System', 'nameFormatStaffFormalReversed', true);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']))->description(
            __('Example').': '.formatName($title, $preferredName, $surname, 'Staff', false, false).'<br/>'.
            __('Reversed').': '.formatName($title, $preferredName, $surname, 'Staff', true, false));
        $col = $row->addColumn($setting['name'])->addClass('stacked');
        $col->addTextField($setting['name'])->required()->maxLength(60)->setValue($setting['value']);
        $col->addTextField($settingRev['name'])->required()->maxLength(60)->setTitle(__('Reversed'))->setValue($settingRev['value']);

    $setting = getSettingByScope($connection2, 'System', 'nameFormatStaffInformal', true);
    $settingRev = getSettingByScope($connection2, 'System', 'nameFormatStaffInformalReversed', true);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']))->description(
            __('Example').': '.formatName($title, $preferredName, $surname, 'Staff', false, true).'<br/>'.
            __('Reversed').': '.formatName($title, $preferredName, $surname, 'Staff', true, true));
        $col = $row->addColumn($setting['name'])->addClass('stacked right');
        $col->addTextField($setting['name'])->required()->maxLength(60)->setValue($setting['value']);
        $col->addTextField($settingRev['name'])->required()->maxLength(60)->setTitle(__('Reversed'))->setValue($settingRev['value']);

    $form->loadAllValuesFrom($formats);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
