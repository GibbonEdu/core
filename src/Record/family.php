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
 * Family Record
 *
 * @version	11th August 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class family extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonFamily';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonFamilyID';
	
	/**
	 * @var	array	$adults
	 */
	protected $adults = array();
	
	/**
	 * @var	array	$children
	 */
	protected $children = array();
	
	/**
	 * @var	array	$relationships
	 */
	protected $relationships = array();
	
	/**
	 * @var	stdClass	$title
	 */
	protected $title;
	
	/**
	 * Unique Test
	 *
	 * @version	2nd August 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('name');
		foreach ($required as $name) 
			if (empty($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		if (! is_null($this->record->familySync))
		{
			$data = array('familySync' => $this->record->familySync, 'gibbonFamilyID' => $this->record->gibbonFamilyID);
			$sql = 'SELECT * FROM `gibbonFamily` WHERE `familySync` = :familySync AND NOT `gibbonFamilyID` = :gibbonFamilyID';
			$tester = clone $this;
			$s = $tester->findAll($sql, $data);
			if (count($s) > 0)
				return $this->uniqueFailed('A record with the familySync already exists.', 'Debug', $this->table, $data) ;
		}
		return true ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * get Adults
	 *
	 * @version	23rd July 2016
	 * @since	23rd July 2016
	 * @param	integer		$id	Family ID
	 * @return	array
	 */
	public function getAdults($id)
	{
		$data = array('gibbonFamilyID' => $id);
		$sql = 'SELECT * 
			FROM `gibbonFamilyAdult`, `gibbonPerson` 
			WHERE `gibbonFamilyAdult`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
				AND `gibbonFamilyID` = :gibbonFamilyID 
			ORDER BY `surname`, `preferredName`';
		$v = new person($this->view);
		$this->adults = $v->findAll($sql, $data);
		return $this->adults ;
	}
	
	/**
	 * get Children
	 *
	 * @version	23rd July 2016
	 * @since	23rd July 2016
	 * @param	integer		$id	Family ID
	 * @return	array
	 */
	public function getChildren($id)
	{
		$data = array('gibbonFamilyID' => $id);
		$sql = 'SELECT * 
			FROM `gibbonFamilyChild`, `gibbonPerson` 
			WHERE `gibbonFamilyChild`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
			 	AND `gibbonFamilyID` = :gibbonFamilyID 
			ORDER BY `surname`, `preferredName` ';
		$v = new person($this->view);
		$this->children = $v->findAll($sql, $data);
		return $this->children ;
	}
	
	/**
	 * get Relationships
	 *
	 * @version	23rd July 2016
	 * @since	23rd July 2016
	 * @param	integer		$id	Family ID
	 * @return	array
	 */
	public function getRelationships($id)
	{
		$data = array('gibbonFamilyID' => $id);
		$sql = 'SELECT * FROM `gibbonFamilyRelationship` WHERE `gibbonFamilyID` = :gibbonFamilyID';
		$v = new familyRelationship($this->view);
		$this->relationships = $v->findAll($sql, $data);
		return $this->relationships ;
	}
	
	/**
	 * get Child
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	integer		$personID
	 * @return	Object		Gibbon\familyChild
	 */
	public function getChild($personID)
	{
		$data = array('gibbonFamilyID' => $this->record->gibbonFamilyID, 'gibbonPersonID' => $personID);
		$this->child = new familyChild($this->view);
		if (intval($personID) > 0)
			$this->child->findBy($data);
		else 
		{
			$this->child->defaultRecord();
			$this->child->setField('gibbonFamilyID', $this->record->gibbonFamilyID);
		}
		return $this->child ;
	}
	
	/**
	 * get Adult
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	integer		$personID
	 * @return	Object		Gibbon\familyChild
	 */
	public function getAdult($personID)
	{
		$data = array('gibbonFamilyID' => $this->record->gibbonFamilyID, 'gibbonPersonID' => $personID);
		$this->adult = new familyAdult($this->view);
		if (intval($personID) > 0)
			$this->adult->findBy($data);
		else 
		{
			$this->adult->defaultRecord();
			$this->adult->setField('gibbonFamilyID', $this->record->gibbonFamilyID);
		}
		return $this->adult ;
	}
	
	/**
	 * get Child from Children
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	integer		$personID
	 * @return	Object		Gibbon\person
	 */
	public function getChildFromChildren($personID)
	{
		$children = $this->getChildren($this->record->gibbonFamilyID);
		$this->child = null;
		foreach($children as $child)
		{
			if ($child->getField('gibbonPersonID') == $personID)
			{
				$this->child = $child;
				break;
			}
		}
		return $this->child ;
	}
	
	/**
	 * get Adult from Adults
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	integer		$personID
	 * @return	Object		Gibbon\person
	 */
	public function getAdultFromAdults($personID)
	{
		$adults = $this->getAdults($this->record->gibbonFamilyID);
		$this->adult = null;
		foreach($adults as $adult)
		{
			if ($adult->getField('gibbonPersonID') == $personID)
			{
				$this->adult = $adult;
				break;
			}
		}
		return $this->adult ;
	}
	
	/**
	 * delete Record
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	integer		$familyID
	 * @return	boolean
	 */
	public function deleteRecord($familyID)
	{
		foreach($this->getRelationships($familyID) as $w)
			if (! $w->deleteRecord($w->getField('getFamilyRelationshipID'))) return false;
		foreach($this->getChildren($familyID) as $w)
			if (! $w->deleteRecord($w->getField('getFamilyChildID'))) return false;
		foreach($this->getAdults($familyID) as $w)
			if (! $w->deleteRecord($w->getField('getFamilyAdultID'))) return false;
		return parent::deleteRecord($familyID);
	}
	
	/**
	 * retrieve Finance Invoicee Update
	 *
	 * @version	10h August 2016
	 * @since	10th August 2016
	 * @param	integer		$mailiyID 
	 * @return	object		Gibbon\Record\financeInvoiceeUpdate
	 */
	public function retrieveUpdateRecord($familyID)
	{
		$data = array(	'gibbonFamilyID' => $familyID, 
						'gibbonPersonIDUpdater' => $this->view->session->get('gibbonPersonID'),
						'status' => 'Pending'
					);
		$sql = "SELECT * 
			FROM `gibbonFamilyUpdate` 
			WHERE `gibbonFamilyID` = :gibbonFamilyID 
				AND `gibbonPersonIDUpdater` = :gibbonPersonIDUpdater 
				AND `status` = :status ";
		$obj = new familyUpdate($this->view);
		$updaters = $obj->findAll($sql, $data);
		if (count($updaters) > 1) {
			$this->success = false;
		}
		elseif (count($updaters) == 0)
		{
			$obj->defaultRecord();
			unset($this->record->status);
			$obj->success = $obj->injectPost($this->record);
		}
		else
			$obj = reset($updaters);
		return $obj ;
	}
	
	/**
	 * get Title
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @param	string		$fieldName
	 * @return	string
	 */
	public function getTitle($fieldName)
	{
		if (! $this->title instanceof \stdClass)
		{
			$this->title = new \stdClass();
			$this->title->nameAddress = 'Name Address';
			$this->title->homeAddress = 'Home Address';
			$this->title->homeAddressDictrict = 'Home Address (District)';
			$this->title->homeAddressCountry = 'Home Address (Country)';
			$this->title->languageHomePrimary = 'Home Language - Primary';
			$this->title->languageHomeSecondary = 'Home Language - Secondary';
		}
		if (! empty($this->title->$fieldName))
			return $this->title->$fieldName;
		return $fieldName ;
	}
}
