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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffDutyPersonGateway;
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
    protected $staffDutyPersonGateway;
    protected $activityGateway;
    protected $settingGateway;
    protected $staffCoverageGateway;

    public function __construct(
        StaffAbsenceGateway $staffAbsenceGateway,
        StaffAbsenceDateGateway $staffAbsenceDateGateway,
        TimetableDayDateGateway $timetableDayDateGateway,
        SchoolYearSpecialDayGateway $schoolYearSpecialDayGateway,
        StaffDutyPersonGateway $staffDutyPersonGateway,
        ActivityGateway $activityGateway,
        SettingGateway $settingGateway,
        StaffCoverageGateway $staffCoverageGateway
    ) {
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->staffAbsenceDateGateway = $staffAbsenceDateGateway;
        $this->timetableDayDateGateway = $timetableDayDateGateway;
        $this->schoolYearSpecialDayGateway = $schoolYearSpecialDayGateway;
        $this->staffDutyPersonGateway = $staffDutyPersonGateway;
        $this->activityGateway = $activityGateway;
        $this->settingGateway = $settingGateway;
        $this->staffCoverageGateway = $staffCoverageGateway;
    }

    public function getCurrentAttendanceStatus($gibbonSchoolYearID, $gibbonPersonID, $title, $preferredName, $surname)
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        $staffName = Format::name($title, $preferredName, $surname, 'Staff', false, true);

        // Check if the staff member is absent today
        $criteria =  $this->staffAbsenceGateway->newQueryCriteria(true)->filterBy('date', 'Today')->filterBy('status', 'Approved');
        $absences = $this->staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID)->toArray();

        if (count($absences) > 0) {                    
            $absenceMessage = '';
            $isFullDayAbsent = false;
            $isPartiallyAbsent = false;

            foreach ($absences as $absence) {
                $absenceDetails = $this->staffAbsenceDateGateway->getByAbsenceAndDate($absence['gibbonStaffAbsenceID'], $today);

                 if ($absenceDetails['allDay'] == 'Y') {
                    $isFullDayAbsent = true;
                    $absenceMessage = __('{name} is absent all day today.', ['name' => $staffName]);
                } else {
                    if ($currentTime >= $absenceDetails['timeStart'] && $currentTime < $absenceDetails['timeEnd']) {
                        $isPartiallyAbsent = true;
                        $absenceMessage = __('{name} is partially absent today.', ['name' => $staffName]);
                    }
                }

                if ($isFullDayAbsent || $isPartiallyAbsent) {
                    $absenceMessage .= '<br/><br/><ul>';
                    $time = $absenceDetails['allDay'] == 'N' ? Format::timeRange($absenceDetails['timeStart'], $absenceDetails['timeEnd']) : __('All Day');
                    $absenceMessage .= '<li>'.Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']).' - '.$time.'</li>';
                    
                    if ($absenceDetails['coverage'] == 'Accepted') {
                        $absenceMessage .= '<li>'.__('Coverage').': '.Format::name($absenceDetails['titleCoverage'], $absenceDetails['preferredNameCoverage'], $absenceDetails['surnameCoverage'], 'Staff', false, true).'</li>';
                    }
                    $absenceMessage .= '</ul>';

                    return Format::alert($absenceMessage, 'warning');
                }
            }
        }

        // Staff is present today
        $presentMessage = __('{name} is present today.', ['name' => $staffName]);
        $presentMessage .= '<br/><br/><ul>';
        $locationDetermined = false;

        // Check if today is a special day
        $specialDay =  $this->schoolYearSpecialDayGateway->getSpecialDayByDate($today);

        // Check if staff is in class now
        if (!$locationDetermined && (empty($specialDay['cancelClasses']) || $specialDay['cancelClasses'] != 'Y')) {
            $classes = $this->timetableDayDateGateway->selectTimetabledPeriodsByPersonAndDateRange($gibbonPersonID, $today, $today)->fetchAll();
            $currentClass = null;

            // Find the current ongoing class
            foreach ($classes as $class) {
                if ($class['timeStart'] <= $currentTime && $class['timeEnd'] > $currentTime) {
                    $currentClass = $class;
                    break;
                }
            }

            if ($currentClass) {
                // Check if the current class is off timetable
                if (!empty($specialDay) && $specialDay['type'] == 'Off Timetable' && 
                    $this->schoolYearSpecialDayGateway->getIsClassOffTimetableByDate($gibbonSchoolYearID, $currentClass['gibbonCourseClassID'], $today)) {
                    $presentMessage .= '<li>'.__('Current class is off timetable for {reason}.', ['reason' => $specialDay['name']]).'</li>';
                    $locationDetermined = true;
                } else {
                    // Find any room changes
                    if (!empty($currentClass['spaceChanged'])) {
                        $currentClass['roomName'] = $currentClass['roomNameChange'] ?? '';
                    }

                    // Current class location
                    $presentMessage .= '<li>'.__('Currently in {class}, {room}.', [
                        'class' => Format::courseClassName($currentClass['courseNameShort'], $currentClass['classNameShort']),
                        'room'  => $currentClass['roomName'] ?? __('No Facility')
                    ]).'</li>';
                    $locationDetermined = true;
                }
            }
        }

        // Check if staff is in Duty now
        if (!$locationDetermined && (empty($specialDay['cancelDuty']) || $specialDay['cancelDuty'] != 'Y')) {
            $staffDutyList = $this->staffDutyPersonGateway->selectDutyByPerson($gibbonPersonID)->fetchAll();
            $weekday = date('l');

            foreach ($staffDutyList as $duty) {
                if ($duty['dayOfWeek'] == $weekday && $duty['timeStart'] <= $currentTime && $duty['timeEnd'] > $currentTime) {
                    $presentMessage .= '<li>'.__('Currently on duty in {dutyLocation}.', ['duty' => $duty['name']]).'</li>';
                    $locationDetermined = true;
                    break;
                }
            }
        }

        // Check if staff is in an activity now
        if (!$locationDetermined) {
            $dateType = $this->settingGateway->getSettingByScope('Activities', 'dateType');
            $activities = $this->activityGateway->selectActiveEnrolledActivities($gibbonSchoolYearID, $gibbonPersonID, $dateType, $today)->fetchAll();
            $weekday = date('l');

            foreach ($activities as $activity) {
                if ($activity['dayOfWeek'] == $weekday && $activity['timeStart'] <= $currentTime && $activity['timeEnd'] > $currentTime) {
                    $location = !empty($activity['space']) ? $activity['space'] : (!empty($activity['locationExternal']) ? $activity['locationExternal'] : __('No Location'));

                    $presentMessage .= '<li>'.__('Currently in activity: {activity}, {location}.', ['activity' => $activity['name'], 'location' => $location
                    ]).'</li>';
                    $locationDetermined = true;
                    break;
                }
            }
        }

        // Check if staff is covering another class now
        if (!$locationDetermined) {
            $criteria = $this->staffCoverageGateway->newQueryCriteria()
                ->filterBy('dateStart', $today)
                ->filterBy('dateEnd', $today)
                ->filterBy('status', 'Accepted');
            
            $coverage = $this->staffCoverageGateway->queryCoverageByPersonCovering($criteria, $gibbonSchoolYearID, $gibbonPersonID, false);

            foreach ($coverage as $cover) {
                if ($cover['date'] == $today && $cover['timeStart'] <= $currentTime && $cover['timeEnd'] > $currentTime) {
                    $coveringFor = !empty($cover['surnameAbsence']) ? Format::name($cover['titleAbsence'], $cover['preferredNameAbsence'], $cover['surnameAbsence'], 'Staff', false, true) : Format::name($cover['titleStatus'], $cover['preferredNameStatus'], $cover['surnameStatus'], 'Staff', false, true);
                    
                    $location = $cover['roomName'] ?? __('No Facility');

                    // Handle room changes
                    if (!empty($cover['spaceChanged'])) {
                        $location = $cover['roomNameChange'] ?? __('No Facility');
                    }

                    $presentMessage .= '<li>'.__('Currently covering {context} for {person} in{room}.', [
                        'context' => __($cover['contextName']),
                        'person'  => $coveringFor,
                        'room'    => $location
                    ]).'</li>';
                    $locationDetermined = true;
                    break;
                }
            }
        }

        // If no current location found
        if (!$locationDetermined) {
            $presentMessage .= '<li>'.__('Check Timetable to find their current location.').'</li>';
        }

        $presentMessage .= '</ul>';

        return Format::alert($presentMessage, 'success');
    }
}