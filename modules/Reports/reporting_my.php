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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportingProofGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_my.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('My Reporting'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    // Select Person, if able to
    if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write.php', 'Write Reports_editAll')) {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID');

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('View As'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('q', '/modules/Reports/reporting_my.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    } else {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    if (empty($gibbonPersonID)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);
    $reportingProofGateway = $container->get(ReportingProofGateway::class);

    // QUERY
    $cycleCount = 0;
    $criteria = $reportingAccessGateway->newQueryCriteria()
        ->sortBy('gibbonReportingCycle.sequenceNumber');

    // Get cycles and scopes for each cycle
    $reportingCycles = $reportingAccessGateway->queryActiveReportingCyclesByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID);
    $reportingCycles->transform(function (&$cycle) use (&$reportingAccessGateway, &$gibbonPersonID, &$cycleCount) {
        $criteriaCount = 0;
        $criteria = $reportingAccessGateway->newQueryCriteria()
            ->sortBy('gibbonReportingScope.sequenceNumber');

        // Get scopes and criteria groups for each scope
        $cycle['scopes'] = $reportingAccessGateway->queryActiveReportingScopesByPerson($criteria, $cycle['gibbonReportingCycleID'], $gibbonPersonID);
        $cycle['scopes']->transform(function (&$scope) use (&$reportingAccessGateway, &$gibbonPersonID, &$criteriaCount) {
            $criteria = $reportingAccessGateway->newQueryCriteria()
                ->sortBy('criteriaNameShort')
                ->fromPOST('reportsMy');
            $scope['criteriaGroups'] = $reportingAccessGateway->queryActiveCriteriaGroupsByPerson($criteria, $scope['gibbonReportingScopeID'], $gibbonPersonID);
            $criteriaCount += count($scope['criteriaGroups']);
        });
        $cycle['criteriaCount'] = $criteriaCount;
        $cycleCount += $criteriaCount > 0;
    });

    if ($cycleCount == 0) {
        echo Format::alert(__('There are no active reporting cycles.'), 'message');
    } else {
        echo Format::alert(__n('There is {count} reporting cycle active right now.', 'There are {count} reporting cycles active right now.', $cycleCount), 'success');
    }

    // Proof reading
    $canProofRead = !empty(array_filter($reportingCycles->getColumn('canProofRead'), function ($canProofRead) {
        return $canProofRead == 'Y';
    }));
    $proofsTotal = $proofsDone = 0;
    $proofCriteria = $reportingProofGateway->newQueryCriteria()->pageSize(0);
    $proofReading = $reportingProofGateway->queryProofReadingByPerson($proofCriteria, $gibbonSchoolYearID, $gibbonPersonID)->toArray();
    if ($canProofRead && !empty($proofReading)) {
        $ids = array_column($proofReading, 'gibbonReportingValueID');
        $proofs = $reportingProofGateway->selectProofsByValueID($ids)->fetchGroupedUnique();
        $proofsTotal = count($proofs);
        $proofsDone = array_reduce($proofs, function ($total, $item) {
            return $item['status'] == 'Done' || $item['status'] == 'Accepted' ? $total+1 : $total;
        }, 0);
    }

    foreach ($reportingCycles as $reportingCycle) {
        if (count($reportingCycle['scopes']) == 0 || $reportingCycle['criteriaCount'] == 0) {
            continue;
        }

        echo $page->fetchFromTemplate('ui/reportingCycleHeader.twig.html', [
            'gibbonPersonID' => $gibbonPersonID,
            'reportingCycle' => $reportingCycle,
            'milestones' => json_decode($reportingCycle['milestones'], true),
            'proofsTotal' => $proofsTotal,
            'progressColour' => 'green',
            'partialColour' => 'blue',
            'totalCount' => count($proofReading),
            'progressCount' => $proofsDone,
            'partialCount' => max(0, $proofsTotal - $proofsDone)
        ]);

        foreach ($reportingCycle['scopes'] as $scope) {
            if (count($scope['criteriaGroups']) == 0) {
                continue;
            }

            // GRID TABLE
            $table = DataTable::create('reportsMy');
            $table->setTitle($scope['name']);
            $table->setDescription(__('You have access from {dateStart} to {dateEnd}.', [
                'dateStart' => Format::dateReadable($scope['dateStart'], Format::MEDIUM_NO_YEAR),
                'dateEnd' => Format::dateReadable($scope['dateEnd'], Format::MEDIUM_NO_YEAR),
            ]));

            $table->addColumn('criteriaName', __('Name'))->width('35%');
            $table->addColumn('criteriaNameShort', __('Short Name'))->width('15%');
            $table->addColumn('progress', __('Progress'))
                ->width('40%')
                ->format(function ($reporting) use (&$page) {
                    if ($reporting['totalCount'] == 0) return '';

                    return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                        'progressCount' => $reporting['progressCount'],
                        'totalCount'    => $reporting['totalCount'],
                        'leftCount'    => $reporting['leftCount'],
                        'width'         => 'w-64',
                    ]);
                });

            $table->addActionColumn()
                ->addParam('gibbonPersonID', $gibbonPersonID)
                ->addParam('gibbonReportingCycleID', $reportingCycle['gibbonReportingCycleID'])
                ->addParam('gibbonReportingScopeID', $scope['gibbonReportingScopeID'])
                ->addParam('scopeTypeID')
                ->format(function ($reportingCycle, $actions) use ($scope) {
                    if ($scope['canWrite'] == 'Y') {
                        $actions->addAction('edit', __('Write'))
                            ->setURL('/modules/Reports/reporting_write.php');
                    } else {
                        $actions->addAction('view', __('View'))
                            ->setURL('/modules/Reports/reporting_write.php');
                    }
                });

            echo $table->render($scope['criteriaGroups']);
        }
    }
}
