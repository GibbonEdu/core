<?
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

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/department_manage_edit.php&gibbonDepartmentID=$gibbonDepartmentID" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_edit.php")==FALSE) {
	//Fail 0
	$URL = $URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonDepartmentID=="") {
		//Fail1
		$URL = $URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
			$sql="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL = $URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL = $URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			//Validate Inputs
			$name=$_POST["name"] ;
			$nameShort=$_POST["nameShort"] ;
			$subjectListing=$_POST["subjectListing"] ;
			$blurb=$_POST["blurb"] ;

			if ($name=="" OR $nameShort=="") {
				//Fail 3
				$URL = $URL . "&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Move attached file, if there is one
				$time=mktime() ;
				if ($_FILES['file']["tmp_name"]!="") {
					//Check for folder in uploads based on today's date
					$path=$_SESSION[$guid]["absolutePath"] ;
					if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
						mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
					}
					$unique=FALSE;
					while ($unique==FALSE) {
						$suffix=randomPassword(16) ;
						$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9\s]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
						if (!(file_exists($path . "/" . $attachment))) {
							$unique=TRUE ;
						}
					}
					
					if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
						//Fail 5
						$URL = $URL . "&updateReturn=fail5" ;
						header("Location: {$URL}");
					}
				}
				else {
					$attachment=$row["logo"] ;
				}
							
				//Scan through staff
				$staff=$_POST["staff"] ;
				$role=$_POST["role"] ;
				if ($role=="") {
					$role="Other" ;
				}
				if (count($staff)>0) {
					foreach ($staff as $t) {
						//Check to see if person is already registered in this activity
						try {
							$dataGuest=array("gibbonPersonID"=>$t, "gibbonDepartmentID"=>$gibbonDepartmentID); 
							$sqlGuest="SELECT * FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonDepartmentID=:gibbonDepartmentID" ;
							$resultGuest=$connection2->prepare($sqlGuest);
							$resultGuest->execute($dataGuest);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
						if ($resultGuest->rowCount()==0) {
							try {
								$data=array("gibbonPersonID"=>$t, "gibbonDepartmentID"=>$gibbonDepartmentID, "role"=>$role); 
								$sql="INSERT INTO gibbonDepartmentStaff SET gibbonPersonID=:gibbonPersonID, gibbonDepartmentID=:gibbonDepartmentID, role=:role" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				}
				
				//Write to database
				try {
					$data=array("name"=>$name, "nameShort"=>$nameShort, "subjectListing"=>$subjectListing, "blurb"=>$blurb, "logo"=>$attachment,"gibbonDepartmentID"=>$gibbonDepartmentID); 
					$sql="UPDATE gibbonDepartment SET name=:name, nameShort=:nameShort, subjectListing=:subjectListing, blurb=:blurb, logo=:logo WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($partialFail==TRUE) {
					//Fail 5
					$URL = $URL . "&updateReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL = $URL . "&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>