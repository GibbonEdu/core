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
    public function __construct(\Gibbon\core $gibbon, \Gibbon\sqlConnection $pdo)
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
	    return ($this->attendanceTypes[$type]['direction'] == 'Out' && $this->attendanceTypes[$type]['scope'] == 'Offsite');
	}

    public function isTypeOnsite( $type ) {
        if ( isset($this->attendanceTypes[$type]) == false ) return false;
        return ( stristr($this->attendanceTypes[$type]['scope'], 'Onsite') !== false );
    }

    public function isTypeOffsite( $type ) {
        if ( isset($this->attendanceTypes[$type]) == false ) return false;
        return ( stristr($this->attendanceTypes[$type]['scope'], 'Offsite') !== false );
    }

	public function renderMiniHistory( $gibbonPersonID, $width = '134px' ) {

		echo "<table cellspacing='0' class='historyCalendarMini' style='width:$width;' >";
        echo '<tr>';
        for ($i = 4; $i >= 0; --$i) {
            $link = '';
            if ($i > ( count($this->last5SchoolDays) - 1)) {
                echo "<td class='highlightNoData'>";
                echo '<i>'.__($this->guid, 'NA').'</i>';
                echo '</td>';
            } else {
            	$currentDayTimestamp = dateConvertToTimestamp($this->last5SchoolDays[$i]);
                try {
                    $dataLast5SchoolDays = array('gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', $currentDayTimestamp).'%');
                    $sqlLast5SchoolDays = 'SELECT type, reason FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonCourseClassID DESC, gibbonAttendanceLogPersonID DESC LIMIT 1';
                    $resultLast5SchoolDays = $this->pdo->executeQuery($dataLast5SchoolDays, $sqlLast5SchoolDays);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultLast5SchoolDays->rowCount() == 0) {
                    $class = 'highlightNoData';
                } else {
                    $link = './index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID='.$gibbonPersonID.'&currentDate='.date('d/m/Y', $currentDayTimestamp);
                    $rowLast5SchoolDays = $resultLast5SchoolDays->fetch();
                    if ($this->isTypeAbsent($rowLast5SchoolDays['type'])) {
                        $class = 'highlightAbsent';
                    } else {
                    	$class = 'highlightPresent';
                    }
                }

                echo "<td class='$class'>";
                if ($link != '') {
                	$title = (!empty($rowLast5SchoolDays['reason']))? $rowLast5SchoolDays['type'].': '.$rowLast5SchoolDays['reason'] : $rowLast5SchoolDays['type'];
                    echo "<a href='$link' title='".$title."'>";
                    echo date('d', $currentDayTimestamp).'<br/>';
                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
                    echo '</a>';
                } else {
                    echo date('d', $currentDayTimestamp).'<br/>';
                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
                }
                echo '</td>';
            }
        }
        echo '</tr>';
        echo '</table>';
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
                        if ( in_array($role, $userRoleIDs, true) ) {
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
