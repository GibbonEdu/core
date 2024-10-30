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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_formGroupSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $today = time();
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Form Group Summary'));

        echo '<h2>';
        echo __('Choose Options');
        echo '</h2>';

        echo '<p>';
        echo __('By default this report counts all students who are enrolled in the current academic year and whose status is currently set to full. However, if dates are set, only those students who have start and end dates outside of the specified dates, or have no start and end dates, will be shown (irrespective of their status).');
        echo '</p>';

        if (empty($dateFrom) && !empty($dateTo)) {
            $dateFrom = date($session->get('i18n')['dateFormatPHP']);
        }
        if (empty($dateTo) && !empty($dateFrom)) {
            if (Format::timestamp(Format::dateConvert($dateFrom))>$today) {
                $dateTo = $dateFrom;
            }
            else {
                $dateTo = date($session->get('i18n')['dateFormatPHP']);
            }
        }

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_formGroupSummary.php");

        $row = $form->addRow();
            $row->addLabel('dateFrom', __('From Date'))->description(__('Start date must be before this date.'))->append('<br/>')->append(__('Format:').' ')->append($session->get('i18n')['dateFormat']);
            $row->addDate('dateFrom')->setValue($dateFrom);

        $row = $form->addRow();
            $row->addLabel('dateTo', __('To Date'))->description(__('End date must be after this date.'))->append('<br/>')->append(__('Format:').' ')->append($session->get('i18n')['dateFormat']);
            $row->addDate('dateTo')->setValue($dateTo);


        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    $reportGateway = $container->get(StudentReportGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria()
        ->sortBy(['gibbonYearGroup.sequenceNumber', 'gibbonFormGroup.nameShort'])
        ->filterBy('from', Format::dateConvert($dateFrom))
        ->filterBy('to', Format::dateConvert($dateTo))
        ->fromPOST();

    $formGroups = $reportGateway->queryStudentCountByFormGroup($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = ReportTable::createPaginated('formGroupSummary', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Form Group Summary'));

    $table->modifyRows(function ($formGroup, $row) {
        if ($formGroup['formGroup'] == __('All Form Groups')) $row->addClass('dull');
        return $row;
    });

    $table->addColumn('formGroup', __('Form Group'));
    $table->addColumn('meanAge', __('Mean Age'));
    $table->addColumn('totalMale', __('Male'));
    $table->addColumn('totalFemale', __('Female'));
    $table->addColumn('totalOther', __('Other'));
    $table->addColumn('totalUnspecified', __('Unspecified'));
    $table->addColumn('total', __('Total'));

    $formGroupsData = $formGroups->toArray();
    $filteredAges = array_filter(array_column($formGroupsData, 'meanAge'));

    $formGroupsData[] = [
        'formGroup'   => __('All Form Groups'),
        'meanAge'     => !empty($filteredAges) ? number_format(array_sum($filteredAges) / count($filteredAges), 1) : 0,
        'totalMale'   => array_sum(array_column($formGroupsData, 'totalMale')),
        'totalFemale' => array_sum(array_column($formGroupsData, 'totalFemale')),
        'totalOther' => array_sum(array_column($formGroupsData, 'totalOther')),
        'totalUnspecified' => array_sum(array_column($formGroupsData, 'totalUnspecified')),
        'total'       => array_sum(array_column($formGroupsData, 'total')),
    ];

    echo $table->render(new DataSet($formGroupsData));
}
