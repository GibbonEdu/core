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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Attendance\AttendanceCodeGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Attendance Settings'));

    echo '<h3>';
    echo __('Attendance Codes');
    echo '</h3>';
    echo '<p>';
    echo __('These codes should not be changed during an active school year. Removing an attendace code after attendance has been recorded can result in lost information.');
    echo '</p>';

    $attendanceCodeGateway = $container->get(AttendanceCodeGateway::class);

    // QUERY
    $criteria = $attendanceCodeGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber'])
        ->fromArray($_POST);

    $attendanceCodes = $attendanceCodeGateway->queryAttendanceCodes($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('attendanceCodesManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/attendanceSettings_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addColumn('nameShort', __('Code'));
    $table->addColumn('name', __('Name'))->translatable();
    $table->addColumn('direction', __('Direction'))->translatable();
    $table->addColumn('scope', __('Scope'))->translatable();
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonAttendanceCodeID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/attendanceSettings_manage_edit.php');

            if ($values['type'] != 'Core') {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/School Admin/attendanceSettings_manage_delete.php');
            }
        });

    echo $table->render($attendanceCodes);

    echo '<h3>';
    echo __(__('Miscellaneous'));
    echo '</h3>';

    $form = Form::create('attendanceSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/attendanceSettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Reasons', __('Reasons'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('Attendance', 'attendanceReasons', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $row = $form->addRow()->addHeading('Context & Defaults', __('Context & Defaults'));

    $setting = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Attendance', 'recordFirstClassAsSchool', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Attendance', 'crossFillClasses', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $sql = "SELECT name AS value, name FROM gibbonAttendanceCode WHERE active='Y' ORDER BY sequenceNumber ASC, name";
    $setting = $settingGateway->getSettingByScope('Attendance', 'defaultFormGroupAttendanceType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromQuery($pdo, $sql)
            ->selected($setting['value'])
            ->required();

    $setting = $settingGateway->getSettingByScope('Attendance', 'defaultClassAttendanceType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromQuery($pdo, $sql)
            ->selected($setting['value'])
            ->required();


    $row = $form->addRow()->addHeading('Student Self Registration', __('Student Self Registration'));

    $setting = $settingGateway->getSettingByScope('Attendance', 'studentSelfRegistrationIPAddresses', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $realIP = getIPAddress();
    $inRange = false;
    if ($setting['value'] != '' && $setting['value'] != null) {
        foreach (explode(',', $setting['value']) as $ipAddress) {
            if (trim($ipAddress) == $realIP) {
                $inRange = true ;
            }
        }
    }
    if ($inRange) { //Current address is in range
        $form->addRow()->addAlert(sprintf(__('Your current IP address (%1$s) is included in the saved list.'), "<b>".$realIP."</b>"), 'success')->setClass('standardWidth');
    } else { //Current address is not in range
        $form->addRow()->addAlert(sprintf(__('Your current IP address (%1$s) is not included in the saved list.'), "<b>".$realIP."</b>"), 'warning')->setClass('standardWidth');
    }

    $setting = $settingGateway->getSettingByScope('Attendance', 'selfRegistrationRedirect', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();


    $row = $form->addRow()->addHeading('Attendance CLI', __('Attendance CLI'));

    $setting = $settingGateway->getSettingByScope('Attendance', 'attendanceCLINotifyByFormGroup', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Attendance', 'attendanceCLINotifyByClass', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();


    $setting = $settingGateway->getSettingByScope('Attendance', 'attendanceCLIAdditionalUsers', true);
    $inputs = array();
    
        $data=array( 'action1' => '%report_formGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonRole.name as roleName
                FROM gibbonPerson
                JOIN gibbonPermission ON (gibbonPerson.gibbonRoleIDPrimary=gibbonPermission.gibbonRoleID)
                JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                WHERE status='Full'
                AND (gibbonAction.URLList LIKE :action1 OR gibbonAction.URLList LIKE :action2)
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonRole.gibbonRoleID, surname, preferredName" ;
        $resultSelect=$connection2->prepare($sql);
        $resultSelect->execute($data);

    $users = explode(',', $setting['value']);
    $selected = array();
    while ($rowSelect=$resultSelect->fetch()) {
        if (in_array($rowSelect['gibbonPersonID'], $users) !== false) {
            array_push($selected, $rowSelect['gibbonPersonID']);
        }
        $inputs[__($rowSelect["roleName"])][$rowSelect['gibbonPersonID']] = Format::name("", $rowSelect["preferredName"], $rowSelect["surname"], "Staff", true, true);
    }

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->selectMultiple()
            ->fromArray($inputs)
            ->selected($selected);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
