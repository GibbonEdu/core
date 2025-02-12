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
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/progress_byDepartment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Progress by Department'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingProgressGateway = $container->get(ReportingProgressGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/Reports/progress_byDepartment.php');

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($gibbonReportingCycleID)
            ->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (empty($gibbonReportingCycleID)) {
        return;
    }

    // QUERY
    $criteria = $reportingProgressGateway->newQueryCriteria()
        ->sortBy(['department', 'courseName', 'className'])
        ->fromPOST();

    $progress = $reportingProgressGateway->queryReportingProgressByDepartment($criteria, $gibbonSchoolYearID, $gibbonReportingCycleID)->toArray();
    $progressByDepartment = array_reduce($progress, function ($group, $item) {
        if (isset($group[$item['gibbonCourseClassID']])) {
            $group[$item['gibbonCourseClassID']]['progressCount'] += $item['progressCount'];
            $group[$item['gibbonCourseClassID']]['totalCount'] += $item['totalCount'];
        } else {
            $group[$item['gibbonCourseClassID']] = $item;
        }
        return $group;
    }, []);

    // DATA TABLE
    $table = DataTable::createPaginated('progress', $criteria);
    $table->setTitle(__('View'));

    $table->addColumn('department', __('Department'))
        ->width('15%');
    
    $table->addColumn('class', __('Class'))
        ->width('15%')
        ->sortable(['courseName', 'className'])
        ->format(function ($reporting) use (&$page) {
            return Format::courseClassName($reporting['courseName'], $reporting['className']);
        });

    $table->addColumn('teachers', __('Teachers'))
        ->width('20%');

    $table->addColumn('progress', __('Progress'))
        ->width('40%')
        ->sortable(['progressCount', 'totalCount'])
        ->format(function ($reporting) use (&$page) {
            return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                'progressCount' => $reporting['progressCount'],
                'totalCount'    => $reporting['totalCount'],
                'width'         => 'w-64',
            ]);
        });


    if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write.php', 'Write Reports_editAll')) {
        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonReportingCycleID')
            ->addParam('criteriaSelector')
            ->format(function ($reportingCycle, $actions) {
                $actions->addAction('view', __('View'))
                        ->setURL('/modules/Reports/reporting_write.php');
            });
    }

    echo $table->render(new DataSet(array_values($progressByDepartment)));

}
