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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/house_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/house_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$name=$_POST["name"] ;
	$nameShort=$_POST["nameShort"] ;
	
	if ($name=="" OR $nameShort=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "nameShort"=>$nameShort); 
			$sql="SELECT * FROM gibbonHouse WHERE name=:name OR nameShort=:nameShort" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//Deal with file upload
			$logo="" ;
			$imageFail=FALSE ;
			if ($_FILES['file1']["tmp_name"]!="") {
				$time=time() ;
				//Check for folder in uploads based on today's date
				$path=$_SESSION[$guid]["absolutePath"] ; ;
				if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
					mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
				}
				$unique=FALSE;
				$count=0 ;
				while ($unique==FALSE AND $count<100) {
					$suffix=randomPassword(16) ;
					if ($count==0) {
						$logo="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $name . "_$suffix" . strrchr($_FILES["file1"]["name"], ".") ;
					}
					else {
						$logo="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $name . "_$suffix" . "_$count" . strrchr($_FILES["file1"]["name"], ".") ;
					}
				
					if (!(file_exists($path . "/" . $logo))) {
						$unique=TRUE ;
					}
					$count++ ;
				}
				if (!(move_uploaded_file($_FILES["file1"]["tmp_name"],$path . "/" . $logo))) {
					$logo="" ;
					$imageFail=TRUE ;
				}
			}
		
			//Write to database
			try {
				$data=array("name"=>$name, "nameShort"=>$nameShort, "logo"=>$logo); 
				$sql="INSERT INTO gibbonHouse SET name=:name, nameShort=:nameShort, logo=:logo" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($imageFail) {
				//Success 1
				$URL.="&addReturn=success1" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>