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

use stdClass ;
/**
 * Student Enrolment Record
 *
 * @version	5th May 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class studentEnrolment extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonStudentEnrolment';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonStudentEnrolmentID';
	
	/**
	 * @var	stdClass
	 */
	protected $rollGroupTable ;
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return false ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return false ;
	}
	
	/**
	 * get Roll Group Table
	 *
	 * Gets Members of a roll group and prints them as a table.
	 * @version	1st October 2016
	 * @param	integer		$rollGroupID
	 * @param	integer		$columns
	 * @param	boolean		$confidential
	 * @param	string		$orderBy  Three modes: normal (roll order, surname, firstName), surname (surname, preferredName), preferredName (preferredNam, surname)
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function getRollGroupTable($rollGroupID, $columns, $confidential = true, $orderBy = 'normal')
	{
		if (!empty($this->rollGroupTable)) return $this->rollGroupTable->content ;
		
		$this->rollGroupTable = new stdClass ;
	
		$dataRollGroup = array('gibbonRollGroupID' => $rollGroupID, 'date1'=>date('Y-m-d'), 'date2'=>date('Y-m-d'));
		$sqlRollGroup = "SELECT * FROM `gibbonStudentEnrolment` 
			INNER JOIN `gibbonPerson` ON `gibbonStudentEnrolment`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
			WHERE `gibbonRollGroupID` = :gibbonRollGroupID 
				AND `status` = 'Full' 
				AND (`dateStart` IS NULL OR `dateStart` <= :date1) 
				AND (`dateEnd` IS NULL  OR `dateEnd` >= :date2) 
			";
		if ($orderBy == 'surname') {
			$sqlRollGroup .= "ORDER BY `surname`, `preferredName`";
		} elseif ($orderBy == 'preferredName') {
			$sqlRollGroup .= "ORDER BY `preferredName`, `surname`";
		} else {
			$sqlRollGroup .= "ORDER BY `rollOrder`, `surname`, `preferredName`";
		}
	
		$return .= "<table class='noIntBorder' cellspacing='0' style='width:100%'>";
		$count = 0;
		$el = new stdClass ;
		$el->columns = $columns ;
		$el->rollGroupID = $rollGroupID ;
		
		if ($confidential) 
			$return .= $this->renderReturn('student.rollGroups.confidential', $el);
	
		foreach($this->findAll($sqlRollGroup, $dataRollGroup) as $rowRollGroup)  {
			if ($count % $columns == 0) {
				$return .= '<tr>';
			}
			$return .= "<td style='width:20%; text-align: center; vertical-align: top'>";
	
			//Alerts, if permission allows
			if ($confidential) 
				$return .= $this->view->getRecord('alert')->getAlertBar($rowRollGroup->gibbonPersonID, $rowRollGroup->privacy, "id='confidential".$rollGroupID.'-'.$count."'");
	
			//User photo
			$return .= getUserPhoto($guid, $rowRollGroup->image_240, 75);
	
			//HEY SHORTY IT'S YOUR BIRTHDAY!
			$daysUntilNextBirthday = daysUntilNextBirthday($rowRollGroup->dob);
			if ($daysUntilNextBirthday == 0) {
				$return .= "<img title='".sprintf(__($guid, '%1$s  birthday today!'), $rowRollGroup->preferredName.'&#39;s')."' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".GIBBON_URL.'src/themes/'.$_SESSION[$guid]['gibbonThemeName."/img/gift_pink.png'/>";
			} elseif ($daysUntilNextBirthday > 0 and $daysUntilNextBirthday < 8) {
				$return .= "<img title='";
				if ($daysUntilNextBirthday != 1) {
					$return .= sprintf(__($guid, '%1$s days until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup->preferredName.'&#39;s');
				} else {
					$return .= sprintf(__($guid, '%1$s day until %2$s birthday!'), $daysUntilNextBirthday, $rowRollGroup->preferredName.'&#39;s');
				}
				$return .= "' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='".$_SESSION[$guid]['absoluteURL.'/themes/'.$_SESSION[$guid]['gibbonThemeName."/img/gift.png'/>";
			}
	
			$return .= "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowRollGroup->gibbonPersonID."'>".formatName('', $rowRollGroup->preferredName, $rowRollGroup->surname, 'Student').'</a><br/><br/></div>';
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
}
