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
/**
 */
namespace Module\System_Admin\Functions ;

use Gibbon\core\moduleFunctions as mFBase;
use Gibbon\core\trans ;

/**
 * module Functions
 *
 * @version	24th August 2016
 * @since	20th April 2016
 * @package	Gibbon
 * @subpackage	Module
 * @subpackage	System Admin
 */
class functions extends mFBase
{
	//Sets the sequence numbers appropriately for a given first day of the week (either Sunday or Monday)
	function setFirstDayOfTheWeek($fdotw)
	{
		$return= true ;
	
		if ($fdotw!="Monday" AND $fdotw!="Sunday") {
			$return = false ;
		}
		else {
			//Remove index on sequenceNumber
			$dataIndex=array("databaseName"=>$this->config->get('dbName')); 
			$sqlIndex="SELECT * FROM information_schema.statistics WHERE table_schema=:databaseName AND table_name='gibbonDaysOfWeek' AND column_name='sequenceNumber'" ;
			$resultIndex = $this->pdo->executeQuery($dataIndex, $sqlIndex);
			if ($resultIndex->rowCount()==1)
			{
				$sqlIndex="ALTER TABLE gibbonDaysOfWeek DROP INDEX sequenceNumber" ;
				$resultIndex=$this->pdo->executeQuery(array(), $sqlIndex, '{message}');
			}	
			if (! $this->pdo->getQuerySuccess()) $return = false;
			
			$nameShort="" ;
			for ($i=1; $i<=7; $i++) {
				if ($fdotw=="Monday") {
					switch ($i) {
						case 1: { $nameShort="Mon" ; break; }
						case 2: { $nameShort="Tue" ; break; }
						case 3: { $nameShort="Wed" ; break; }
						case 4: { $nameShort="Thu" ; break; }
						case 5: { $nameShort="Fri" ; break; }
						case 6: { $nameShort="Sat" ; break; }
						case 7: { $nameShort="Sun" ; break; }
					}
				}
				else
				{
					switch ($i) {
						case 1: { $nameShort="Sun" ; break; }
						case 2: { $nameShort="Mon" ; break; }
						case 3: { $nameShort="Tue" ; break; }
						case 4: { $nameShort="Wed" ; break; }
						case 5: { $nameShort="Thu" ; break; }
						case 6: { $nameShort="Fri" ; break; }
						case 7: { $nameShort="Sat" ; break; }
					}
				}
			}
			$dataDOTW=array("sequenceNumber"=>$i, "nameShort"=>$nameShort); 
			$sqlDOTW="UPDATE gibbonDaysOfWeek SET sequenceNumber=:sequenceNumber WHERE nameShort=:nameShort" ;
			$resultDOTW=$this->pdo->executeQuery($dataDOTW, $sqlDOTW, '{message}');
			if (! $this->pdo->getQuerySuccess() ) $reurn = false;
			
			//Reinstate index on sequenceNumber
			$sqlIndex="ALTER TABLE gibbonDaysOfWeek ADD UNIQUE `sequenceNumber` (`sequenceNumber`);" ;
			$resultIndex=$this->pdo->executeQuery(array(), $sqlIndex, '{message}');
			if (! $this->pdo->getQuerySuccess() ) $reurn = false ;			
		}
		
		return $return ;
	}
		
		
	/**
	 * @version	24th August 2016
	 */
	function getThemeVersion($themeName) {
		$return = false ;
		
		$file=file(GIBBON_ROOT . "src/themes/$themeName/manifest.php") ;
		foreach($file AS $fileEntry) {
			if (substr($fileEntry,1,7)=="version") {
				$temp="" ;
				$temp=substr($fileEntry,10,-1) ;
				$temp=substr($temp, 0, strpos($temp, "\"")) ;
				$return=$temp ;
			}
		}
		
		return $return ;
	}
	
	
	function getCurrentVersion() {
		
		return $this->view->renderReturn('update.version');
	}
}
