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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\Tables\DataTable;

/**
 * ReportsPage
 * 
 * Displays generated reports archive for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class ReportsPage extends ProfilePage
{
    private UserGateway $userGateway;
    private StudentGateway $studentGateway;
    private ReportArchiveEntryGateway $reportArchiveEntryGateway;

    public function __construct(
        Session $session,
        UserGateway $userGateway,
        StudentGateway $studentGateway,
        ReportArchiveEntryGateway $reportArchiveEntryGateway
    ) {
        parent::__construct($session);
        $this->userGateway = $userGateway;
        $this->studentGateway = $studentGateway;
        $this->reportArchiveEntryGateway = $reportArchiveEntryGateway;
    }

    /**
     * Check if the current user has permission to view reports
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Reports', 'archive_byStudent_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Reports');
    }


    /**
     * Generate HTML output for the reports page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $highestActionReports = Access::get('Reports', 'archive_byStudent_view');
        $gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');

        if ($highestActionReports->allows('View by Student')) {
            $student = $this->userGateway->getByID($this->gibbonPersonID);
        } else if ($highestActionReports->allows('View Reports_myChildren')) {
            $children = $this->studentGateway
                ->selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $this->session->get('gibbonPersonID'))
                ->fetchGroupedUnique();

            if (!empty($children[$this->gibbonPersonID])) {
                $student = $this->userGateway->getByID($this->gibbonPersonID);
            }
        } else if ($highestActionReports->allows('View Reports_mine')) {
            $this->gibbonPersonID = $this->session->get('gibbonPersonID');
            $student =  $this->studentGateway->selectActiveStudentByPerson($gibbonSchoolYearID, $this->gibbonPersonID)->fetch();
        }

        if (empty($student)) {
            return Format::alert(__('You do not have access to this action.'), 'error');
        }

        $output = '';
        $criteria = $this->reportArchiveEntryGateway->newQueryCriteria()
            ->sortBy('sequenceNumber', 'DESC')
            ->sortBy(['timestampCreated'])
            ->fromPOST();


        $canViewDraftReports = Access::allows('Reports', 'archive_byStudent', 'View Draft Reports');
        $canViewPastReports = Access::allows('Reports', 'archive_byStudent', 'View Past Reports');
        $roleCategory = $this->session->get('gibbonRoleIDCurrentCategory');

        $reports = $this->reportArchiveEntryGateway->queryArchiveByStudent($criteria, $this->gibbonPersonID, $roleCategory, $canViewDraftReports, $canViewPastReports);

        $reportsBySchoolYear = array_reduce($reports->toArray(), function ($group, $item) {
            $group[$item['schoolYear']][] = $item;
            return $group;
        }, []);

        if (empty($reportsBySchoolYear)) {
            $reportsBySchoolYear = [__('Reports') => []];
        }

        foreach ($reportsBySchoolYear as $schoolYear => $reports) {

            $table = DataTable::create('reportsView');
            $table->setTitle($schoolYear);

            $table->addColumn('reportName', __('Report'))
                ->width('30%')
                ->format(function ($report) {
                    return !empty($report['reportName'])? $report['reportName'] : $report['reportIdentifier'];
                });

            $table->addColumn('yearGroup', __('Year Group'))->width('15%');
            $table->addColumn('formGroup', __('Form Group'))->width('15%');
            $table->addColumn('timestampModified', __('Date'))
                ->width('30%')
                ->format(function ($report) {
                    $output = Format::dateReadable($report['timestampModified']);
                    if ($report['status'] == 'Draft') {
                        $output .= '<span class="tag ml-2 dull">'.__($report['status']).'</span>';
                    }

                    if (!empty($report['timestampAccessed'])) {
                        $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);
                        $output .= '<span class="tag ml-2 success" title="'.$title.'">'.__('Read').'</span>';
                    }

                    return $output;
                });

            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID')
                ->format(function ($report, $actions) {
                    $actions->addAction('view', __('View'))
                        ->directLink()
                        ->addParam('action', 'view')
                        ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                        ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                        ->setURL('/modules/Reports/archive_byStudent_download.php');

                    $actions->addAction('download', __('Download'))
                        ->setIcon('download')
                        ->directLink()
                        ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                        ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                        ->setURL('/modules/Reports/archive_byStudent_download.php');
                });

            $output .= $table->render($reports);
        }

        return $output;
    }
}
