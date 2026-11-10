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

namespace Gibbon\Module\Students;

use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

/**
 * Student Attendance Status
 *
 * @version v31
 * @since   v31
 */
class StudentAttendanceStatus
{
    protected $attendanceLogGateway;
    protected $timetableGateway;
    protected $schoolYearSpecialDayGateway;
    protected $activityGateway;
    protected $settingGateway;

    public function __construct(
        AttendanceLogPersonGateway $attendanceLogGateway,
        TimetableDayDateGateway $timetableGateway,
        SchoolYearSpecialDayGateway $schoolYearSpecialDayGateway,
        ActivityGateway $activityGateway,
        SettingGateway $settingGateway
    ) {
        $this->attendanceLogGateway = $attendanceLogGateway;
        $this->timetableGateway = $timetableGateway;
        $this->schoolYearSpecialDayGateway = $schoolYearSpecialDayGateway;
        $this->activityGateway = $activityGateway;
        $this->settingGateway = $settingGateway;
    }

    public function getCurrentAttendanceStatus($gibbonSchoolYearID, $gibbonPersonID, $preferredName, $surname)
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        $studentName = Format::name('', $preferredName, $surname, 'Student');
        $lastNonClassAttendanceLog = $this->attendanceLogGateway->selectNonClassAttendanceLogsByPersonAndDate($gibbonPersonID, $today)->fetch();

        if (empty($lastNonClassAttendanceLog)) {
            return Format::alert(__('No attendance has been recorded for {name} today yet.', [
                'name' => $studentName,
            ]), 'message');
        }

        $absent = $lastNonClassAttendanceLog['type'] == 'Absent';
        $absenceMessage = __('{name} is {type} today.', [
            'name' => $studentName,
            'type' => __($lastNonClassAttendanceLog['type']),
        ]);

        if (!$absent) {
            $details = '';
            $isStudentOffTimetableToday = $this->schoolYearSpecialDayGateway->getIsStudentOffTimetableByDate($gibbonSchoolYearID, $gibbonPersonID, $today);

            if ($isStudentOffTimetableToday) {
                $details .= '<li>'.__('The student is off timetable today for ').$isStudentOffTimetableToday['name'].'</li>';
            } else {
                $classes = $this->timetableGateway->selectTimetabledPeriodsByPersonAndDateRange($gibbonPersonID, $today, $today)->fetchAll();
                $currentClass = null;

                foreach ($classes as $class) {
                    if ($class['timeStart'] <= $currentTime && $class['timeEnd'] > $currentTime) {
                        $currentClass = $class;
                        break;
                    }
                }

                if ($currentClass) {
                    if (!empty($currentClass['spaceChanged'])) {
                        $currentClass['roomName'] = $currentClass['roomNameChange'] ?? '';
                    }

                    $className = Format::courseClassName($currentClass['courseNameShort'], $currentClass['classNameShort']);
                    $roomName = $currentClass['roomName'];

                    if (($currentClass['attendance'] ?? 'Y') == 'Y') {
                        $currentClassAttendance = $this->attendanceLogGateway->selectClassAttendanceLogsByPersonAndDate($currentClass['gibbonCourseClassID'], $gibbonPersonID, $today)->fetch() ?: [];
                        $details .= '<li>'.__('Currently, {type} in {class}, {room}.', [
                            'type'  => $currentClassAttendance['type'] ?? 'attendance has not been recorded yet',
                            'class' => $className,
                            'room'  => $roomName,
                        ]).'</li>';
                    } else {
                        $details .= '<li>'.__('Currently in {class}, {room}.', [
                            'class' => $className,
                            'room'  => $roomName,
                        ]).'</li>';
                    }
                } else {
                    $dateType = $this->settingGateway->getSettingByScope('Activities', 'dateType');
                    $activities = $this->activityGateway->selectActiveEnrolledActivities($gibbonSchoolYearID, $gibbonPersonID, $dateType, $today)->fetchAll();
                    $weekday = date('l');

                    foreach ($activities as $activity) {
                        if ($activity['dayOfWeek'] == $weekday && $activity['timeStart'] <= $currentTime && $activity['timeEnd'] > $currentTime) {
                            $location = !empty($activity['space']) ? $activity['space'] : (!empty($activity['locationExternal']) ? $activity['locationExternal'] : __('Location Unavailable'));
                            $details .= '<li>'.__('Currently in activity: {activity}, {location}.', [
                                'activity' => $activity['name'],
                                'location' => $location,
                            ]).'</li>';
                            break;
                        }
                    }
                }
            }

            if ($details != '') {
                $absenceMessage .= '<br/><br/><ul>'.$details.'</ul>';
            }
        }

        return $absent ? Format::alert($absenceMessage, 'error') : Format::alert($absenceMessage, 'message');
    }
}
