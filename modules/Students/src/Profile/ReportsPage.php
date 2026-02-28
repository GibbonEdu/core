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

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Services\Format;
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
    private ReportArchiveEntryGateway $reportArchiveGateway;

    public function __construct(
        Session $session,
        ReportArchiveEntryGateway $reportArchiveGateway
    ) {
        parent::__construct($session);
        $this->reportArchiveGateway = $reportArchiveGateway;
    }

    /**
     * Check if the current user has permission to view reports
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Reports', 'archive_byStudent_view');
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
            return Format::alert(__('Invalid student ID.'));
        }

        $criteria = $this->reportArchiveGateway->newQueryCriteria(true)
            ->sortBy('timestampModified', 'DESC')
            ->fromPOST();

        $reports = $this->reportArchiveGateway->queryArchiveByStudent($criteria, $this->gibbonSchoolYearID, $this->gibbonPersonID);

        $table = DataTable::createPaginated('reportsView', $criteria);
        $table->setTitle(__('Reports'));

        $table->addColumn('reportIdentifier', __('Report'))
            ->format(function ($report) {
                return '<b>'.$report['reportName'].'</b><br/><span class="text-xs">'.$report['reportIdentifier'].'</span>';
            });

        $table->addColumn('schoolYear', __('School Year'));

        $table->addColumn('timestampModified', __('Date'))
            ->format(Format::using('date', 'timestampModified'));

        $table->addColumn('status', __('Status'))
            ->format(function ($report) {
                return Format::tag(__($report['status']), $report['status'] == 'Final' ? 'success' : 'dull');
            });

        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $this->gibbonSchoolYearID)
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('gibbonReportArchiveEntryID')
            ->format(function ($report, $actions) {
                $actions->addAction('view', __('View'))
                    ->setIcon('page_right')
                    ->directLink()
                    ->setURL('/modules/Reports/archive_byStudent_download.php');
            });

        return $table->render($reports);
    }
}
