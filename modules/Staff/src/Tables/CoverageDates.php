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

namespace Gibbon\Module\Staff\Tables;

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;

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

    public function __construct(Session $session, Connection $db, StaffCoverageGateway $staffCoverageGateway, StaffCoverageDateGateway $staffCoverageDateGateway, StaffAbsenceDateGateway $staffAbsenceDateGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
    }

    public function create($gibbonStaffCoverageID)
    {
        $coverage = $this->staffCoverageGateway->getByID($gibbonStaffCoverageID);
        $dates = $this->staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID)->toDataSet();

        return $this->createFromDates($coverage['status'], $dates);
    }

    public function createFromAbsence($gibbonStaffAbsenceID, $status)
    {
        $dates = $this->staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID)->toDataSet();

        return $this->createFromDates($status, $dates);
    }

    protected function createFromDates($status, $dates) {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $canManage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');

        $coverageByTimetable = count(array_filter($dates->toArray(), function($item) {
            return !empty($item['gibbonTTDayRowClassID']);
        }));

        if ($coverageByTimetable) {
            $dates->transform(function (&$item) {
                if (empty($item['gibbonTTDayRowClassID'])) return;

                $times = $this->staffCoverageDateGateway->getCoverageTimesByTimetableClass($item['gibbonTTDayRowClassID']);
                $item['columnName'] = $times['period'];
                $item['courseNameShort'] = $times['courseName'];
                $item['classNameShort'] = $times['className'];
            });
        }

        $table = DataTable::create('staffCoverageDates')->withData($dates);

        $table->addColumn('date', __('Date'))
            ->format(Format::using('dateReadable', 'date'));

        $table->addColumn('timeStart', __('Time'))
            ->format([AbsenceFormats::class, 'timeDetails']);

        if ($coverageByTimetable) {
            $table->addColumn('columnName', __('Period'));
            $table->addColumn('courseClass', __('Class'))->format(Format::using('courseClassName', ['courseNameShort', 'classNameShort']));
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
                // ->addParam('gibbonStaffCoverageID', $gibbonStaffCoverageID)
                ->addParam('gibbonStaffCoverageDateID')
                ->format(function ($coverage, $actions) use ($canManage, $canDelete) {
                    if ($canManage) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Staff/coverage_manage_edit_edit.php');
                    }

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
