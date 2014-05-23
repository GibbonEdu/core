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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/update.php" ;
$partialFail=FALSE;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/update.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$type=$_GET["type"] ;
	if ($type!="regularRelease" AND $type!="cuttingEdge") {
		//Fail 3
		$URL=$URL . "&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else if ($type=="regularRelease") { //Do regular release update
		$versionDB=$_POST["versionDB"] ;
		$versionCode=$_POST["versionCode"] ;
	
		//Validate Inputs
		if ($versionDB=="" OR $versionCode=="" OR $versionDB>=$versionCode) {
			//Fail 3
			$URL=$URL . "&updateReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {	
			include "../../CHANGEDB.php" ;
		
			foreach ($sql AS $version) {
				if ($version[0]>$versionDB AND $version[0]<=$versionCode) {
					$sqlTokens=explode(";end", $version[1]) ;
					foreach ($sqlTokens AS $sqlToken) {
						if (trim($sqlToken)!="") {
							try {
								$result=$connection2->query($sqlToken);   
							}
							catch(PDOException $e) { 
								$partialFail=TRUE;
							}
						}
					}
				}
			}
		
			if ($partialFail==TRUE) {
				//Fail 5
				$URL=$URL . "&updateReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Update DB version
				try {
					$data=array("value"=>$versionCode); 
					$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
			
				//Success 0
				$URL=$URL . "&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
	else if ($type=="cuttingEdge") { //Do cutting edge update
		$versionDB=$_POST["versionDB"] ;
		$versionCode=$_POST["versionCode"] ;
		$cuttingEdgeCodeLine=getSettingByScope( $connection2, "System", "cuttingEdgeCodeLine" ) ;
		
		include "../../CHANGEDB.php" ;
		$versionMax=$sql[(count($sql)-1)][0] ;
		$update=FALSE ;
		if ($versionMax>$versionDB) {
			$update=TRUE ;
		}
		else {
			$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
			$versionMaxLinesMax=(count($sqlTokens)-1) ;
			if ($versionMaxLinesMax>$cuttingEdgeCodeLine) {
				$update=TRUE ;
			}
		}
		
		if ($update==FALSE) { //Something went wrong...abandon!
			//Fail 3
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		else { //Let's do it
			if ($versionMax>$versionDB) { //At least one whole verison needs to be done
				foreach ($sql AS $version) {
					$tokenCount=0 ;				
					if ($version[0]>=$versionDB AND $version[0]<=$versionCode) {
						$sqlTokens=explode(";end", $version[1]) ;
						if ($version[0]==$versionDB) { //Finish current version
							foreach ($sqlTokens AS $sqlToken) {
								if ($tokenCount>=$cuttingEdgeCodeLine) {
									if (trim($sqlToken)!="") { //Decide whether this has been run or not
										try {
											$result=$connection2->query($sqlToken);   
										}
										catch(PDOException $e) { 
											$partialFail=TRUE;
										}
									}
								}
								$tokenCount++ ;
							}
						}
						else { //Update intermediate versions and max version
							foreach ($sqlTokens AS $sqlToken) {
								if (trim($sqlToken)!="") { //Decide whether this has been run or not
									try {
										$result=$connection2->query($sqlToken);   
									}
									catch(PDOException $e) { 
										$partialFail=TRUE;
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
					if ($version[0]>=$versionDB AND $version[0]<=$versionCode) {
						$sqlTokens=explode(";end", $version[1]) ;
						foreach ($sqlTokens AS $sqlToken) {
							if ($tokenCount>=$cuttingEdgeCodeLine) {
								if (trim($sqlToken)!="") { //Decide whether this has been run or not
									try {
										$result=$connection2->query($sqlToken);   
									}
									catch(PDOException $e) { 
										$partialFail=TRUE;
									}
								}
							}
							$tokenCount++ ;
						}
					}
				}
			}
			
			if ($partialFail==TRUE) {
				//Fail 5
				$URL=$URL . "&updateReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Update DB version
				try {
					$data=array("value"=>$versionMax); 
					$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				//Update DB line count
				try {
					$data=array("value"=>$versionMaxLinesMax); 
					$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='cuttingEdgeCodeLine'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
			
				//Success 0
				$URL=$URL . "&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
		
	}
}
?>