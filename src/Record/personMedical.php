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
 * Person Medical Record
 *
 * @version	15th August 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class personMedical extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonPersonMedical';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonPersonMedicalID';
	
	/**
	 * @var	stdClass	Titles for Fields
	 */
	protected $title ;

	/**
	 * @var	array of Gibbon\Record\personMedicalCondition
	 */
	protected $medicalConditions ;
	
	/**
	 * Unique Test
	 *
	 * @version	6th August 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('gibbonPersonID', 'longTermMedication', 'tetanusWithin10Years');
		foreach ($required as $name) {
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
			}
		}
		$data = array('gibbonPersonMedicalID' => $this->record->gibbonPersonMedicalID, 'gibbonPersonID' => $this->record->gibbonPersonID);
		$sql = 'SELECT * 
			FROM `gibbonPersonMedical` 
			WHERE `gibbonPersonID` = :gibbonPersonID 
				AND NOT `gibbonPersonMedicalID` = :gibbonPersonMedicalID';
		$tester = clone $this;
		$s = $tester->findAll($sql, $data);
		if (count($s) > 0)
			return $this->uniqueFailed('A medical record for the student already exists.', 'Debug', $this->table, $data) ;
		return true ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * get Title
	 *
	 * @version	6th August 2016
	 * @since	6th August 2016
	 * @param	string		$fieldName
	 * @return	string
	 */
	public function getTitle($fieldName)
	{
		if (! $this->title instanceof \stdClass)
		{
			$this->title = new \stdClass();
			$this->title->bloodType = 'Blood Type';
			$this->title->longTermMedication = 'Long Term Medication';
			$this->title->longTermMedicationDetails = 'Long Term Medication Details';
			$this->title->tetanusWithin10Years = 'Tetanus Within 10 Years';
		}
		if (! empty($this->title->$fieldName))
			return $this->title->$fieldName;
		return $fieldName ;
	}

	/**
	 * get Medical Conditions
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @return	array of Gibbon\Record\personMedical
	 */
	public function getMedicalConditions()
	{
		if (isset($this->validMedicalConditions) && $this->validMedicalConditions)
			return $this->medicalConditions ;
		$this->validMedicalConditions = false;
		$cond = new personMedicalCondition($this->view);
		$sql = 'SELECT * 
			FROM `gibbonPersonMedicalCondition` 
			WHERE `gibbonPersonMedicalID` = :medicalID 
			ORDER BY `name`';
		$this->medicalConditions = $cond->findAll($sql, array('medicalID'=>$this->record->gibbonPersonMedicalID));
		if ($cond->getSuccess() && count($this->medicalConditions) > 0)
			$this->validMedicalConditions = true;
		else
			$this->medicalConditions = array();
		return $this->medicalConditions;
	}

	/**
	 * get Highest Medical Risk
	 *
	 * Returns the risk level of the highest-risk condition for an individual
	 * @version	15th August 2016
	 * @since	copied from functions.php
	 * @return	mixed		false or array
	 */
	public function getHighestMedicalRisk() {

		$output = false ;
		$highest = array();
		$highest['sequenceNumber'] = 0;
		$alert = $this->view->getRecord('alertLevel');
		foreach($this->getMedicalConditions() as $q=>$condition)
		{
			if ($highest['sequenceNumber'] < $alert->getAlert($condition->getField('gibbonAlertLevelID'))['sequenceNumber'])
				$highest = $alert->getAlert($condition->getField('gibbonAlertLevelID'));
			$this->medicalConditions[$q]->alert = $alert->getAlert($condition->getField('gibbonAlertLevelID'));
		}
		if ($highest['sequenceNumber'] > 0)
			return $highest ;
		return false ;
	}
	
	/**
	 * get Available Condition Names
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @param	string		$fieldName
	 * @return	array		Medical Condition Names
	 */
	public function getAvailableConditionsNames($name)
	{
		$medConds = $this->view->config->getMedicalConditions();
		foreach($this->getMedicalConditions() as $condition)
		{
			foreach ($medConds as $q=>$w)
				if ($w == $condition->getField('name'))
				{
					unset($medConds[$q]);
					break ;
				}
		}
		if (! empty($name)) $medConds[] = $name;
		$medConds = array_unique($medConds);
		sort($medConds);
		return $medConds ;
	}
}
