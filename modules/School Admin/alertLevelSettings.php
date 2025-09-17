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
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;
use Gibbon\UI\Components\Alert;
use Gibbon\View\Component;

if (!isActionAccessible($guid, $connection2, '/modules/School Admin/alertLevelSettings.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Student Alert Settings'));

    // ALERT TYPES
    $alertTypeGateway = $container->get(AlertTypeGateway::class);
    
    // QUERY
    $criteria = $alertTypeGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'name'])
        ->fromArray($_POST);

    $alertTypes = $alertTypeGateway->queryAlertTypes($criteria);

    // DATA TABLE
    $table = DataTable::create('alertTypesManage');
    $table->setTitle(__('Alert Types'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/alertType_add.php')
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['active'] != 'Y') $row->addClass('error');
        return $row;
    });

    $table->addDraggableColumn('gibbonAlertTypeID', $session->get('absoluteURL').'/modules/School Admin/alertType_editOrderAjax.php');

    $table->addColumn('tag', __('Tag'))
        ->width('8%')
        ->format(function($values) {
            return Component::render(Alert::class,  [
                'title'   => $values['name'],
                'color'   => $values['color'] ?? '#939090',
                'colorBG' => $values['colorBG'] ?? '#dddddd',
                'large'   => true,
            ] + $values);
        });
    
    $table->addColumn('name', __('Name'))
        ->format(function($values) {
            return Format::bold(__($values['name']));
        });

    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('description', __('Description'));    

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonAlertTypeID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/School Admin/alertType_edit.php');

            if ($values['type'] != 'Core') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/alertType_delete.php');
            }
        });

    echo $table->render($alertTypes);

    // ALERT LEVELS
    $data = [];
    $sql = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
    $result = $connection2->prepare($sql);
    $result->execute($data);

    // Let's go!
    $form = Form::create('alertLevelSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/alertLevelSettingsProcess.php' );
    $form->addHiddenValue('address', $session->get('address'));
    
    $form->setTitle(__('Alert Levels'));

    $count = 0;
    while ($rowSQL = $result->fetch()) {
        $row = $form->addRow()->addHeading($rowSQL['name'], __($rowSQL['name']));

        $form->addHiddenValue('gibbonAlertLevelID'.$count, $rowSQL['gibbonAlertLevelID']);

        $row = $form->addRow();
        	$row->addLabel('name'.$count, __('Name'));
    		$row->addTextField('name'.$count)
            ->setValue($rowSQL['name'])
            ->maxLength(50)
            ->required();

        $row = $form->addRow();
        	$row->addLabel('nameShort'.$count, __('Short Name'));
    		$row->addTextField('nameShort'.$count)
            ->setValue($rowSQL['nameShort'])
            ->maxLength(4)
            ->required();

        $row = $form->addRow();
        	$row->addLabel('color'.$count, __('Font/Border Colour'))->description(__('Click to select a colour.'));
    		$row->addColor("color$count")
                ->setValue($rowSQL['color'])
                ->required();

        $row = $form->addRow();
        	$row->addLabel('colorBG'.$count, __('Background Colour'))->description(__('Click to select a colour.'));
    		$row->addColor("colorBG$count")
                ->setValue($rowSQL['colorBG'])
                ->required();

        $row = $form->addRow();
        	$row->addLabel('sequenceNumber'.$count, __('Sequence Number'));
    		$row->addTextField('sequenceNumber'.$count)
            ->setValue($rowSQL['sequenceNumber'])
            ->maxLength(4)
            ->readonly()
            ->required();

        $row = $form->addRow();
        	$row->addLabel('description'.$count, __('Description'));
            $row->addTextArea('description'.$count)->setValue($rowSQL['description']);

        $count++;
    }

    $form->addHiddenValue('count', $count);

    $row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();
}
