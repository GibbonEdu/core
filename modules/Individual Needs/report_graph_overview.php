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
use Gibbon\UI\Chart\Chart;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\IndividualNeeds\INGateway;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/report_graph_overview.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

    $onClickURL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/';
    $onClickURL .= !empty($gibbonYearGroupID)? 'in_summary.php&gibbonFormGroupID=' : 'report_graph_overview.php&gibbonYearGroupID=';

    // DATA
    $inGateway = $container->get(INGateway::class);
    $criteria = $inGateway->newQueryCriteria()
        ->sortBy(['gibbonYearGroup.sequenceNumber', 'gibbonFormGroup.name'])
        ->fromPOST();

    $inCounts = $inGateway->queryINCountsBySchoolYear($criteria, $session->get('gibbonSchoolYearID'), $gibbonYearGroupID);
    $chartData = $inCounts->toArray();

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Individual Needs Overview'));
        $page->scripts->add('chart');

        if (!empty($gibbonYearGroupID)) {
            $page->navigator->addHeaderAction('clear', __('Clear Filters'))
                ->setURL('/modules/Individual Needs/report_graph_overview.php');
        }

        // SETUP CHART
        $chart = Chart::create('overview', 'bar');
        $chart->setLabels(array_column($chartData, 'labelName'));
        $chart->setMetaData(array_column($chartData, 'labelID'));
        $chart->setOptions([
            'tooltip' => [
                'mode' => 'label',
            ],
        ]);

        $chart->addDataset('total', __('Total Students'))
            ->setData(array_column($chartData, 'studentCount'));

        $chart->addDataset('in', __('Individual Needs'))
            ->setData(array_column($chartData, 'inCount'));

        $chart->onClick('function(event, elements) {
            if (elements[0] == undefined) return;
            var index = elements[0].index;
            var labelID = this.config._config.metadata[index];
            window.location = "'.$onClickURL.'" + labelID;
        }');

        // RENDER CHART
        echo $chart->render();
    }

    $table = ReportTable::createPaginated('inOverview', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Individual Needs').': '.($gibbonYearGroupID ? __('Form Group') : __('Year Groups')));

    $table->addColumn('labelName', $gibbonYearGroupID ? __('Form Group') : __('Year Groups'))
        ->sortable(['gibbonYearGroup.sequenceNumber', 'gibbonFormGroup.name'])
        ->format(function ($inData) use ($onClickURL) {
            return Format::link($onClickURL.$inData['labelID'], $inData['labelName']);
        });

    $table->addColumn('studentCount', __('Total Students'));
    $table->addColumn('inCount', __('Individual Needs'));

    echo $table->render($inCounts);
}
