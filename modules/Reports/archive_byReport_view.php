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
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('View by Report'), 'archive_byReport.php')
        ->add(__('View Reports'));

    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $gibbon->session->get('gibbonSchoolYearID');
    $gibbonReportID = $_GET['gibbonReportID'] ?? '';
    $reportIdentifier = $_GET['reportIdentifier'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    
    if (empty($gibbonReportID) && empty($reportIdentifier)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Reports/archive_byReport_view.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonReportID', $gibbonReportID);
    $form->addHiddenValue('reportIdentifier', $reportIdentifier);

    $reportsBySchoolYear = $reportGateway->selectActiveReportsBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonReportID', __('Report'));
        $row->addSelect('gibbonReportID')->fromArray($reportsBySchoolYear)->required()->placeholder()->selected($gibbonReportID);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->selected($gibbonRollGroupID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();
    
    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Past Reports');
    $roleCategory = getRoleCategory($gibbon->session->get('gibbonRoleIDCurrent'), $connection2);

    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy($gibbonRollGroupID ? ['surname', 'preferredName'] : ['sequenceNumber', 'name'])
        ->fromPOST();

    // QUERY
    if (empty($gibbonReportID) && !empty($reportIdentifier)) {
        $reports = $reportArchiveEntryGateway->queryArchiveByReportIdentifier($criteria, $gibbonSchoolYearID, $reportIdentifier, $gibbonYearGroupID, $gibbonRollGroupID, $roleCategory, $canViewDraftReports, $canViewPastReports);
    } elseif (!empty($gibbonRollGroupID)) {
        $reports = $reportArchiveEntryGateway->queryArchiveByReport($criteria, !empty($gibbonReportID) ? $gibbonReportID : $reportIdentifier, $gibbonYearGroupID, $gibbonRollGroupID, $roleCategory, $canViewDraftReports, $canViewPastReports);
    } elseif (!empty($gibbonYearGroupID)) {
        $reports = $reportGateway->queryRollGroupsByReport($criteria, $gibbonReportID, $gibbonYearGroupID, $roleCategory, $canViewDraftReports, $canViewPastReports);
    } else {
        $reports = $reportGateway->queryYearGroupsByReport($criteria, $gibbonReportID, $roleCategory, $canViewDraftReports, $canViewPastReports);
    }

    // Data TABLE
    $table = DataTable::createPaginated('reportsView', $criteria)->withData($reports);
    $table->setTitle(__('View'));

    if (!empty($gibbonRollGroupID)) {
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->width('25%')
            ->format(function ($person) use ($guid) {
                return Format::nameLinked($person['gibbonPersonID'],'', $person['preferredName'], $person['surname'], 'Student', true, false, ['subpage' => 'Reports']);
            });

        $table->addColumn('status', __('Last Created'))
            ->notSortable()
            ->format(function ($report) use ($roleCategory, $canViewDraftReports, $canViewPastReports, &$reportArchiveEntryGateway) {
                $output = '';
                $archive = $reportArchiveEntryGateway->getRecentArchiveEntryByReport($report['gibbonReportID'] ?? $report['reportIdentifier'], 'Single', $report['gibbonPersonID'], $roleCategory, $canViewDraftReports, $canViewPastReports);

                if ($archive) {
                    $tag = '<span class="tag ml-2 '.($archive['status'] == 'Final' ? 'success' : 'dull').'">'.__($archive['status']).'</span>';
                    $url = './modules/Reports/archive_byStudent_download.php?gibbonReportArchiveEntryID='.$archive['gibbonReportArchiveEntryID'].'&gibbonPersonID='.$report['gibbonPersonID'];
                    $title = Format::dateTimeReadable($archive['timestampModified']);
                    $output .= Format::link($url, $title).$tag;
                }

                if (!empty($report['timestampAccessed'])) {
                    $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);
                    $output .= '<span class="tag ml-2 success" title="'.$title.'">'.__('Read').'</span>';
                }

                return $output;
            });
    } elseif (!empty($gibbonYearGroupID)) {
        $table->addColumn('name', __('Name'));

        $table->addColumn('count', __('Reports'));

        $table->addColumn('read', __('Read'))
            ->notSortable()
            ->width('30%')
            ->format(function ($report) use (&$page) {
                if (empty($report['readCount'])) return Format::small(__('N/A'));

                return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                    'progressName'   => __('Read'),
                    'progressColour' => 'green',
                    'progressCount' => $report['readCount'],
                    'totalCount'    => $report['count'],
                    'width'         => 'w-48',
                ]);
            });
    } else {
        $table->addColumn('name', __('Name'));

        $table->addColumn('timestamp', __('Last Created'))
            ->notSortable()
            ->format(function ($report) use ($roleCategory, $canViewDraftReports, $canViewPastReports, &$reportArchiveEntryGateway, &$logs) {
                $archive = $reportArchiveEntryGateway->getRecentArchiveEntryByReport($report['gibbonReportID'] ?? $report['reportIdentifier'], 'Batch', $report['gibbonYearGroupID'], $roleCategory, $canViewDraftReports, $canViewPastReports);

                if ($archive) {
                    $tag = '<span class="tag ml-2 '.($archive['status'] == 'Final' ? 'success' : 'dull').'">'.__($archive['status']).'</span>';
                    $url = './modules/Reports/archive_byReport_download.php?gibbonReportArchiveEntryID='.$archive['gibbonReportArchiveEntryID'];
                    $title = Format::dateTimeReadable($archive['timestampModified']);
                    return Format::link($url, $title).$tag;
                }

                return '';
            });

        $table->addColumn('count', __('Reports'));

        $table->addColumn('read', __('Read'))
            ->notSortable()
            ->width('30%')
            ->format(function ($report) use (&$page) {
                if (empty($report['readCount'])) return Format::small(__('N/A'));

                return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                    'progressName'   => __('Read'),
                    'progressColour' => 'green',
                    'progressCount'  => $report['readCount'],
                    'totalCount'     => $report['count'],
                    'width'          => 'w-48',
                ]);
            });
    }

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonReportID', $gibbonReportID)
        ->addParam('reportIdentifier', $reportIdentifier)
        ->format(function ($report, $actions) {
            if (!empty($report['gibbonRollGroupID']) && !empty($report['gibbonPersonID'])) {
                $actions->addAction('view', __('View'))
                    ->addParam('gibbonReportID', $report['gibbonReportID'] ?? '')
                    ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                    ->setURL('/modules/Reports/archive_byStudent_view.php');
            } else {
                $actions->addAction('go', __('Go'))
                    ->setIcon('page_right')
                    ->addParam('gibbonYearGroupID', $report['gibbonYearGroupID'] ?? '')
                    ->addParam('gibbonRollGroupID', $report['gibbonRollGroupID'] ?? '')
                    ->setURL('/modules/Reports/archive_byReport_view.php');
            }
        });

    echo $table->render($reports);
}
