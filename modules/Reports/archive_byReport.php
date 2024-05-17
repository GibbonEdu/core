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
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('View by Report'));

    $page->return->addReturns(['error3' => __('The selected record does not exist, or you do not have access to it.')]);

    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Past Reports');
    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    // School Year Picker
    if (!empty($gibbonSchoolYearID) && $canViewPastReports) {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);
    }

    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    // QUERY
    $criteria = $reportArchiveEntryGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'reportIdentifier', 'name'])
        // ->filterBy('active', 'Y')
        ->fromPOST();

    $archives = $reportArchiveEntryGateway->queryArchiveReportsBySchoolYear($criteria, $gibbonSchoolYearID, $roleCategory, $canViewDraftReports, $canViewPastReports);

    // GRID TABLE
    $table = DataTable::createPaginated('reportsView', $criteria);
    $table->setTitle(__('View'));

    $table->addColumn('reportIdentifier', __('Report'));
    $table->addColumn('timestampModified', __('Date'))->format(Format::using('dateReadable', 'timestampModified'));
    $table->addColumn('readCount', __('Read'))
        ->width('30%')
        ->format(function ($report) use (&$page) {
            if ($report['totalCount'] == 0) return Format::small(__('N/A'));

            return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                'progressName'   => __('Read'),
                'progressColour' => 'green',
                'progressCount'  => $report['readCount'],
                'totalCount'     => $report['totalCount'],
                'width'          => 'w-48',
            ]);
        });

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonReportID')
        ->addParam('reportIdentifier')
        ->format(function ($report, $actions) {
            $actions->addAction('view', __('View'))
                    ->setURL('/modules/Reports/archive_byReport_view.php');
        });

    echo $table->render($archives);
}
