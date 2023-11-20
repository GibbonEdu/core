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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/activeSessions.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Active Sessions'));

    $sessionGateway = $container->get(SessionGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $primaryRole = $container->get(RoleGateway::class)->selectBy(['gibbonRoleID' => $session->get('gibbonRoleIDPrimary')], ['name'])->fetch();
   
    // FORM - Administrator only (to prevent themselves from removing their own access)
    if (!empty($primaryRole['name']) && $primaryRole['name'] == 'Administrator') {
        $form = Form::create('sessionSettings', $session->get('absoluteURL').'/modules/System Admin/activeSessions_settingsProcess.php');

        $form->addRow()->addHeading('Settings', __('Settings'));

        $setting = $settingGateway->getSettingByScope('System Admin', 'maintenanceMode', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addYesNo($setting['name'])->required()->selected($setting['value']);

        $form->toggleVisibilityByClass('maintenance')->onSelect('maintenanceMode')->when('Y');

        $setting = $settingGateway->getSettingByScope('System Admin', 'maintenanceModeMessage', true);
        $row = $form->addRow()->addClass('maintenance');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextArea($setting['name'])->required()->setValue($setting['value']);

        $row = $form->addRow()->addSubmit();

        echo $form->getOutput();
    }


    // QUERY
    $criteria = $sessionGateway->newQueryCriteria()
        ->sortBy(['sessionStatus', 'timestampModified'], 'DESC')
        ->pageSize(100)
        ->fromPOST();

    $sessions = $sessionGateway->queryActiveSessions($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('sessions', $criteria);
    $table->setTitle(__('View'));

    $table->modifyRows(function ($values, $row) {
        if (empty($values['sessionStatus'])) $row->addClass('dull');
        return $row;
    });

    $table->addColumn('name', __('Name'))
        ->context('primary')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($values) {
            return !empty($values['gibbonPersonID'])
                ? Format::name($values['title'], $values['preferredName'], $values['surname'], 'Student', true, true)
                : __('Anonymous');
        });

    $table->addColumn('roleCategory', __('Role Category'))
        ->context('secondary')
        ->translatable();

    $table->addColumn('lastIPAddress', __('IP Address'))
        ->context('secondary');

    $table->addColumn('actionName', __('Page'));

    $table->addColumn('timestampCreated', __('Duration'))
        ->format(Format::using('relativeTime', ['timestampCreated', true, false]));

    $table->addColumn('timestampActive', __('Last Active'))
        ->sortable('timestampModified')
        ->format(Format::using('relativeTime', ['timestampModified', true, false]));

    $table->addColumn('timestampModified', __('Last Updated'))
        ->format(Format::using('dateTime', 'timestampModified'));
        
    echo $table->render($sessions);
}
