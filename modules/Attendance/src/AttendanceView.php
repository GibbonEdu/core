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

namespace Gibbon\Module\Attendance;

use Gibbon\Core;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Contracts\Database\Connection;

/**
 * Attendance display & edit class
 *
 * @version 12th Sept 2016
 * @since   12th Sept 2016
 */
class AttendanceView
{
    /**
     * Gibbon\Contracts\Database\Connection
     */
    protected $pdo;

    /**
     * @var SettingGateway
     */
    protected $settingGateway;

    /**
     * Attendance Types
     * @var array
     */
    protected $attendanceTypes = [];
    protected $attendanceTypesRestricted = [];
    protected $userRoleIDs = [];

    /**
     * Attendance Reasons
     * @var array
     */
    protected $attendanceReasons = [];
    protected $genericReasons = [];
    protected $medicalReasons = [];

    protected $currentDate;
    protected $last5SchoolDays = [];

    /**
     * Constructor
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    Gibbon\Core
     * @param    Gibbon\Contracts\Database\Connection
     * @param    SettingGateway $settingGateway
     * @return   void
     */
    public function __construct(Core $gibbon, Connection $pdo, SettingGateway $settingGateway)
    {
        global $session;
        $this->pdo = $pdo;
        $this->settingGateway = $settingGateway;

        // Collect the current IDs of the user
        $this->userRoleIDs = array_filter(array_column($session->get('gibbonRoleIDAll'), 0));
        // Get the current date
        $currentDate = (isset($_GET['currentDate'])) ? Format::dateConvert($_GET['currentDate']) : date('Y-m-d');

        // Get attendance codes
        $sql = "SELECT * FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY sequenceNumber ASC, name";
        $result = $this->pdo->select($sql);
        $this->setAttendanceTypes($result->rowCount() > 0? $result->fetchAll() : []);

        // Get attendance reasons
        $this->genericReasons = explode(',', $settingGateway->getSettingByScope('Attendance', 'attendanceReasons'));
        $this->medicalReasons = explode(',', $settingGateway->getSettingByScope('Attendance', 'attendanceMedicalReasons'));

        //$this->attendanceReasons = array_merge( array(''), $this->genericReasons, $this->medicalReasons );
        $this->attendanceReasons = array_merge(array(''), $this->genericReasons);

        //Get last 5 school days from currentDate within the last 100
        $this->last5SchoolDays = getLastNSchoolDays($gibbon->guid(), $this->pdo->getConnection(), $currentDate, 5);
    }

    public function getAttendanceTypes(bool $showRestricted = false)
    {
        return array_reduce($this->attendanceTypes, function ($group, $item) use ($showRestricted) {
            if ($showRestricted || $item['restricted'] != 'Y') $group[$item['name']] = __($item['name']);
            return $group;
        }, []);
    }

    public function getFutureAttendanceTypes()
    {
        return array_reduce($this->attendanceTypes, function ($group, $item) {
            if ($item['future'] == 'Y' && $item['restricted'] != 'Y') $group[$item['name']] = __($item['name']);
            return $group;
        }, []);
    }

    public function getAttendanceReasons()
    {
        return $this->attendanceReasons;
    }

    public function getAttendanceCodeByType($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return '';
        }

        return $this->attendanceTypes[$type];
    }

    public function isTypeRestricted($type)
    {
        return isset($this->attendanceTypes[$type]) && $this->attendanceTypes[$type]['restricted'] == 'Y';
    }

    public function isTypePresent($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return ($this->attendanceTypes[$type]['direction'] == 'In');
    }

    public function isTypeLate($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return ($this->attendanceTypes[$type]['scope'] == 'Onsite - Late' || $this->attendanceTypes[$type]['scope'] == 'Offsite - Late');
    }

    public function isTypeLeft($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return ($this->attendanceTypes[$type]['scope'] == 'Offsite - Left');
    }

    public function isTypeAbsent($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return ($this->attendanceTypes[$type]['direction'] == 'Out' && $this->isTypeOffsite($type));
    }

    public function isTypeOnsite($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return (stristr($this->attendanceTypes[$type]['scope'], 'Onsite') !== false);
    }

    public function isTypeOffsite($type)
    {
        if (isset($this->attendanceTypes[$type]) == false) {
            return false;
        }

        return ($this->attendanceTypes[$type]['scope'] == 'Offsite' || $this->attendanceTypes[$type]['scope'] == 'Offsite - Left');
    }

    public function renderMiniHistory($gibbonPersonID, $context, $gibbonCourseClassID = null, $cssClass = '')
    {

        $countClassAsSchool = $this->settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');

        $schoolDays = (is_array($this->last5SchoolDays)) ? implode(',', $this->last5SchoolDays) : '';

        // Grab all 5 days on one query to improve page load performance
        if ($context == 'Class') {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'schoolDays' => $schoolDays, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT date, type, reason
                    FROM gibbonAttendanceLogPerson
                    WHERE gibbonPersonID=:gibbonPersonID
                    AND gibbonCourseClassID=:gibbonCourseClassID
                    AND FIND_IN_SET(date, :schoolDays)
                    ORDER BY gibbonAttendanceLogPerson.timestampTaken";
        }
        else {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'schoolDays' => $schoolDays);
            $sql = "SELECT date, type, reason
                    FROM gibbonAttendanceLogPerson
                    WHERE gibbonPersonID=:gibbonPersonID";
                    if ($countClassAsSchool == "N") {
                        $sql .= " AND NOT context='Class'";
                    }
                    $sql .= " AND FIND_IN_SET(date, :schoolDays)
                    ORDER BY gibbonAttendanceLogPerson.timestampTaken";
        }

        $result = $this->pdo->executeQuery($data, $sql);

        $logs = ($result->rowCount() > 0) ? $result->fetchAll(\PDO::FETCH_GROUP) : array();
        $logs = array_reduce(array_keys($logs), function ($group, $date) use ($logs) {
            $group[$date] = end($logs[$date]);
            return $group;
        }, array());

        $output = '';
        $output .= '<table cellspacing="0" class="historyCalendarMini smallIntBorder ' . $cssClass . '">';
        $output .= '<tr>';
        for ($i = 4; $i >= 0; --$i) {
            if (!isset($this->last5SchoolDays[$i])) {
                $output .= '<td class="highlightNoData">';
                $output .= '<i>' . __('NA') . '</i>';
                $output .= '</td>';
            } else {
                $date = $this->last5SchoolDays[$i];
                $currentDay = new \DateTime($date);
                $link = './index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID=' . $gibbonPersonID . '&currentDate=' . Format::date($currentDay->format('Y-m-d'));

                if (isset($logs[$date])) {
                    $log = $logs[$date];

                    $class = ($this->isTypeAbsent($log['type'])) ? 'highlightAbsent' : 'highlightPresent';
                    $linkTitle = (!empty($log['reason'])) ? $log['type'] . ': ' . $log['reason'] : $log['type'];
                } else {
                    $class = 'highlightNoData';
                    $linkTitle = '';
                }

                $output .= '<td class="' . $class . '">';
                $output .= '<a href="' . $link . '" title="' . $linkTitle . '">';
                $output .= Format::date($currentDay, 'd') . '<br/>';
                $output .= '<span>' . Format::monthName($currentDay, true) . '</span>';
                $output .= '</a>';
                $output .= '</td>';
            }
        }
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
    }

    protected function setAttendanceTypes(array $attendanceTypes)
    {
        if (empty($attendanceTypes)) return [];

        $this->attendanceTypes = [];

        foreach ($attendanceTypes as $attendanceType) {
            $attendanceType['restricted'] = 'N';

            // Check if a role is restricted - blank for unrestricted use
            if (!empty($attendanceType['gibbonRoleIDAll'])) {
                $allowAttendanceType = false;
                $rolesAllowed = explode(',', $attendanceType['gibbonRoleIDAll']);

                foreach ($rolesAllowed as $role) {
                    if (in_array($role, $this->userRoleIDs)) {
                        $allowAttendanceType = true;
                    }
                }
                if ($allowAttendanceType == false) {
                    $attendanceType['restricted'] = 'Y';
                }
            }

            $this->attendanceTypes[$attendanceType['name']] = $attendanceType;
        }
    }
}
