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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonTTColumnRowID=$_GET["gibbonTTColumnRowID"] ;
$gibbonTTColumnID=$_GET["gibbonTTColumnID"] ;

if ($gibbonTTColumnID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/ttColumn_edit_row_edit.php&gibbonTTColumnID=$gibbonTTColumnID&gibbonTTColumnRowID=$gibbonTTColumnRowID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttColumn_edit_row_edit.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if tt specified
		if ($gibbonTTColumnRowID=="") {
			//Fail1
			$URL.="&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
				$sql="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Validate Inputs
				$name=$_POST["name"] ;
				$nameShort=$_POST["nameShort"] ;
				$timeStart=$_POST["timeStart"] ;
				$timeEnd=$_POST["timeEnd"] ;
				$type=$_POST["type"] ;

				if ($name=="" OR $nameShort=="" OR $timeStart=="" OR $timeEnd=="" OR $type=="") {
					//Fail 3
					$URL.="&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					//Check unique inputs for uniquness
					try {
						$data=array("name"=>$name, "nameShort"=>$nameShort, "gibbonTTColumnID"=>$gibbonTTColumnID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
						$sql="SELECT * FROM gibbonTTColumnRow WHERE (name=:name OR nameShort=:nameShort) AND gibbonTTColumnID=:gibbonTTColumnID AND NOT gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					if ($result->rowCount()>0) {
						//Fail 4
						$URL.="&updateReturn=fail4" ;
						header("Location: {$URL}");
					}
					else {	
						//Write to database
						try {
							$data=array("name"=>$name, "nameShort"=>$nameShort, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "type"=>$type, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
							$sql="UPDATE gibbonTTColumnRow SET name=:name, nameShort=:nameShort, timeStart=:timeStart, timeEnd=:timeEnd, type=:type WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							exit() ;
						}
						
						//Success 0
						$URL.="&updateReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>