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

use Gibbon\core\security; 
use Gibbon\core\module ;
use Gibbon\core\post ;
use Module\System_Admin\Functions\functions ;

if (! $this instanceof post) die();

$URL = array('q'=>'/modules/System Admin/update.php') ;
$partialFail = false ;
$this->session->clear("systemUpdateError") ;

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/update.php")) {
	$this->insertMessage('return.error.0');
	$this->redirect($URL);
}
else {
	//Proceed!
	$type = $_GET["type"] ;
	$mf = new functions($this);
	
	if ($type != "regularRelease" AND $type != "cuttingEdge") {
		$this->insertMessage('return.error.3') ;
		$this->redirect($URL);
	}
	else if ($type == "regularRelease") { //Do regular release update
		$versionDB = $_POST["versionDB"] ;
		$versionCode = $_POST["versionCode"] ;
	
		//Validate Inputs
		if (empty($versionDB) || empty($versionCode) || version_compare($versionDB, $versionCode)!=-1) {
			$this->insertMessage('return.error.3');
			$this->redirect($URL);
		}
		else 
		{	
			$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Engine` = "MyISAM"');
			while ($table = $result->fetchObject())
				$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` ENGINE=INNODB');
			$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Collation` != "utf8_unicode_ci"');
			while ($table = $result->fetchObject())
				$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

			include $mf->getInstallPath()."CHANGEDB.php" ;
			foreach ($sql AS $version) {
				if (version_compare($version[0], $versionDB, ">") AND version_compare($version[0], $versionCode, "<=")) {
					$sqlTokens = explode(";end", $version[1]) ;
					$versionMaxLinesMax = (count($sqlTokens) - 1) ;	
					foreach ($sqlTokens AS $sqlToken) {
						if (trim($sqlToken)!="") {
							$result=$this->pdo->executeQuery(array(), $sqlToken);  
							if (! $this->pdo->getQuerySuccess()) { 
								$partialFail = true ;
								$this->session->append("systemUpdateError", $sqlToken . "<br/><b>" . $this->pdo->getError() . "</b></br><br/>") ;  
							}
						}
					}
				}
			}
			if (isset($_POST['cuttingEdgeUpgrade']) && $_POST['cuttingEdgeUpgrade'] === 'Yes')
			{
				$this->config->setSettingByScope("cuttingEdgeCode", 'Y', "System");
				$this->config->setSettingByScope("cuttingEdgeCodeLine", $versionMaxLinesMax, "System");
			}
	
			if ($partialFail) {
				$this->insertMessage('Some aspects of your request failed, but others were successful. The elements that failed are shown below:', 'warning');
				$this->redirect($URL);
			}
			else {
				//Update DB version
				if (! $this->config->setSettingByScope("version", $versionCode, "System"))
				{
					$this->insertMessage('return.error.2') ;
					$this->redirect($URL);
				}
			
				$this->insertMessage('return.success.0', 'success') ;
				$this->redirect($URL);
			}
		}
	}
	else if ($type == "cuttingEdge") { //Do cutting edge update
		$versionDB = $_POST["versionDB"] ;
		$versionCode = $_POST["versionCode"] ;
		$cuttingEdgeCodeLine = $this->config->getSettingByScope("System", "cuttingEdgeCodeLine" ) ;
		
		$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Engine` = "MyISAM"');
		while ($table = $result->fetchObject())
			$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` ENGINE=INNODB');
		$result = $this->pdo->executeQuery(array(), 'SHOW TABLE STATUS WHERE `Collation` != "utf8_unicode_ci"');
		while ($table = $result->fetchObject())
			$this->pdo->executeQuery(array(), 'ALTER TABLE `'.$table->Name.'` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

		include $mf->getInstallPath()."CHANGEDB.php" ;
		$versionMax =$sql[(count($sql))][0] ;
		$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
		$versionMaxLinesMax=(count($sqlTokens)-1) ;	
		$update = false ;
		if (version_compare($versionMax , $versionDB, ">")) {
			$update = true ;
		}
		else {
			if (version_compare($versionMaxLinesMax, $cuttingEdgeCodeLine, ">")) {
				$update = true ;
			}
		}
		
		if (! $update) { //Something went wrong...abandon!
			$this->insertMessage('return.error.2') ;
			$this->redirect($URL);
		}
		else { //Let's do it
			if (version_compare($versionMax , $versionDB, ">")) { //At least one whole verison needs to be done
				foreach ($sql AS $version) {
					$tokenCount=0 ;		
					if (version_compare($version[0], $versionDB, ">=") AND version_compare($version[0], $versionCode, "<=")) {
						$sqlTokens=explode(";end", $version[1]) ;
						if ($version[0]==$versionDB) { //Finish current version
							foreach ($sqlTokens AS $sqlToken) {
								if (version_compare($tokenCount, $cuttingEdgeCodeLine, ">=")) {
									if (trim($sqlToken)!="") { //Decide whether this has been run or not
										$result=$this->pdo->executeQuery(array(), $sqlToken);   
										if (! $this->pdo->getQuerySuccess()) { 
											$partialFail = true ;
											$this->session->append("systemUpdateError", $sqlToken . "<br/><b>" . $this->pdo->getError() . "</b></br><br/>") ; 
										}
									}
								}
								$tokenCount++ ;
							}
						}
						else { //Update intermediate versions and max version
							foreach ($sqlTokens AS $sqlToken) {
								if (trim($sqlToken)!="") { //Decide whether this has been run or not
									$result=$this->pdo->executeQuery(array(), $sqlToken);  
									if ( !$this->pdo->getQuerySuccess()) { 
										$partialFail = true ;
										$this->session->append("systemUpdateError", $sqlToken . "<br/>
										<b>" . $this->pdo->getError() . "</b></br><br/>" );   
									}
								}
							}
						}
					}
				}
			}
			else { //Less than one whole version
				//Get up to speed in max version
				foreach ($sql AS $version) {
					$tokenCount=0 ;
					if (version_compare($version[0], $versionDB, ">=") && version_compare($version[0], $versionCode, "<=")) {
						$sqlTokens=explode(";end", $version[1]) ;
						foreach ($sqlTokens AS $sqlToken) {
							if (version_compare($tokenCount, $cuttingEdgeCodeLine, ">=")) {
								if (trim($sqlToken)!="") { //Decide whether this has been run or not
									$result=$this->pdo->executeQuery(array(), $sqlToken);  
									if ( !$this->pdo->getQuerySuccess()) { 
										$partialFail = true ;
										$this->session->append("systemUpdateError", $sqlToken . "<br/><b>" . $this->pdo->getError() . "</b></br><br/>" );   
									}
								}
							}
							$tokenCount++ ;
						}
					}
				}
			}
			
			if ($partialFail) {
				$this->insertMessage('return.warning.1', 'warning') ;
				$this->redirect($URL);
			}
			else
			{
				//Update DB version
				if ( ! $this->config->setSettingByScope('version', $versionMax, 'System'))
				{ 
					$this->insertMessage('return.error.2') ;
					$this->redirect($URL);
				}
				
				//Update DB line count
				if (! $this->config->setSettingByScope('cuttingEdgeCodeLine', $versionMaxLinesMax, 'System'))
				{ 
					$this->insertMessage('return.error.2');
					$this->redirect($URL);
				}
				
				//Reset cache to force top-menu reload
				$this->session->set("pageLoads", -1) ;
				$this->insertMessage('return.success.0', 'success');
				$this->redirect($URL);
			}
		}
	}
}
