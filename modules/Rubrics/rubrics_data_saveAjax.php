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

include "./moduleFunctions.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$mode=$_GET["mode"] ;
if ($mode=="Add") {
	try {
		$data=array("gibbonRubricID"=>$_GET["gibbonRubricID"], "gibbonPersonID"=>$_GET["gibbonPersonID"], "gibbonRubricCellID"=>$_GET["gibbonRubricCellID"], "contextDBTable"=>$_GET["contextDBTable"], "contextDBTableID"=>$_GET["contextDBTableID"]); 
		$sql="INSERT INTO gibbonRubricEntry SET gibbonRubricID=:gibbonRubricID, gibbonPersonID=:gibbonPersonID, gibbonRubricCellID=:gibbonRubricCellID, contextDBTable=:contextDBTable, contextDBTableID=:contextDBTableID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
}
if ($mode=="Remove") {
	try {
		$data=array("gibbonRubricID"=>$_GET["gibbonRubricID"], "gibbonPersonID"=>$_GET["gibbonPersonID"], "gibbonRubricCellID"=>$_GET["gibbonRubricCellID"], "contextDBTable"=>$_GET["contextDBTable"], "contextDBTableID"=>$_GET["contextDBTableID"]); 
		$sql="DELETE FROM gibbonRubricEntry WHERE gibbonRubricID=:gibbonRubricID AND gibbonPersonID=:gibbonPersonID AND gibbonRubricCellID=:gibbonRubricCellID AND contextDBTable=:contextDBTable AND contextDBTableID=:contextDBTableID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
}

?>