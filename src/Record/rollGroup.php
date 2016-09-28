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

use Gibbon\Record\space ;
use Gibbon\People\staff ;
/**
 * Roll Group Record
 *
 * @version	28th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class rollGroup extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonRollGroup';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonRollGroupID';
	
	/**
	 * @var	Gibbon\Record\person	
	 */
	protected	$tutor1 ;
	
	/**
	 * @var	Gibbon\People\staff	
	 */
	protected	$tutor2 ;
	
	/**
	 * @var	Gibbon\People\staff	
	 */
	protected	$tutor3 ;
	
	/**
	 * @var	array of Gibbon\People\staff	
	 */
	protected	$tutors ;
	
	/**
	 * @var	Gibbon\Record\space	
	 */
	protected	$space ;
	
	/**
	 * @var	Gibbon\Record\rollGroup	
	 */
	protected	$rollGroupNext ;
	
	/**
	 * Unique Test
	 *
	 * @version	28th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('name', 'nameShort');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		if (empty($this->record->gibbonSpaceID))
		{
			$data = array(	'name' => $this->record->name, 
							'nameShort' => $this->record->nameShort, 
							'gibbonRollGroupID' => $this->record->gibbonRollGroupID, 
							'gibbonSchoolYearID' => $this->record->gibbonSchoolYearID
						);
			$sql = 'SELECT * 
				FROM `gibbonRollGroup` 
				WHERE (`name`=:name OR `nameShort` = :nameShort) 
					AND NOT `gibbonRollGroupID` = :gibbonRollGroupID 
					AND `gibbonSchoolYearID` = :gibbonSchoolYearID';
		} else {
			$data = array(	'name' => $this->record->name, 
							'nameShort' => $this->record->nameShort, 
							'gibbonSpaceID' => $this->record->gibbonSpaceID, 
							'gibbonRollGroupID' => $this->record->gibbonRollGroupID, 
							'gibbonSchoolYearID' => $this->record->gibbonSchoolYearID
						);
			$sql = 'SELECT * 
				FROM `gibbonRollGroup` 
				WHERE (`name` = :name OR `nameShort` = :nameShort OR `gibbonSpaceID` = :gibbonSpaceID) 
					AND NOT `gibbonRollGroupID` = :gibbonRollGroupID 
					AND `gibbonSchoolYearID` = :gibbonSchoolYearID';
		}
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @param	integer		$id		Role Group ID
	 * @return	stdClass	Record
	 */
	public function find($id)
	{
		if (parent::find($id))
		{
			return $this->record;
		}
		return false ;
	}
		
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor1()
	{
		if ($this->tutor1 instanceof staff)
			return $this->tutor1 ;
		return $this->tutor1 = new staff($this->view, $this->record->gibbonPersonIDTutor);
	}
	
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor2()
	{
		if ($this->tutor2 instanceof staff)
			return $this->tutor2 ;
		return $this->tutor2 = new staff($this->view, $this->record->gibbonPersonIDTutor2);
	}
	
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor3()
	{
		if ($this->tutor3 instanceof staff)
			return $this->tutor3 ;
		return $this->tutor3 = new staff($this->view, $this->record->gibbonPersonIDTutor3);
	}
	
	/**
	 * get Space
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\Record\space
	 */
	public function getSpace()
	{
		if ($this->space instanceof space)
			return $this->space ;
		return $this->space = new space($this->view, $this->record->gibbonSpaceID);
	}
	
	/**
	 * get Space
	 *
	 * @version	7th September 2016
	 * @since	24th May 2016
	 * @return	Gibbon\Record\space
	 */
	public function getRollGroupNext()
	{
		if ($this->rollGroupNext instanceof rollGroup)
			return $this->rollGroupNext ;
		return $this->rollGroupNext = new rollGroup($this->view, $this->record->gibbonRollGroupIDNext);
	}

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * Get Roll Group Table
	 *
	 * Gets Members of a roll group and prints them as a table.<br/>
	 * Three modes: normal (roll order, surname, firstName), surname (surname, preferredName), preferredName (preferredName, surname)
	 * @version	27th May 2016
	 * @since	Copied from functions.php
	 * @param	integer		$rollGroupID		
	 * @param	integer		$columns		
	 * @param	boolean		$confidencial		
	 * @param	string		$orderBy		
	 */
	public function getRollGroupTable($rollGroupID, $columns, $confidential = true, $orderBy = 'Normal')
	{
		$return = false;
	
		$criteria = array();
		switch ($orderBy) {
			case 'surname':
				$sql = "SELECT * 
					FROM gibbonStudentEnrolment 
						INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID 
					WHERE gibbonRollGroupID=:gibbonRollGroupID 
						AND status='Full' 
						AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
						AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') 
					ORDER BY surname, preferredName";
				break ;
			case 'preferredName':
				$sql = "SELECT * 
					FROM gibbonStudentEnrolment 
						INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID 
					WHERE gibbonRollGroupID=:gibbonRollGroupID 
						AND status='Full' 
						AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."')
						AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') 
					ORDER BY preferredName, surname";
				break ;
			default:
				$sql = "SELECT * 
					FROM gibbonStudentEnrolment 
						INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID 
					WHERE gibbonRollGroupID=:gibbonRollGroupID 
						AND status='Full' 
						AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
						AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') 
					ORDER BY rollOrder, surname, preferredName";
		}
		$data = array('gibbonRollGroupID' => $rollGroupID);
	
		$return .= "<table class='noIntBorder' cellspacing='0' style='width:100%'>";
		$count = 0;
	
		if ($confidential) {
			$return .= '<tr>';
			$return .= "<td style='text-align: right' colspan='$columns'>";
			$return .= "<input checked type='checkbox' name='confidential' class='confidential' id='confidential".$rollGroupID."' value='Yes' />";
			$return .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".trans::__('Show Confidential Data').'</span>';
			$return .= '</td>';
			$return .= '</tr>';
		}
	
		foreach($this->findAll($sql, $data) as $rollGroup) {
			if ($count % $columns == 0) {
				$return .= '<tr>';
			}
			$return .= "<td style='width:20%; text-align: center; vertical-align: top'>";
	
			//Alerts, if permission allows
			if ($confidential) {
				$return .= helper::getAlertBar($rollGroup->getField('gibbonPersonID'), $rollGroup->getField('privacy'), "id='confidential".$rollGroupID.'-'.$count."'");
			}
	
			//User photo
			$return .= helper::getUserPhoto($rollGroup->getField('image_240'), 75);
	
			//HEY SHORTY IT'S YOUR BIRTHDAY!
			$daysUntilNextBirthday = helper::daysUntilNextBirthday($rollGroup->getField('dob'));
			if ($daysUntilNextBirthday == 0) {
				$return .= "<img title='".sprintf(__($guid, '%1$s  birthday today!'), $rowRollGroup['preferredName'].'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION['absoluteURL'].'/themes/'.$_SESSION['gibbonThemeName']."/img/gift_pink.png'/>";
			} elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
				$return .= "<img title='";
				if ($daysUntilNextBirthday != 1) {
					$return .= sprintf(__($guid, '%1$s days until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
				} else {
					$return .= sprintf(__($guid, '%1$s day until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup['preferredName'].'&#39;s');
				}
				$return .= "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION['absoluteURL'].'/themes/'.$_SESSION['gibbonThemeName']."/img/gift.png'/>";
			}
	
			$return .= "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rollGroup->getField('gibbonPersonID')."'>".helper::formatName('', $rollGroup->getField('preferredName'), $rollGroup->getField('surname'), 'Student').'</a><br/><br/></div>';
			$return .= '</td>';
	
			if ($count % $columns == ($columns - 1)) {
				$return .= '</tr>';
			}
			++$count;
		}
	
		for ($i = 0;$i < $columns - ($count % $columns);++$i) {
			$return .= '<td></td>';
		}
	
		if ($count % $columns != 0) {
			$return .= '</tr>';
		}
	
		$return .= '</table>';
	
		$return .= '<script type="text/javascript">
			/* Confidential Control */
			$(document).ready(function(){
				$("#confidential'.$rollGroupID."\").click(function(){
					if ($('input[id=confidential".$rollGroupID."]:checked').val()==\"Yes\" ) {";
		for ($i = 0; $i < $count; ++$i) {
			$return .= '$("#confidential'.$rollGroupID.'-'.$i.'").slideDown("fast", $("#confidential'.$i."\").css(\"{'display' : 'table-row', 'border' : 'right'}\"));";
		}
		$return .= '}
					else {';
		for ($i = 0; $i < $count; ++$i) {
			$return .= '$("#confidential'.$rollGroupID.'-'.$i.'").slideUp("fast");';
		}
		$return .= '}
				 });
			});
		</script>';
	
		return $return;
	}

	/**
	 * Get Roll Group Name
	 *
	 * @version	24th July 2016
	 * @since	24th July 2016
	 * @since	integer		$personID
	 * @param	integer		$yearID		
	 * @param	string				
	 */
	public function getRollGroupName($personID, $yearID)
	{
		$data = array('gibbonPersonID' => $personID, 'gibbonSchoolYearID' => $yearID);
		$sql = 'SELECT `name` 
			FROM `gibbonRollGroup` 
				JOIN `gibbonStudentEnrolment` ON `gibbonStudentEnrolment`.`gibbonRollGroupID` = `gibbonRollGroup`.`gibbonRollGroupID` 
			WHERE `gibbonPersonID` = :gibbonPersonID 
				AND `gibbonStudentEnrolment`.`gibbonSchoolYearID` = :gibbonSchoolYearID';
		$w = $this->findAll($sql, $data);
		if (count($w) !== 1)
			return '';
		$w = reset($w);
		return $w->getField('name');
	}

	/**
	 * Get Tutors
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	array			
	 */
	public function getTutors()
	{
		$this->getTutor1();
		$this->getTutor2();
		$this->getTutor3();
		$this->tutors = array();
		if ($this->tutor1->getSuccess() && $this->record->gibbonPersonIDTutor > 0)
			$this->tutors[1] = $this->tutor1;
		if ($this->tutor2->getSuccess() && $this->record->gibbonPersonIDTutor2 > 0)
			$this->tutors[2] = $this->tutor2;
		if ($this->tutor3->getSuccess() && $this->record->gibbonPersonIDTutor3 > 0)
			$this->tutors[3] = $this->tutor3;
		return $this->tutors;
	}
}
