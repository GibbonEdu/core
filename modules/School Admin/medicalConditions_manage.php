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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\MedicalConditionGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/medicalConditions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Medical Conditions'));

    $form = Form::create('medicalSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/medicalConditions_manageProcess.php' );

    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $session->get('address'));

    $setting = $container->get(SettingGateway::class)->getSettingByScope('Students', 'medicalConditionIntro', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setRows(6)->setValue($setting['value']);

    $row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();

    $medicalConditionGateway = $container->get(MedicalConditionGateway::class);

    // QUERY
    $criteria = $medicalConditionGateway->newQueryCriteria(true)
        ->sortBy(['name'])
        ->fromPOST();

    $medicalConditions = $medicalConditionGateway->queryMedicalConditions($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('medicalConditionsManage', $criteria);

    $table->setTitle(__('Conditions'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/medicalConditions_manage_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('description', __('Description'))->format(Format::using('truncate', 'description'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonMedicalConditionID')
        ->format(function ($facilities, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/medicalConditions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/medicalConditions_manage_delete.php');
        });

    echo $table->render($medicalConditions);
}
