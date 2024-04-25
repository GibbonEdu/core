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
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\Tables\CoverageDates;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\View\AbsenceView;
use Gibbon\Module\Staff\Tables\AbsenceDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('View Absences'), 'absences_view_byPerson.php')
        ->add(__('Cancel Absence'));

    $page->return->addReturns([
        'success1' => __('Your request was completed successfully.')
    ]);

    $highestAction = getHighestGroupedAction($guid, '/modules/Staff/absences_view_byPerson.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';
    $gibbonStaffAbsenceID = str_pad($gibbonStaffAbsenceID, 14, 0, STR_PAD_LEFT);

    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);

    if (empty($gibbonStaffAbsenceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $absence = $staffAbsenceGateway->getAbsenceDetailsByID($gibbonStaffAbsenceID);
    if (empty($absence)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($absence['dateEnd'] < date('Y-m-d')) {
        $page->addError(__('Your request failed because the selected date is not in the future.'));
        return;
    }

    if ($highestAction == 'View Absences_mine' && $absence['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($absence['gibbonPersonID'])->compose($page);

    // Absence Dates
    $table = $container->get(AbsenceDates::class)->create($gibbonStaffAbsenceID, true, false);
    $page->write($table->getOutput());

    // Coverage Dates
    if ($absence['coverageRequired'] == 'Y') {
        $table = $container->get(CoverageDates::class)->createFromAbsence($gibbonStaffAbsenceID, $absence['status']);
        $page->write($table->getOutput());
    }

    // Absence View Composer
    $absenceView = $container->get(AbsenceView::class);
    $absenceView->setAbsence($gibbonStaffAbsenceID, $session->get('gibbonPersonID'))->compose($page);

    // Form
    $form = Form::create('staffAbsence', $session->get('absoluteURL').'/modules/Staff/absences_view_cancelProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $form->addRow()->addHeading('Cancel Absence', __('Cancel Absence'));

    $row = $form->addRow();
        $row->addLabel('comment', __('Reply'));
        $row->addTextArea('comment')->setRows(3);

    $row = $form->addRow();
        $row->addSubmit();
    
    echo $form->getOutput();
}
