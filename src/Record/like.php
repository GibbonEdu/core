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
 * Like Record
 *
 * @version	4th October 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class like extends record
{
	/**
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonLike';

	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonLikeID';

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
	 * count Likes By Context
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @param	string		$moduleName
	 * @param	string		$contextKeyName
	 * @param	string		$contextKeyValue
	 * @return	boolean
	 */
	function countLikesByContext($moduleName, $contextKeyName, $contextKeyValue)
	{
		$mObj = new module($this->view);
		$module = $mObj->findOneBy(array('name'=> $moduleName));
		$w = $this->findAllBy(array('gibbonModuleID'=>$module->gibbonModuleID, 'contextKeyName'=>$contextKeyName, 'contextKeyValue'=>$contextKeyValue));
		if ($this->getSuccess())
			return count($w);
		return null ;
	}

	/**
	 * can Delete
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * count Likes By Context And Giver
	 *
	 * @version	8th September 2016
	 * @since	copied from functions.php
	 * @param	string		$moduleName	Module Name
	 * @param	string		$contextKeyName	Context Key Name
	 * @param	mixed		$contextKeyValue	Context Key Value
	 * @param	integer		$personIDGiver	Person ID Giver
	 * @param	integer		$personIDRecipient  Person ID Recipient
	 * @return	mixed		Count or false
	 */
	public function countLikesByContextAndGiver($moduleName, $contextKeyName, $contextKeyValue, $personIDGiver, $personIDRecipient = null) {

		if (is_null($personIDRecipient)) {
			$data=array("moduleName"=>$moduleName, "contextKeyName"=>$contextKeyName, "contextKeyValue"=>$contextKeyValue, "gibbonPersonIDGiver"=>$personIDGiver);
			$sql="SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue
				FROM gibbonLike
				WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName)
					AND contextKeyName=:contextKeyName
					AND contextKeyValue=:contextKeyValue
					AND gibbonPersonIDGiver=:gibbonPersonIDGiver" ;
		}
		else {
			$data=array("moduleName"=>$moduleName, "contextKeyName"=>$contextKeyName, "contextKeyValue"=>$contextKeyValue, "gibbonPersonIDGiver"=>$personIDGiver, "gibbonPersonIDRecipient"=>$personIDRecipient);
			$sql="SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue
				FROM gibbonLike
				WHERE gibbonModuleID = ( SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName)
					AND contextKeyName = :contextKeyName
					AND contextKeyValue = :contextKeyValue
					AND gibbonPersonIDGiver = :gibbonPersonIDGiver
					AND gibbonPersonIDRecipient = :gibbonPersonIDRecipient" ;
		}
		$result = $this->executeQuery($data, $sql);
		if ( ! $this->getQuerySuccess())
			return false ;

		return $result->rowCount() ;
	}

	/**
	 * count Likes by Recipient
	 *
	 * $mode can be either "count" to get a numeric count, or "result" to get a result set
	 * @version	4th October 2016
	 * @since	copied from functions.php
	 * @param	integer		Person ID Recipient
	 * @param	string		Mode
	 * @param	integer		School Year ID
	 * @return	integer
	 */
	public function countLikesByRecipient($personIDRecipient, $mode="count", $schoolYearID)
	{
		$return = false ;

		$data=array("personIDRecipient"=>$personIDRecipient, "schoolYearID"=>$schoolYearID);
		$v = clone $this;
		if ($mode == "count") {
			$sql="SELECT COUNT(`gibbonLikeID`) AS `likes` FROM `gibbonLike`
				WHERE `gibbonPersonIDRecipient` = :personIDRecipient
					AND `gibbonSchoolYearID` = :schoolYearID" ;
			$return = $v->findAll($sql, $data);
		}
		else {
			$sql = "SELECT `gibbonLike`.*, `gibbonPersonID`, `image_240`, `gibbonRoleIDPrimary`, `preferredName`, `surname`
				FROM `gibbonLike`
					JOIN `gibbonPerson` ON `gibbonLike`.`gibbonPersonIDGiver` = `gibbonPerson`.`gibbonPersonID`
				WHERE `gibbonPersonIDRecipient` = :personIDRecipient
					AND `gibbonSchoolYearID` = :schoolYearID
				ORDER BY `timestamp` DESC" ;
			$return = $v->findAll($sql, $data);
		}

		if (! $v->getSuccess()) $return = false ;

		if ($mode == "count") {
			$x = reset($return);
			$return = $x->getField('likes') ;
		}

		return $return ;
	}
}
