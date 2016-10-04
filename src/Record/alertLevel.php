<?php
/**
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
/**
 */
namespace Gibbon\Record ;

/**
 * Alert Level Record
 *
 * @version	2nd October 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class alertLevel extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonAlertLevel';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonAlertLevelID';
	
	/**
	 * @var	array	$alerts
	 */
	protected $alerts = array();
	
	/**
	 * Unique Test
	 *
	 * @version	7th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('color','name','nameShort','colorBG','sequenceNumber');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data = array('name' => $this->record->name, 'nameShort' => $this->record->nameShort, 'alertLevelID' => $this->record->gibbonAlertLevelID, 'sequenceNumber' => $this->record->sequenceNumber);
		$sql = 'SELECT * 
			FROM `gibbonAlertLevel` 
			WHERE (`name` = :name OR `nameShort` = :nameShort OR `sequenceNumber` = :sequenceNumber) 
				AND NOT `gibbonAlertLevelID` = :alertLevelID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ; 
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	11th July 2016
	 * @since	11th July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
	
	/**
	 * get Alert
	 *
	 * @version	15th August 2016
	 * @since	copied from functions.php
	 * @param	integer		$alertLevelID	Gibbon Alert ID
	 * @return	mixed		array || false
	 */			
	public function getAlert($alertLevelID)
	{
		$output = false ;
		if (isset($this->alerts[$alertLevelID]))
			return $this->alerts[$alertLevelID];
		if ($row = $this->find($alertLevelID)) {
			$output=array() ;
			$output["name"] 			= $this->view->__($row->name) ;
			$output["nameShort"]		= $row->nameShort ;
			$output["colour"]			= $row->color ;
			$output["colourBG"]			= $row->colorBG ;
			$output["color"]			= $row->color ;
			$output["colorBG"]			= $row->colorBG ;
			$output["description"]		= $this->view->__($row->description) ;
			$output["sequenceNumber"]	= $row->sequenceNumber ;
			$this->alerts[$alertLevelID] = $output ;
		}
		return $output ;
	}
	
	/**
	 * inject Post
	 *
	 * @version	7th September 2016
	 * @since	7th September 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$data['colour'] = isset($data['colour']) ? strtoupper($data['colour']) : '';
		$data['colourBG'] = isset($data['colourBG']) ? strtoupper($data['colourBG']) : '';
		return parent::injectPost($data);
	}

	/**
	 * get Alert Bar
	 *
	 * @version	2nd October 2016
	 * @since	copied from functions.php
	 * @param	integer		$personID	Person ID
	 * @param	string		$$privacy 
	 * @param	string		$divExtras 
	 * @param	boolean		$div
	 * @param	boolean		$large
	 * @return	string		HTML
	 */
	public function getAlertBar($personID, $privacy="", $divExtras="", $div = true, $large = false) {
		$output="" ;
		
		$session = $this->view->getSession();
		$config = $this->view->getConfig();
		$security = $this->view->getSecurity();
		
		$width="14" ;
		$height="13" ;
		$fontSize="12" ;
		$totalHeight="16" ;
		if ($large) {
			$width="42" ;
			$height="35" ;
			$fontSize="24" ;
			$totalHeight="45" ;
		}
	
		$highestAction = $security->getHighestGroupedAction("/modules/Students/student_view_details.php") ;
		if ($highestAction=="View Student Profile_full") {
			if ($div) {
				$output.="<div $divExtras style='width: 83px; text-align: right; height: " . $totalHeight . "px; padding: 3px 0px; margin: auto'><strong>" ;
			}
	
			//Individual Needs
			$obj = $this->view->getRecord('INPersonDescriptor');
			$w = $obj->findFirst("SELECT * 
				FROM `gibbonINPersonDescriptor` 
					JOIN `gibbonAlertLevel` ON `gibbonINPersonDescriptor`.`gibbonAlertLevelID` = `gibbonAlertLevel`.`gibbonAlertLevelID` 
				WHERE `gibbonPersonID` = :gibbonPersonID 
				ORDER BY `sequenceNumber` DESC",
				array("gibbonPersonID"=>$personID)
				);

			if (! is_null($w)) {
				if ($obj->rowCount()==1) 
					$title =  $this->__(array('Individual Need alert is set. Alert level of %1$s.', array($w->getField('name')))) ;
				else 
					$title =  $this->__(array('%1$s Individual Needs alerts are set. Maximum alert level of %2$s.', array($obj->rowCount(), $w->getField('name')))) ;

				$output.="<a style='font-size: " . $fontSize . "px; color: #" . $w->getField('color') . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Individual Needs'><div title='$title' class='alertBar' style='float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $highestColour . "; margin: -2px 0 0 2px; background-color: #" . $w->getField('colorBG') . "'>" .  $this->__('IN') . "</div></a>" ;
			}
	
			//Academic
			$alertLevelID="" ;
			$dataAlert=array("gibbonPersonIDStudent"=>$personID, "gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"));
			$sqlAlert="SELECT * 
				FROM gibbonMarkbookEntry 
					JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) 
					JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
					JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
				WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent 
					AND (attainmentConcern='Y' OR effortConcern='Y') 
					AND complete='Y' 
					AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$resultAlert = $this->view->getRecord('markbookEntry')->findAll($sqlAlert, $dataAlert);
			if (count($resultAlert) > 1 && $count($resultAlert) <= 4) {
				$alertLevelID = 003 ;
			}
			elseif (count($resultAlert) <= 8) {
				$alertLevelID = 002 ;
			}
			elseif (count($resultAlert) > 8) {
				$alertLevelID = 001 ;
			}
			if (! empty($alertLevelID)) {
				$alert = $this->getAlert($alertLevelID) ;
				if ($alert) {
					$title =  $this->view->__(array('Student has a %1$s alert for academic concern in the current academic year.', array($this->view->__($alert["name"])))) ;
					$output .= "<a style='font-size: " . $fontSize . "px; color: #" . $alert["colour"] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Markbook&filter=" . $session->get("gibbonSchoolYearID") . "'><div title='$title' class='alertBar' style='max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 2px solid #" . $alert["colour"] . "; background-color: #" . $alert["colourBG"] . "'>" .  $this->view->__('A') . "</div></a>" ;
				}
			}
	
			//Behaviour
			$alertLevelID = "" ;
			$dataAlert=array("gibbonPersonID"=>$personID);
			$sqlAlert="SELECT * 
				FROM gibbonBehaviour 
				WHERE gibbonPersonID=:gibbonPersonID 
					AND type='Negative' 
					AND date>'" . date("Y-m-d", (time()-(24*60*60*60))) . "'" ;
			$resultAlert = $this->view->getRecord('behaviour')->findAll($sqlAlert, $dataAlert);

			if (count($resultAlert) > 1 && count($resultAlert) <= 4) {
				$alertLevelID = 003 ;
			}
			elseif (count($resultAlert) <= 8) {
				$alertLevelID = 002 ;
			}
			elseif (count($resultAlert) > 8) {
				$alertLevelID = 001 ;
			}
			if ($alertLevelID!="") {
				$alert = $this->getAlert($alertLevelID) ;
				if ($alert) {
					$title =  $this->view->__(array('Student has a %1$s alert for behaviour over the past 60 days.', array($this->view->__($alert["name"])))) ;
					$output .= "<a style='font-size: " . $fontSize . "px; color: #" . $alert["colour"] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Behaviour'><div title='$title' class='alertBar' style='float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 2px solid #" . $alert["colour"] . ";  background-color: #" . $alert["colourBG"] . "'>" .  $this->view->__('B') . "</div></a>" ;
				}
			}
	
			//Medical
			$alert = $this->view->getRecord('personMedical')->getHighestMedicalRisk($personID) ;
			if ($alert) {
				$title =  $this->view->__(array('Medical alerts are set, up to a maximum of %1$s', array($this->view->__($alert['name'])))) ;
				$output .= "<a style='font-size: " . $fontSize . "px; color: #" . $alert['colour'] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Medical'>
				<div title='$title' class='alertBar' style='max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $alert['colour'] . "; background-color: #" . $alert['colourBG'] . "'><strong>" .  $this->view->__('M') . "</strong></div>
				</a>" ;
			}
	
			//Privacy
			$privacySetting = $this->config->getSettingByScope("User Admin", "privacy" ) ;
			if ($privacySetting=="Y" && ! empty($privacy)) {
				$alert = $this->getAlert(001) ;
				$title = $this->view->__(array('Privacy is required: %1$s', array($privacy))) ;
				$output .= "<div title='$title' class='alertBar' style='font-size: " . $fontSize . "px; float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $alert["colour"] . "; color: #" . $alert["colour"] . "; background-color: #" . $alert["colourBG"] . "'>" .  $this->view->__('P') . "</div>" ;
			}
	
			if ($div) {
				$output.="</div>" ;
			}
		}
	
		return $output ;
	}
}
