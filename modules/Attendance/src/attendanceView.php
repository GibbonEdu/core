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

namespace Module\Attendance ;

use Gibbon\session;
use Gibbon\sqlConnection;

/**
 * Attendance display & edit class
 *
 * @version	12th Sept 2016
 * @since	12th Sept 2016
 * @author	Sandra Kuipers
 */
class attendanceView
{
	/**
	 * Gibbon\sqlConnection
	 */
	protected $pdo ;

	/**
	 * Gibbon\session
	 */
	protected $session ;

	/**
	 * Attendance Types
	 * @var array
	 */
	protected $attendanceTypes = array();

	/**
	 * Attendance Reasons
	 * @var array
	 */
	protected $attendanceReasons = array();
	protected $genericReasons = array();
	protected $medicalReasons = array();

	protected $currentDate;
	protected $last5SchoolDays = array();

	protected $guid;

	/**
     * Constructor
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    Gibbon\session
     * @param    Gibbon\config
     * @param    Gibbon\sqlConnection
     * @return   void
     */
    public function __construct(\Gibbon\Core $gibbon, \Gibbon\sqlConnection $pdo)
    {
        $this->session = $gibbon->session ;
        $this->pdo = $pdo ;

        $this->guid = $gibbon->guid();

        // Get attendance codes
        try {
	        $data = array();
	        $sql = "SELECT * FROM gibbonAttendanceCode WHERE active = 'Y' ORDER BY sequenceNumber ASC, name";
	        $result = $this->pdo->executeQuery($data, $sql);
	    } catch (PDOException $e) {
	        echo "<div class='error'>".$e->getMessage().'</div>';
	    }
	    if ($result->rowCount() > 0) {
	    	while ($attendanceCode = $result->fetch()) {
        		$this->attendanceTypes[ $attendanceCode['name'] ] = $attendanceCode;
        	}
    	}

    	// Get the current date
		$currentDate = (isset($_GET['currentDate']))? dateConvert($this->guid, $_GET['currentDate']) : date('Y-m-d');

    	// Get attendance reasons
        $this->genericReasons = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceReasons') );
        $this->medicalReasons = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceMedicalReasons') );

        //$this->attendanceReasons = array_merge( array(''), $this->genericReasons, $this->medicalReasons );
        $this->attendanceReasons = array_merge( array(''), $this->genericReasons );

        //Get last 5 school days from currentDate within the last 100
        $this->last5SchoolDays = getLastNSchoolDays($this->guid, $this->pdo->getConnection(), $currentDate, 5);
    }

    public function getAttendanceTypes() {
        return $this->attendanceTypes;
    }

    public function getAttendanceReasons() {
        return $this->attendanceReasons;
    }

    public function getAttendanceCodeByType( $type ) {
    	if ( isset($this->attendanceTypes[$type]) == false ) return '';
    	return $this->attendanceTypes[$type];
    }

	public function isTypePresent( $type ) {
		if ( isset($this->attendanceTypes[$type]) == false ) return false;
	    return ($this->attendanceTypes[$type]['direction'] == 'In');
	}

	public function isTypeLate( $type ) {
	    if ( isset($this->attendanceTypes[$type]) == false ) return false;
	    return ($this->attendanceTypes[$type]['scope'] == 'Onsite - Late');
	}

    public function isTypeLeft( $type ) {
        if ( isset($this->attendanceTypes[$type]) == false ) return false;
        return ($this->attendanceTypes[$type]['scope'] == 'Offsite - Left');
    }

	public function isTypeAbsent( $type ) {
	    if ( isset($this->attendanceTypes[$type]) == false ) return false;
	    return ($this->attendanceTypes[$type]['direction'] == 'Out' && $this->isTypeOffsite($type));
	}

    public function isTypeOnsite( $type ) {
        if ( isset($this->attendanceTypes[$type]) == false ) return false;
        return ( stristr($this->attendanceTypes[$type]['scope'], 'Onsite') !== false );
    }

    public function isTypeOffsite( $type ) {
        if ( isset($this->attendanceTypes[$type]) == false ) return false;
        return ( stristr($this->attendanceTypes[$type]['scope'], 'Offsite') !== false );
    }

	public function renderMiniHistory( $gibbonPersonID, $cssClass = '' ) {

        $schoolDays = (is_array($this->last5SchoolDays))? implode(',', $this->last5SchoolDays) : '';

        // Grab all 5 days on one query to improve page load performance
        $data = array('gibbonPersonID' => $gibbonPersonID, 'schoolDays' => $schoolDays);
        $sql = "SELECT date, type, reason FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND FIND_IN_SET(date, :schoolDays) ORDER BY gibbonAttendanceLogPerson.timestampTaken";
        $result = $this->pdo->executeQuery($data, $sql);

        $logs = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP) : array();
        $logs = array_reduce(array_keys($logs), function ($group, $date) use ($logs) {
            $group[$date] = end($logs[$date]);
            return $group;
        }, array());

        $dateFormat = $_SESSION[$this->guid]['i18n']['dateFormatPHP'];

        $output = '';
		$output .= '<table cellspacing="0" class="historyCalendarMini '. $cssClass .'">';
        $output .= '<tr>';
        for ($i = 4; $i >= 0; --$i) {
            if (!isset($this->last5SchoolDays[$i])) {
                $output .= '<td class="highlightNoData">';
                $output .= '<i>'.__('NA').'</i>';
                $output .= '</td>';
            } else {
                $date = $this->last5SchoolDays[$i];
                $currentDay = new \DateTime($date);
                $link = './index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID=' . $gibbonPersonID . '&currentDate=' . $currentDay->format($dateFormat);

                if (isset($logs[$date])) {
                    $log = $logs[$date];

                    $class = ($this->isTypeAbsent($log['type']))? 'highlightAbsent' : 'highlightPresent';
                    $linkTitle = (!empty($log['reason'])) ? $log['type'] . ': ' . $log['reason'] : $log['type'];
                } else {
                    $class = 'highlightNoData';
                    $linkTitle = '';
                }

                $output .= '<td class="'.$class.'">';
                    $output .= '<a href="'.$link.'" title="'.$linkTitle.'">';
                        $output .= $currentDay->format('d') .'<br/>';
                        $output .= '<span>'.$currentDay->format('M').'</span>';
                    $output .= '</a>';
                $output .= '</td>';
            }
        }
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
	}

	public function renderAttendanceTypeSelect( $lastType = '', $name='type', $width='302px', $future = false ) {

	    $output = '';

        // Collect the current IDs of the user
        $userRoleIDs = array();
        foreach ($_SESSION[$this->guid]['gibbonRoleIDAll'] as $role) {
            if (isset($role[0])) $userRoleIDs[] = $role[0];
        }

	    $output .= "<select style='float: none; width: $width; margin-bottom: 3px' name='$name' id='$name'>";
	    if ( !empty($this->attendanceTypes) ) {
	        foreach ($this->attendanceTypes as $name => $attendanceType) {
                // Skip non-future codes on Set Future Absence
	        	if ($future && $attendanceType['future'] == 'N') continue;

                // Check if a role is restricted - blank for unrestricted use
                if ( !empty($attendanceType['gibbonRoleIDAll']) ) {
                    $allowAttendanceType = false;
                    $rolesAllowed = explode(',', $attendanceType['gibbonRoleIDAll']);

                    foreach ($rolesAllowed as $role) {
                        if ( in_array($role, $userRoleIDs) ) {
                            $allowAttendanceType = true;
                        }
                    }
                    if ($allowAttendanceType == false) continue;
                }

	            $output .= sprintf('<option value="%1$s" %2$s/>%1$s</option>', $name, (($lastType == $name)? 'selected' : '' ) );
	        }
	    }
	    $output .= '</select>';

	    return $output;
	}


	public function renderAttendanceReasonSelect( $lastReason = '', $name='reason', $width='302px' ) {

	    $output = '';

	    $output .= "<select style='float: none; width: $width; margin-bottom: 3px' name='$name' id='$name'>";

	    if (!empty($this->attendanceReasons) && is_array($this->attendanceReasons)) {
	        foreach ($this->attendanceReasons as $attendanceReason) {
	            $output .= sprintf('<option value="%1$s" %2$s/>%1$s</option>', $attendanceReason, (($lastReason == $attendanceReason)? 'selected' : '' ) );
	        }
	    }

	    $output .= '</select>';

	    return $output;
	}

}
?>
