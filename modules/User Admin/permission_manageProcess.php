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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/permission_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/permission_manage.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	if (is_null(ini_get("max_input_vars"))!=FALSE AND ini_get("max_input_vars")<=count($_POST)) {
		//Fail 3
		$URL=$URL . "&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array(); 
			$sql="DELETE FROM gibbonPermission" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		$insertFail=FALSE ;
		for ($i=0;$i<count($_POST);$i++) {
			if (isset($_POST[$i])) {
				$gibbonActionID=substr($_POST[$i],0,7) ;
				$gibbonRoleID=substr($_POST[$i],8) ;
				$value=$gibbonActionID . "-" . $gibbonRoleID ;
				if (isset($_POST[$value])) {
					if ($_POST[$value]=="on") {
						try {
							$data=array("gibbonActionID"=>$gibbonActionID, "gibbonRoleID"=>$gibbonRoleID); 
							$sql="INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=:gibbonRoleID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$insertFail=TRUE ;
						}
					}
				}
			}
		}
		
		if ($insertFail==TRUE) {
			//Fail 2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Success0
			$URL=$URL . "&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>