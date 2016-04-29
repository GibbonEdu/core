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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/report_catalogSummary.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Library/report_catalogSummary.php")==FALSE) {
	$URL.="&return=fail0" ;
	header("Location: {$URL}");
}
else {
	$naownershipTypeme=trim($_GET["ownershipType"]) ;
	$gibbonLibraryTypeID=trim($_GET["gibbonLibraryTypeID"]) ;
	$gibbonSpaceID=trim($_GET["gibbonSpaceID"]) ;
	$status=trim($_GET["status"]) ;

	$ownershipType=NULL ;
	if (isset($_GET["ownershipType"])) {
		$ownershipType=trim($_GET["ownershipType"]) ;
	}
	$gibbonLibraryTypeID=NULL ;
	if (isset($_GET["gibbonLibraryTypeID"])) {
		$gibbonLibraryTypeID=trim($_GET["gibbonLibraryTypeID"]) ;
	}
	$gibbonSpaceID=NULL ;
	if (isset($_GET["gibbonSpaceID"])) {
		$gibbonSpaceID=trim($_GET["gibbonSpaceID"]) ;
	}
	$status=NULL ;
	if (isset($_GET["status"])) {
		$status=trim($_GET["status"]) ;
	}
	

	try {
		$data=array(); 
		$sqlWhere="WHERE " ;
		if ($ownershipType!="") {
			$data["ownershipType"]=$ownershipType ;
			$sqlWhere.="ownershipType=:ownershipType AND " ; 
		}
		if ($gibbonLibraryTypeID!="") {
			$data["gibbonLibraryTypeID"]=$gibbonLibraryTypeID;
			$sqlWhere.="gibbonLibraryTypeID=:gibbonLibraryTypeID AND " ; 
		}
		if ($gibbonSpaceID!="") {
			$data["gibbonSpaceID"]=$gibbonSpaceID;
			$sqlWhere.="gibbonSpaceID=:gibbonSpaceID AND " ; 
		}
		if ($status!="") {
			$data["status"]=$status;
			$sqlWhere.="status=:status AND " ; 
		}
		if ($sqlWhere=="WHERE ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		$sql="SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		$URL.="&return=error3" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		$exp=new Gibbon\Excel();
		$exp->exportWithPage($guid, "./report_catalogSummaryExportContents.php","catalogSummary.xls", "ownershipType=$ownershipType&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
	}
}
?>