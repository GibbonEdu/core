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

    public function __construct(Session $session, Connection $db, StaffCoverageGateway $staffCoverageGateway, StaffCoverageDateGateway $staffCoverageDateGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
    }

    public function create($gibbonStaffCoverageID)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $canManage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');

        $coverage = $this->staffCoverageGateway->getByID($gibbonStaffCoverageID);
        $dates = $this->staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID)->toDataSet();
        $table = DataTable::create('staffCoverageDates')->withData($dates);

        $table->addColumn('date', __('Date'))
            ->format(Format::using('dateReadable', 'date'));

        $table->addColumn('timeStart', __('Time'))
            ->format([AbsenceFormats::class, 'timeDetails']);

        if ($canManage) {
            $table->addColumn('value', __('Value'));
        }

        if ($coverage['status'] != 'Requested') {
            $table->addColumn('coverage', __('Coverage'))
                ->width('30%')
                ->format([AbsenceFormats::class, 'coverage']);
        }

        // ACTIONS
        $canDelete = count($dates) > 1;

        if ($canManage) {
            $table->addActionColumn()
                ->addParam('gibbonStaffCoverageID', $gibbonStaffCoverageID)
                ->addParam('gibbonStaffCoverageDateID')
                ->format(function ($coverage, $actions) use ($canManage, $canDelete) {
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
