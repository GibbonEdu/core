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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Alerts\AlertGateway;
use Gibbon\Domain\Alerts\AlertTypeGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Alerts/studentAlerts_edit.php')) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $page->breadcrumbs
        ->add(__('Manage Alerts'), 'studentAlerts_manage.php')
        ->add(__('Edit'));

    $alertGateway = $container->get(AlertGateway::class);

    $gibbonAlertID = $_GET['gibbonAlertID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

    if (empty($gibbonAlertID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $alertGateway->getByID($gibbonAlertID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditAlert = $alertGateway->getAlertEditAccess($gibbonAlertID, $session->get('gibbonPersonID'));
    
    if ($highestAction != 'Manage Student Alerts_all' && !$canEditAlert) {
        $page->addError(__('You do not have edit access to this record.'));
        return;
    }

    $form = Form::create('editAlert', $session->get('absoluteURL').'/modules/Alerts/studentAlerts_editProcess.php?gibbonAlertID='.$gibbonAlertID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonAlertID', $gibbonAlertID);

    if (!empty($gibbonPersonID) or !empty($gibbonFormGroupID) or !empty($gibbonYearGroupID)) {
        $form->addHeaderAction('back', __('Back to Search Results'))
            ->setURL('/modules/Alerts/studentAlerts_manage.php')
            ->setIcon('search')
            ->displayLabel()
            ->addParam('gibbonPersonID', $_GET['gibbonPersonID'])
            ->addParam('gibbonFormGroupID', $_GET['gibbonFormGroupID'])
            ->addParam('gibbonYearGroupID', $_GET['gibbonYearGroupID']);
    }

    $form->addRow()->addHeading('Edit Alert', __('Edit Alert'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($values['gibbonPersonID'])->readonly();
        $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

    $alertTypes = $container->get(AlertTypeGateway::class)->selectAllAlertTypes()->fetchAll();
    $alertTypes = array_column($alertTypes, 'name');

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')
            ->fromArray($alertTypes)
            ->required()
            ->selected($values['type']);

    $row = $form->addRow();
        $row->addLabel('level', __('Level'));
        $row->addSelect('level')
            ->fromArray(['High' => __('High'), 'Medium' => __('Medium'), 'Low' => __('Low')])->required()
            ->selected($values['level']);
    
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description(__('If the alert is for a specified period'));
        $row->addDate('dateStart')->setValue(Format::date($values['dateStart']));

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description(__('If the alert is for a specified period')); 
        $row->addDate('dateEnd')->setValue(Format::date($values['dateEnd']));

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('comment', __('Comment'));
        $col->addEditor('comment', $guid)
            ->setValue($values['comment']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}