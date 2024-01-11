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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/personalDocumentSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Personal Document Settings'));

    $settingGateway = $container->get(SettingGateway::class);
    $personDocumentTypeGateway = $container->get(PersonalDocumentTypeGateway::class);
    $absoluteURL = $session->get('absoluteURL');

    // QUERY
    $criteria = $personDocumentTypeGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber'])
        ->fromArray($_POST);

    $absenceTypes = $personDocumentTypeGateway->queryDocumentTypes($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('personalDocumentTypes', $criteria);
    $table->setTitle(__('Personal Document Types'));

    $table->modifyRows(function ($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/personalDocumentSettings_manage_add.php')
        ->displayLabel();

    $table->addDraggableColumn('gibbonPersonalDocumentTypeID', $session->get('absoluteURL').'/modules/User Admin/personalDocumentSettings_manage_editOrderAjax.php');

    $table->addColumn('name', __('Name'));
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));
    $table->addColumn('required', __('Required'))->format(Format::using('yesNo', 'required'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonPersonalDocumentTypeID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/personalDocumentSettings_manage_edit.php');

            if ($values['type'] == 'Additional') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/User Admin/personalDocumentSettings_manage_delete.php');
            }
        });

    echo $table->render($absenceTypes);

    // FORM
    $form = Form::create('personalDocumentSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/personalDocumentSettingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $session->get('address'));

    $setting = $settingGateway->getSettingByScope('User Admin', 'residencyStatus', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
