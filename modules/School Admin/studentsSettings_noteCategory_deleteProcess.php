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

$gibbonStudentNoteCategoryID=$_GET["gibbonStudentNoteCategoryID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/studentsSettings_noteCategory_delete.php&gibbonStudentNoteCategoryID=" . $gibbonStudentNoteCategoryID ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/studentsSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/studentsSettings_noteCategory_delete.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonStudentNoteCategoryID=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonStudentNoteCategoryID"=>$gibbonStudentNoteCategoryID); 
			$sql="SELECT * FROM gibbonStudentNoteCategory WHERE gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()!=1) {
			$URL.="&return=error2" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("gibbonStudentNoteCategoryID"=>$gibbonStudentNoteCategoryID); 
				$sql="DELETE FROM gibbonStudentNoteCategory WHERE gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
					$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
	
			$URLDelete=$URLDelete . "&return=success0" ;
			header("Location: {$URLDelete}");
		}
	}
}
?>