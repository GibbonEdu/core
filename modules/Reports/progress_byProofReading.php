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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Module\Reports\Domain\ReportingProofGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/progress_byProofReading.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Proof Reading Progress'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingProgressGateway = $container->get(ReportingProgressGateway::class);
    $reportingProofGateway = $container->get(ReportingProofGateway::class);
    
    $reportingCycles = $container->get(ReportingCycleGateway::class)->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Reports/progress_byProofReading.php');

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($gibbonReportingCycleID)
            ->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();


    $reportingScopes = $reportingProofGateway->selectProofReadingScopes($gibbonSchoolYearID)->fetchAll();
    if (empty($reportingScopes)) {
        $reportingScopes = [0 => ['gibbonReportingScopeID' => null, 'cycleName' => __('View')]];
    }

    // QUERY
    foreach ($reportingScopes as $scope) {
        $criteria = $reportingProgressGateway->newQueryCriteria()
            ->sortBy('name')
            ->filterBy('reportingCycle', $gibbonReportingCycleID);
        $progress = $reportingProgressGateway->queryProofReadingProgressByScope($criteria, $scope['gibbonReportingScopeID'], $scope['scopeType'] ?? 'Year Group');

        // DATA TABLE
        $table = DataTable::create('progress');
        $table->setTitle(($scope['cycleNameShort'] ?? '').(!empty($scope['scopeName'])? ': '.$scope['scopeName'] : ''));

        $table->addColumn('name', __('Name'))->width('30%');
        $table->addColumn('progress', __('Progress'))
            ->width('40%')
            ->format(function ($reporting) use (&$page) {
                return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                    'totalCount'    => $reporting['totalCount'],
                    'progressCount' => $reporting['progressCount'],
                    'partialCount'    => $reporting['partialCount'],
                    'progressColour' => 'green',
                    'partialColour' => 'orange',
                    'width'         => 'w-64',
                ]);
            });

        echo $table->render($progress);
    }
}
