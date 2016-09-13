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
use Gibbon\config;
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
	 * Gibbon\config
	 */
	protected $config ;

	/**
	 * Attendance Types
	 * @var array
	 */
	protected $attendanceTypes = array();
	protected $presentDescriptors = array();
	protected $lateDescriptors = array();
	protected $absentDescriptors = array();

	/**
	 * Attendance Reasons
	 * @var array
	 */
	protected $attendanceReasons = array();
	protected $unexcusedReasons = array();
	protected $excusedReasons = array();
	protected $medicalReasons = array();

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
    public function __construct($session = NULL, $config = NULL, $pdo = NULL)
    {
    	if ($session === NULL)
            $this->session = new session();
        else
            $this->session = $session ;

        if ($config === NULL)
            $this->config = new config();
        else
            $this->config = $config ;

        if ($pdo === NULL)
            $this->pdo = new sqlConnection();
        else
            $this->pdo = $pdo ;

    	$this->presentDescriptors = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendancePresentDescriptors') );
        $this->lateDescriptors = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceLateDescriptors') );
        $this->absentDescriptors = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceAbsentDescriptors') );

        $this->attendanceTypes = array_merge($this->presentDescriptors, $this->lateDescriptors, $this->absentDescriptors);


        $this->unexcusedReasons = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceUnexcusedReasons') );
        $this->excusedReasons = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceExcusedReasons') );
        $this->medicalReasons = explode(',', getSettingByScope($this->pdo->getConnection(), 'Attendance', 'attendanceMedicalReasons') );

        $this->attendanceReasons = array_merge( array(' '), $this->unexcusedReasons, $this->medicalReasons, $this->excusedReasons);
    }


	public function isTypePresent( $type ) {
	    return in_array( $type, $this->presentDescriptors, true );
	}

	public function isTypeLate( $type ) {
	    return in_array( $type, $this->lateDescriptors, true );
	}

	public function isTypeAbsent( $type ) {
	    return in_array( $type, $this->absentDescriptors );
	}

	public function isReasonExcused( $type ) {
	    return in_array( $type, $this->excusedReasons, true ) || in_array( $type, $this->medicalReasons, true );
	}

	public function isReasonUnexcused( $type ) {
	    return in_array( $type, $this->unexcusedReasons, true );
	}

	public function renderAttendanceTypeSelect( $lastType = '', $name='type', $width='302px' ) {

	    $output = '';

	    $output .= "<select style='float: none; width: $width; margin-bottom: 3px' name='$name' id='$name'>";

	    if (!empty($this->attendanceTypes) && is_array($this->attendanceTypes)) {
	        foreach ($this->attendanceTypes as $attendanceType) {
	            $output .= sprintf('<option value="%1$s" %2$s/>%1$s</option>', $attendanceType, (($lastType == $attendanceType)? 'selected' : '' ) );
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