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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Module\Staff\Tables\AbsenceDates;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Staff Absences'), 'absences_manage.php')
        ->add(__('Edit Absence'));

    $page->return->addReturns(['error3' => __('School is closed on the specified day.')]);

    $gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';

    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    if (empty($gibbonStaffAbsenceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get absence types & format them for the chained select lists
    $type = $staffAbsenceTypeGateway->getByID($values['gibbonStaffAbsenceTypeID']);
    $types = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();

    $typesWithReasons = $reasonsOptions = $reasonsChained = [];

    $types = array_reduce($types, function ($group, $item) use (&$reasonsOptions, &$reasonsChained, &$typesWithReasons) {
        $id = $item['gibbonStaffAbsenceTypeID'];
        $group[$id] = $item['name'];
        $reasons = array_filter(array_map('trim', explode(',', $item['reasons'])));
        if (!empty($reasons)) {
            $typesWithReasons[] = $id;
            foreach ($reasons as $reason) {
                $reasonsOptions[$reason] = $reason;
                $reasonsChained[$reason] = $id;
            }
        }
        return $group;
    }, []);

    // FORM
    $form = Form::create('staffAbsenceEdit', $session->get('absoluteURL').'/modules/Staff/absences_manage_editProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'));
        $row->addSelectStaff('gibbonPersonID')->placeholder()->isRequired()->readonly();

    if ($type['requiresApproval'] == 'Y') {
        $approver = '';
        if (!empty($values['gibbonPersonIDApproval'])) {
            $approver = $container->get(UserGateway::class)->getByID($values['gibbonPersonIDApproval']);
            $approver = Format::small(__('By').' '.Format::nameList([$approver], 'Staff'));
        }

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addContent($values['status'].'<br/>'.$approver)->wrap('<div class="standardWidth floatRight">', '</div>');
    }

    $row = $form->addRow();
        $row->addLabel('gibbonStaffAbsenceTypeID', __('Type'));
        $row->addSelect('gibbonStaffAbsenceTypeID')
            ->fromArray($types)
            ->placeholder()
            ->isRequired();

    $form->toggleVisibilityByClass('reasonOptions')->onSelect('gibbonStaffAbsenceTypeID')->when($typesWithReasons);

    $row = $form->addRow()->addClass('reasonOptions');
        $row->addLabel('reason', __('Reason'));
        $row->addSelect('reason')
            ->fromArray($reasonsOptions)
            ->chainedTo('gibbonStaffAbsenceTypeID', $reasonsChained)
            ->placeholder()
            ->isRequired();

    $row = $form->addRow();
        $row->addLabel('coverageRequired', __('Cover Required'));
        $row->addYesNo('coverageRequired');

    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'));
        $row->addTextArea('comment')->setRows(2);

    $notificationList = !empty($values['notificationList'])? json_decode($values['notificationList']) : [];
    $notified = $container->get(UserGateway::class)->selectNotificationDetailsByPerson($notificationList)->fetchGroupedUnique();

    $row = $form->addRow();
        $row->addLabel('sentToLabel', __('Notified'));
        $row->addTextArea('sentTo')->setRows(3)->readonly()->setValue(Format::nameList($notified, 'Staff', false, true, ', '));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    // Absence Dates
    $table = $container->get(AbsenceDates::class)->create($gibbonStaffAbsenceID, true, false);
    $table->setTitle(__('Absence'));
    echo  $table->getOutput();

    // Coverage Dates
    if ($values['coverageRequired'] == 'Y') {
        $table = $container->get(CoverageDates::class)->createFromAbsence($gibbonStaffAbsenceID, $values['status']);
        $table->setTitle(__('Coverage'));
        echo  $table->getOutput();
    }


    $form = Form::create('staffAbsenceAdd', $session->get('absoluteURL').'/modules/Staff/absences_manage_edit_addProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $form->addRow()->addHeading('Add', __('Add'));

    $row = $form->addRow();
        $row->addLabel('allDay', __('All Day'));
        $row->addYesNoRadio('allDay')->checked('Y');

    $form->toggleVisibilityByClass('timeOptions')->onRadio('allDay')->when('N');

    $row = $form->addRow();
        $row->addLabel('date', __('Date'));
        $row->addDate('date')->isRequired();

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('time', __('Time'));
        $col = $row->addColumn('time');
        $col->addTime('timeStart')
            ->addClass('w-full mr-1')
            ->isRequired();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart', false)
            ->addClass('w-full')
            ->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
