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
use Gibbon\Module\Reports\Sources\School;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

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
    protected $timetableDayDateGateway;
    protected $schoolYearSpecialDayGateway;


    public function __construct(
        StaffAbsenceGateway $staffAbsenceGateway,
		StaffAbsenceDateGateway $staffAbsenceDateGateway,
        TimetableDayDateGateway $timetableDayDateGateway,
        SchoolYearSpecialDayGateway $schoolYearSpecialDayGateway
    ) {
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
        $this->timetableDayDateGateway = $timetableDayDateGateway;
        $this->schoolYearSpecialDayGateway = $schoolYearSpecialDayGateway;
    }

    public function getCurrentAttendanceStatus($gibbonSchoolYearID, $gibbonPersonID, $title, $preferredName, $surname)
    {

        // Display a message if the staff member is absent today.
        $criteria =  $this->staffAbsenceGateway->newQueryCriteria(true)->filterBy('date', 'Today')->filterBy('status', 'Approved');
        $absences = $this->staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID)->toArray();
        $today = date('Y-m-d');

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
        } else {
            // Staff is present today    
            $presentMessage = '';

            // Check if today is a special day
            $specialDay =  $this->schoolYearSpecialDayGateway->getSpecialDayByDate($today);

            if (!empty($specialDay['cancelClasses']) && $specialDay['cancelClasses'] == 'Y') {
                $presentMessage .= __('{name} is present today but classes are cancelled for {reason}.', [
                    'name' => Format::name($title, $preferredName, $surname, 'Staff', false, true),
                    'reason' => $specialDay['name']]);
            } else {
                // Get all staff classes for today
                $classes = $this->timetableDayDateGateway->selectTimetabledPeriodsByPersonAndDateRange($gibbonPersonID, $today, $today)->fetchAll();
                $currentTime = date('H:i:s');
                $currentClass = null;

                // Find the current ongoing class
                foreach ($classes as $class) {
                    if ($class['timeStart'] <= $currentTime and $class['timeEnd'] > $currentTime) {
                        $currentClass = $class;
                        break;
                    }
                }

                if ($currentClass) {
                    // Check if the class location has changed
                    if (!empty($currentClass['spaceChanged'])) {
                        $currentClass['roomName'] = $currentClass['roomNameChange'] ?? '';
                    }

                    // Check if the current class is scheduled to be off timetable today
                    if (!empty($specialDay) && $specialDay['type'] == 'Off TimeTable' && $this->schoolYearSpecialDayGateway->getIsClassOffTimetableByDate($gibbonSchoolYearID, $currentClass['gibbonCourseClassID'], $today)) {
                        $presentMessage .= __('Currently, {name} is present today but the isclass off timetable', [
                            'name'  => Format::name($title, $preferredName, $surname, 'Staff', false, true),
                            'class' => Format::courseClassName($currentClass['courseNameShort'], $class['classNameShort']),
                            ]);
                    } else {
                        // Display staff's current class with location
                        $presentMessage .= __('Currently, {name} is in {class}, {room}.', [
                        'name'  => Format::name($title, $preferredName, $surname, 'Staff', false, true),
                        'class' => Format::courseClassName($currentClass['courseNameShort'], $class['classNameShort']),
                        'room'  => $currentClass['roomName']
                        ]);
                    }
                } else {
                
                    // Check if staff is in duty
                    if (!empty($specialDay['cancelDuty']) && $specialDay['cancelDuty'] == 'Y') {
                           $presentMessage .= __('{name} is present today but all duties are cancelled for {reason}.', [
                        'name' => Format::name($title, $preferredName, $surname, 'Staff', false, true),
                        'reason' => $specialDay['name']]);
                    } else {

                    }


                }
            }

            return Format::alert($presentMessage, 'success');
        }

        return '';
    }
}
