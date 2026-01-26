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

namespace Gibbon\Module\Staff;

use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;

/**
 * Staff Attendance Status
 *
 * @version v31
 * @since   v31
 */
class StaffAttendanceStatus
{
    protected $staffAbsenceGateway;
    protected $staffAbsenceDateGateway;

    public function __construct(
        StaffAbsenceGateway $staffAbsenceGateway,
		StaffAbsenceDateGateway $staffAbsenceDateGateway,
    ) {
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
    }

    public function getCurrentAttendanceStatus($gibbonPersonID, $title, $preferredName, $surname)
    {

        // Display a message if the staff member is absent today.
        $criteria =  $this->staffAbsenceGateway->newQueryCriteria(true)->filterBy('date', 'Today')->filterBy('status', 'Approved');
        $absences = $this->staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID)->toArray();

        if (count($absences) > 0) {                          
            foreach ($absences as $absence) {
                $absenceMessage = $absence['allDay'] == 'Y' ? __('{name} is absent all day today.', [
                'name' => Format::name($title, $preferredName, $surname, 'Staff', false, true)]) : __('{name} is partially absent today.', [
                'name' => Format::name($title, $preferredName, $surname, 'Staff', false, true)]);

                $absenceMessage .= '<br/><br/><ul>';

                $details = $this->staffAbsenceDateGateway->getByAbsenceAndDate($absence['gibbonStaffAbsenceID'], date('Y-m-d'));
                $time = $details['allDay'] == 'N' ? Format::timeRange($details['timeStart'], $details['timeEnd']) : __('All Day');

                $absenceMessage .= '<li>'.Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']).'  '.$time.'</li>';
                if ($details['coverage'] == 'Accepted') {
                    $absenceMessage .= '<li>'.__('Coverage').': '.Format::name($details['titleCoverage'], $details['preferredNameCoverage'], $details['surnameCoverage'], 'Staff', false, true).'</li>';
                }
            }
            $absenceMessage .= '</ul>';

            return $details['allDay'] == 'Y' ? Format::alert($absenceMessage, 'warning') : Format::alert($absenceMessage, 'message');
        }
        return '';
    }
}


