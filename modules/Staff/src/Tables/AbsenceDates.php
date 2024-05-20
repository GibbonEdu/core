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
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\System\SettingGateway;

/**
 * AbsenceDates
 *
 * Reusable DataTable class for displaying the info and actions available for absence dates.
 *
 * @version v25
 * @since   v18
 */
class AbsenceDates
{
    protected $session;
    protected $db;
    protected $staffAbsenceGateway;
    protected $staffAbsenceDateGateway;
    protected $staffCoverageDateGateway;
    protected $coverageMode;

    public function __construct(Session $session, Connection $db, SettingGateway $settingGateway, StaffAbsenceGateway $staffAbsenceGateway, StaffAbsenceDateGateway $staffAbsenceDateGateway, StaffCoverageDateGateway $staffCoverageDateGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
    }

    public function create($gibbonStaffAbsenceID, $includeDetails = false, $includeCoverage = true)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $absence = $this->staffAbsenceGateway->getAbsenceDetailsByID($gibbonStaffAbsenceID);
        $dates = $includeCoverage
            ? $this->staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID)->toDataSet()
            : $this->staffAbsenceDateGateway->selectDatesByAbsence($gibbonStaffAbsenceID)->toDataSet();

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

        $table = DataTable::create('staffAbsenceDates')->withData($dates);

        if ($includeDetails) {
            $dateLabel = __($absence['type']).' '.__($absence['reason']);
            $timeLabel = __n('{count} Day', '{count} Days', $absence['value'], ['count' => $absence['value']]);
        } else {
            $dateLabel = __('Date');
            $timeLabel = __('Time');
        }

        $table->addColumn('date', $dateLabel)
            ->format(Format::using('dateReadable', 'date'));

        $table->addColumn('timeStart', $timeLabel)
            ->format([AbsenceFormats::class, 'timeDetails']);

        if ($includeCoverage && $coverageByTimetable) {
            $table->addColumn('columnName', __('Period'));
            $table->addColumn('courseClass', __('Class'))->format(Format::using('courseClassName', ['courseNameShort', 'classNameShort']));
        }

        if ($includeCoverage && !empty($absence['coverage'])) {

            if ($absence['coverage'] != 'Pending') {
                $table->addColumn('coverage', __('Coverage'))
                    ->width('30%')
                    ->format([AbsenceFormats::class, 'coverage']);
            }

            $table->addColumn('notes', __('Notes'))->format(Format::using('truncate', 'notes', 60));
        }

        // ACTIONS
        $canRequestCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') && (($this->coverageMode == 'Requested' && $absence['status'] == 'Approved') || ($this->coverageMode == 'Assigned' && $absence['status'] != 'Declined'));
        $canManage = isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage.php');
        $canDelete = count($dates) > 1;

        if ($canManage || $absence['gibbonPersonID'] == $this->session->get('gibbonPersonID')) {
            $table->addActionColumn()
                ->addParam('gibbonStaffAbsenceID', $gibbonStaffAbsenceID)
                ->addParam('gibbonStaffAbsenceDateID')
                ->format(function ($absence, $actions) use ($canManage, $canDelete, $canRequestCoverage) {
                    if ($canManage) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Staff/absences_manage_edit_edit.php');
                    }

                    if ($canManage && $canDelete) {
                        $actions->addAction('deleteInstant', __('Delete'))
                            ->setIcon('garbage')
                            ->isDirect()
                            ->setURL('/modules/Staff/absences_manage_edit_deleteProcess.php')
                            ->addConfirmation(__('Are you sure you wish to delete this record?'));
                    }

                    if ($canRequestCoverage && empty($absence['gibbonStaffCoverageID']) && $absence['date'] >= date('Y-m-d')) {
                        $actions->addAction('coverage', __('Request Coverage'))
                            ->setIcon('attendance')
                            ->setURL('/modules/Staff/coverage_request.php');
                    }
                });
        }

        return $table;
    }
}
