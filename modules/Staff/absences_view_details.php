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

use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\View\AbsenceView;
use Gibbon\Module\Staff\Tables\AbsenceDates;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('View Absences'), 'absences_view_byPerson.php')
        ->add(__('View Details'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
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

    $absence = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);

    if (empty($absence)) {
        $page->addError(__('The specified record cannot be found.'));
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
    $table->setTitle($absence['coverageRequired'] == 'Y' ? __('Absence') : '');
    $page->write($table->getOutput());

    // Coverage Dates
    if ($absence['coverageRequired'] == 'Y') {
        $table = $container->get(CoverageDates::class)->createFromAbsence($gibbonStaffAbsenceID, $absence['status']);
        $table->setTitle(__('Coverage'));
        $page->write($table->getOutput());
    }

    // Absence View Composer
    $absenceView = $container->get(AbsenceView::class);
    $absenceView->setAbsence($gibbonStaffAbsenceID, $session->get('gibbonPersonID'))->compose($page);
}
