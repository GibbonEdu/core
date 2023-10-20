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
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\View\CoverageView;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_accept.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('My Coverage'), 'coverage_my.php')
        ->add(__('Accept Coverage Request'));

    $page->return->addReturns([
            'warning3' => __('This coverage request has already been accepted.'),
        ]);

    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';

    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    if (empty($gibbonStaffCoverageID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageGateway->getCoverageDetailsByID($gibbonStaffCoverageID);
    if (empty($coverage)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($coverage['status'] != 'Requested') {
        $page->addWarning(__('This coverage request has already been accepted.'));
        return;
    }

    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);

    // Coverage View Composer
    $coverageView = $container->get(CoverageView::class);
    $coverageView->setCoverage($gibbonStaffCoverageID)->compose($page);

    // Coverage Dates
    $table = $container->get(CoverageDates::class)->create($gibbonStaffCoverageID);
    $table->getRenderer()->addData('class', 'bulkActionForm');

    // Checkbox options
    $gibbonPersonID = !empty($coverage['gibbonPersonIDCoverage']) ? $coverage['gibbonPersonIDCoverage'] : $session->get('gibbonPersonID');
    $unavailable = $container->get(SubstituteGateway::class)->selectUnavailableDatesBySub($gibbonPersonID, $coverage['dateStart'], $coverage['dateEnd'], $gibbonStaffCoverageID)->fetchGrouped();

    $datesAvailableToRequest = 0;
    $table->addCheckboxColumn('coverageDates', 'gibbonStaffCoverageDateID')
        ->width('15%')
        ->checked(true)
        ->format(function ($coverage) use (&$datesAvailableToRequest, &$unavailable) {
            // Has this date already been requested?
            if (empty($coverage['gibbonStaffCoverageID'])) return __('N/A');

            // Is this date unavailable: absent, already booked, or has an availability exception
            if (isset($unavailable[$coverage['date']])) {
                $times = $unavailable[$coverage['date']];

                foreach ($times as $time) {
                    // Handle full day and partial day unavailability
                    if ($time['allDay'] == 'Y' 
                    || ($time['allDay'] == 'N' && $coverage['allDay'] == 'Y')
                    || ($time['allDay'] == 'N' && $coverage['allDay'] == 'N'
                        && $time['timeStart'] < $coverage['timeEnd']
                        && $time['timeEnd'] > $coverage['timeStart'])) {
                        return Format::small(__($time['status'] ?? 'Not Available'));
                    }
                }
            }

            $datesAvailableToRequest++;
        })
        ->modifyCells(function ($coverage, $cell) {
            return $cell->addClass('h-10');
        });

    // FORM
    $form = Form::create('staffCoverage', $session->get('absoluteURL').'/modules/Staff/coverage_view_acceptProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $gibbonStaffCoverageID);

    $form->addRow()->addHeading('Accept Coverage Request', __('Accept Coverage Request'));

    $row = $form->addRow()->addContent($table->getOutput());

    if ($datesAvailableToRequest > 0) {
        $row = $form->addRow();
            $row->addLabel('notesCoverage', __('Reply'));
            $row->addTextArea('notesCoverage')->setRows(3)->setClass('w-full sm:max-w-xs');

        $row = $form->addRow();
            $row->addContent();
            $row->addSubmit();
    } else {
        $row = $form->addRow()->addAlert(__('Not Available'), 'warning');
    }

    echo $form->getOutput();
}
