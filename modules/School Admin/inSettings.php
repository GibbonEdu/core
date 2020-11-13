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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/inSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Individual Needs Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __('Individual Needs Descriptors');
    echo '</h3>';


    $INGateway = $container->get(INGateway::class);

    // QUERY
    $criteria = $INGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber'])
        ->fromArray($_POST);

    $individualNeedsDescriptors = $INGateway->queryIndividualNeedsDescriptors($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('individualNeedsDescriptorsManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/inSettings_add.php')
        ->displayLabel();


    $table->addColumn('sequenceNumber', __('Sequence'));
    $table->addColumn('name', __('Name').'<br/>'.Format::small(__('Short Name')))
        ->width('15%')
        ->format(function($values) {
            return '<strong>'.$values['name'].'</strong><br/>'.Format::small($values['nameShort']);
        });
    $table->addColumn('description', __('Description'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonINDescriptorID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/School Admin/inSettings_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/School Admin/inSettings_delete.php');
        });

    echo $table->render($individualNeedsDescriptors);

    echo '<h3>';
    echo __('Settings');
    echo '</h3>';

    $form = Form::create('inSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/inSettingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'Individual Needs', 'targetsTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Individual Needs', 'teachingStrategiesTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Individual Needs', 'notesReviewTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Individual Needs', 'investigationNotificationRole', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectRole($setting['name'])->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
