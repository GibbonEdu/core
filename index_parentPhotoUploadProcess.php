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

//Gibbon system-wide includes
include "./functions.php" ;
include "./config.php" ;

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

$gibbonPersonID=$_GET["gibbonPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php" ;

//Proceed!
//Check if planner specified
if ($gibbonPersonID=="" OR $gibbonPersonID!=$_SESSION[$guid]["gibbonPersonID"] OR $_FILES['file1']["tmp_name"]=="") {
	//Fail1
	$URL.="?uploadReturn=fail1" ;
	header("Location: {$URL}");
}
else {
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID); 
		$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		//Fail2
		$URL.="?uploadReturn=fail2" ;
		header("Location: {$URL}");
		BREAK ;
	}

	if ($result->rowCount()!=1) {
		//Fail 2
		$URL.="?uploadReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {	
		$attachment1=NULL ;		
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
				if ($count==0) {
					$attachment1="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $_SESSION[$guid]["username"] . "_240" . strrchr($_FILES["file1"]["name"], ".") ;
				}
				else {
					$attachment1="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $_SESSION[$guid]["username"] . "_240" . "_$count" . strrchr($_FILES["file1"]["name"], ".") ;
				}
			
				if (!(file_exists($path . "/" . $attachment1))) {
					$unique=TRUE ;
				}
				$count++ ;
			}
			
			if (!(move_uploaded_file($_FILES["file1"]["tmp_name"],$path . "/" . $attachment1))) {
				//Fail 6
				$URL.="&addReturn=fail6" ;
				header("Location: {$URL}");
				break ;
			}
		}
		
		//Check for reasonable image
		$size=getimagesize($path . "/" . $attachment1) ;
		$width=$size[0] ;
		$height=$size[1] ;
		if ($width<240 OR $height<320) {
			//Fail1
			$URL.="?uploadReturn=fail1" ;
			header("Location: {$URL}");
		}
		else if ($width>480 OR $height>640) {
			//Fail1
			$URL.="?uploadReturn=fail1" ;
			header("Location: {$URL}");
		}
		else if (($width/$height)<0.60 OR ($width/$height)>0.8) {
			//Fail1
			$URL.="?uploadReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			//UPDATE
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "attachment1"=>$attachment1, "attachment2"=>$attachment1); 
				$sql="UPDATE gibbonPerson SET image_240=:attachment1, image_75=:attachment2 WHERE gibbonPersonID=:gibbonPersonID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="?uploadReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
		
			//Update session variables
			$_SESSION[$guid]["image_240"]=$attachment1 ;
			$_SESSION[$guid]["image_75"]=$attachment1 ;
		
			$URL.="?uploadReturn=success0" ;
			//Success 0
			header("Location: {$URL}");
		}
	}
}
?>