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

namespace Gibbon\Module\Staff\Tables;

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\System\SettingGateway;

/**
 * CoverageDates
 *
 * Reusable DataTable class for displaying the info for coverage dates.
 *
 * @version v18
 * @since   v18
 */
class CoverageDates
{
    protected $session;
    protected $db;
    protected $staffCoverageGateway;
    protected $staffCoverageDateGateway;
    protected $staffAbsenceDateGateway;
    protected $coverageMode;

    public function __construct(Session $session, Connection $db, SettingGateway $settingGateway, StaffCoverageGateway $staffCoverageGateway, StaffCoverageDateGateway $staffCoverageDateGateway, StaffAbsenceDateGateway $staffAbsenceDateGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
        $this->coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
    }

    public function create($gibbonStaffCoverageID)
    {
        $coverage = $this->staffCoverageGateway->getByID($gibbonStaffCoverageID);
        $dates = $this->staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID)->toDataSet();

        return $this->createFromDates($coverage['status'], $dates);
    }

    public function createFromAbsence($gibbonStaffAbsenceID, $status)
    {
        $dates = $this->staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID, true)->toDataSet();

        return $this->createFromDates($status, $dates);
    }

    protected function createFromDates($status, $dates) {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $canManage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');

        $coverageByTimetable = count(array_filter($dates->toArray(), function($item) {
            return !empty($item['foreignTableID']);
        }));

        if ($coverageByTimetable) {
            $dates->transform(function (&$item) {
                if (empty($item['foreignTableID'])) return;

                $times = $this->staffCoverageDateGateway->getCoverageTimesByForeignTable($item['foreignTable'], $item['foreignTableID'], $item['date']);

                $item['period'] = $times['period'] ?? '';
                $item['contextName'] = $times['contextName'] ?? '';
            });
        }

        $table = DataTable::create('staffCoverageDates')->withData($dates);

        $table->addMetaData('blankSlate', __('Coverage is required but has not been requested yet.'));

        $table->addColumn('date', __('Date'))
            ->format(Format::using('dateReadable', 'date'))
            ->formatDetails(function ($coverage) {
                return Format::small(Format::dayOfWeekName($coverage['date']));
            });

        if ($coverageByTimetable) {
            $table->addColumn('period', __('Period'))
                ->description(__('Time'))
                ->formatDetails([AbsenceFormats::class, 'timeDetails']);

            $table->addColumn('contextName', __('Cover'));
        } else {
            $table->addColumn('timeStart', __('Time'))
                  ->format([AbsenceFormats::class, 'timeDetails']);
        }

        if ($canManage && $status != 'Pending Approval') {
            $table->addColumn('value', __('Value'));
        }

        if ($status != 'Requested' && $status != 'Pending Approval') {
            $table->addColumn('coverage', __('Coverage'))
                ->width('20%')
                ->format([AbsenceFormats::class, 'coverage']);
        }

        $table->addColumn('notes', __('Notes'))->format(Format::using('truncate', 'notes', 60));

        // ACTIONS
        $canDelete = count($dates) > 1;

        if ($canManage) {
            $table->addActionColumn()
                ->addParam('gibbonStaffCoverageID')
                ->addParam('gibbonStaffCoverageDateID')
                ->addParam('gibbonCourseClassID')
                ->addParam('date')
                ->format(function ($coverage, $actions) use ($canDelete) {

                    if ($this->coverageMode == 'Assigned' && $coverage['absenceStatus'] == 'Approved') {
                        if (empty($coverage['gibbonPersonIDCoverage'])) {
                            $actions->addAction('assign', __('Assign'))
                                ->setURL('/modules/Staff/coverage_planner_assign.php')
                                ->setIcon('attendance')
                                ->addClass('mr-1 -mt-px')
                                ->modalWindow(900, 700)
                                ->append('<img src="themes/Default/img/page_new.png" class="w-4 h-4 absolute ml-4 mt-4 pointer-events-none">');
                        } else {
                            $actions->addAction('cancel', __('Unassign'))
                                ->setURL('/modules/Staff/coverage_planner_unassign.php')
                                ->setIcon('attendance')
                                ->addClass('mr-1 -mt-px')
                                ->modalWindow(650, 250)
                                ->append('<img src="themes/Default/img/iconCross.png" class="w-4 h-4 absolute ml-4 mt-4 pointer-events-none">');
                        }
                    }

                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Staff/coverage_manage_edit_edit.php');

                    if ($canDelete) {
                        $actions->addAction('deleteInstant', __('Delete'))
                            ->setIcon('garbage')
                            ->isDirect()
                            ->setURL('/modules/Staff/coverage_manage_edit_deleteProcess.php')
                            ->addConfirmation(__('Are you sure you wish to delete this record?'));
                    }

                });
        }

        return $table;
    }
}
