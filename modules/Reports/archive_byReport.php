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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('View by Report'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, ['error3' => __('The selected record does not exist, or you do not have access to it.')]);
    }

    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Past Reports');
    $roleCategory = getRoleCategory($gibbon->session->get('gibbonRoleIDCurrent'), $connection2);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $gibbon->session->get('gibbonSchoolYearID');

    // School Year Picker
    if (!empty($gibbonSchoolYearID) && $canViewPastReports) {
        $schoolYearGateway = $container->get(SchoolYearGateway::class);
        $targetSchoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);

        echo '<h2>';
        echo $targetSchoolYear['name'];
        echo '</h2>';

        echo "<div class='linkTop'>";
        if ($prevSchoolYear = $schoolYearGateway->getPreviousSchoolYearByID($gibbonSchoolYearID)) {
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$prevSchoolYear['gibbonSchoolYearID']."'>".__('Previous Year').'</a> ';
        } else {
            echo __('Previous Year').' ';
        }
        echo ' | ';
        if ($nextSchoolYear = $schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID)) {
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$nextSchoolYear['gibbonSchoolYearID']."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';
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
