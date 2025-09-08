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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Alerts\AlertGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Alerts/studentAlerts_manage_view.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    
    $page->breadcrumbs
        ->add(__('Manage Alerts'), 'studentAlerts_manage.php')
        ->add(__('View Alert'));

    $gibbonAlertID = $_GET['gibbonAlertID'] ?? '';
    $alertGateway = $container->get(AlertGateway::class);

    if (empty($gibbonAlertID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $alert = $alertGateway->getByID($gibbonAlertID);
    if (empty($alert)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('viewAlert', '');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addRow()->addHeading('Alert', __('Alert'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($alert['gibbonPersonID'])->readonly();

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addTextField('type')
            ->setValue($alert['type'])
            ->readonly();
    
    $row = $form->addRow();
        $row->addLabel('level', __('Level'));
        $row->addTextField('level')
            ->setValue($alert['level'])
            ->readonly();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addTextField('status')
            ->setValue($alert['status'])
            ->readonly();
    
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description(__('If the alert is for a specified period'));
        $row->addTextField('dateStart')
            ->setValue(!empty($alert['dateStart']) ? Format::date($alert['dateStart']) : __('N/A'))
            ->readonly();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description(__('If the alert is for a specified period')); 
        $row->addTextField('dateEnd')
            ->setValue(!empty($alert['dateEnd']) ? Format::date($alert['dateEnd']) : __('N/A'))
            ->readonly();

    $row = $form->addRow();
            $col = $row->addColumn();
            $col->addLabel('comment', __('Comment'));
            $col->addTextArea('comment')
                ->setValue($alert['comment'])
                ->readonly()
                ->setRows(3);
                
    echo $form->getOutput();
}

