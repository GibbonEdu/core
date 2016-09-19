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

namespace Module\System_Admin ;

use Gibbon\core\post ;
use Gibbon\helper ;
use Gibbon\trans ;
use Gibbon\Record\module ;
use Module\System_Admin\Functions\functions ;

if (! $this instanceof post) die();

$mf = new functions($this);

$moduleID = $_GET["gibbonModuleID"] ;
$URL = array('q' => '/modules/System Admin/module_manage_update.php', 'gibbonModuleID' => $moduleID) ;
$this->session->clear("moduleUpdateError") ;

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/module_manage_update.php")) {
	$this->insertMessage('return.error.0');
	$this->redirect($URL);
}
else {
	//Proceed!
	//Check if role specified
	if (intval($moduleID) < 1) {
		$this->insertMessage('return.error.1');
		$this->redirect($URL);
	}
	else {
		//NAMED
		$mObj = new module($this, $moduleID);
		
		if ($mObj->rowCount()!=1) {
			$this->insertMessage('return.error.2');
			$this->redirect($URL);
		}
		else {
			
			
			$versionDB = $_POST["versionDB"] ;
			$versionCode = $_POST["versionCode"] ;
			
			//Validate Inputs
			if (empty($versionDB) || empty($versionCode) || version_compare($versionDB, $versionCode) != -1) {
				//Fail 3
				$this->insertMessage('return.error.3');
				$this->redirect($URL);
			}
			else {	
				include GIBBON_ROOT . "src/modules/" . $mObj->getField("name") . "/CHANGEDB.php" ;
				
				$partialFail = false ;
				foreach ($sql as $version) {
					if (version_compare($version[0], $versionDB, ">") AND version_compare($version[0], $versionCode, "<=")) {
						$sqlTokens=explode(";end", $version[1]) ;
						foreach ($sqlTokens AS $sqlToken) {
							if (trim($sqlToken)!="") {
								try {
									$result=$this->pdo->getConnection()->query($sqlToken);   
								}
								catch(PDOException $e) { 
									$this->session->append("moduleUpdateError", $sqlToken . "<br/><b>" . $e->getMessage() . "</b></br><br/>"); 
									$partialFail = true ;
								}
							}
						}
					}
				}
				
				if ($partialFail) {
					$this->insertMessage('return.warning.1', 'warning');
					$this->redirect($URL);
				}
				else {
					//Update DB version
					$mObj->setField('version', $versionCode);
					if (! $mObj->writeRecord()) { 
						$this->insertMessage('return.error.2');
						$this->redirect($URL);
					}
					
					$this->insertMessage('return.success.0', 'success');
					$this->redirect($URL);
				}
			}
		}
	}
}
