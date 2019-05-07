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
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\View\AbsenceView;
use Gibbon\Module\Staff\Tables\AbsenceDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_approval_action.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';
    $status = $_GET['status'] ?? '';

    $page->breadcrumbs
        ->add(__('Approve Staff Absences'), 'absences_approval.php')
        ->add(__('Approval'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $absence = $container->get(StaffAbsenceGateway::class)->getAbsenceDetailsByID($gibbonStaffAbsenceID);

    if (empty($absence)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($absence['gibbonPersonIDApproval'] != $_SESSION[$guid]['gibbonPersonID']) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    
    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($absence['gibbonPersonID'])->compose($page);

    // Absence Dates
    $table = $container->get(AbsenceDates::class)->create($gibbonStaffAbsenceID, true);
    $page->write($table->getOutput());

    // Absence View Composer
    $absenceView = $container->get(AbsenceView::class);
    $absenceView->setAbsence($gibbonStaffAbsenceID, $_SESSION[$guid]['gibbonPersonID'])->compose($page);
    
    // Approval Form
    $form = Form::create('staffAbsenceApproval', $_SESSION[$guid]['absoluteURL'].'/modules/Staff/absences_approval_actionProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $options = [
        'Approved' => __('Approved'),
        'Declined' => __('Declined'),
    ];
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($options)->selected($status)->required();

    $row = $form->addRow();
        $row->addLabel('notesApproval', __('Reply'));
        $row->addTextArea('notesApproval')->setRows(3);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
