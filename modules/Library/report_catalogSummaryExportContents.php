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

if (isActionAccessible($guid, $connection2, "/modules/Library/report_catalogSummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	$ownershipType=trim($_GET["ownershipType"]) ;
	$gibbonLibraryTypeID=trim($_GET["gibbonLibraryTypeID"]) ;
	$gibbonSpaceID=trim($_GET["gibbonSpaceID"]) ;
	$status=trim($_GET["status"]) ;
	
	print "<h1>" ;
	print "Catalog Summary" ;
	print "</h1>" ;
	
	try {
		$data=array(); 
		$sqlWhere="WHERE " ;
		if ($ownershipType!="") {
			$data["ownershipType"]=$ownershipType ;
			$sqlWhere.="ownershipType=:ownershipType AND " ; 
		}
		if ($gibbonLibraryTypeID!="") {
			$data["gibbonLibraryTypeID"]=$gibbonLibraryTypeID;
			$sqlWhere.="gibbonLibraryItem.gibbonLibraryTypeID=:gibbonLibraryTypeID AND " ; 
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
		$sql="SELECT gibbonLibraryItem.*, gibbonLibraryType.fields AS typeFields FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) $sqlWhere ORDER BY id" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th>" ;
				print "School ID" ;
			print "</th>" ;
			print "<th>" ;
				print "Name<br/>" ;
				print "<span style='font-size: 85%; font-style: italic'>Producer</span>" ;
			print "</th>" ;
			print "<th>" ;
				print _("Type") ;
			print "</th>" ;
			print "<th>" ;
				print "Location" ;
			print "</th>" ;
			print "<th>" ;
				print "Ownership<br/>" ;
				print "<span style='font-size: 85%; font-style: italic'>User/Owner</span>" ;
			print "</th>" ;
			print "<th>" ;
				print "Status<br/>" ;
				print "<span style='font-size: 85%; font-style: italic'>Borrowable</span>" ;
			print "</th>" ;
			print "<th>" ;
				print "Purchase Date<br/>" ;
				print "<span style='font-size: 85%; font-style: italic'>Vendor</span>" ;
			print "</th>" ;
			print "<th>" ;
				print "Details<br/>" ;
			print "</th>" ;
		print "</tr>" ;
		
		$count=0;
		$rowNum="odd" ;
		while ($row=$result->fetch()) {
			if ($count%2==0) {
				$rowNum="even" ;
			}
			else {
				$rowNum="odd" ;
			}
			$count++ ;
			
			//COLOR ROW BY STATUS!
			print "<tr class=$rowNum>" ;
				print "<td>" ;
					print "<b>" . $row["id"] . "</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>" . $row["name"] . "</b>" ;
					if ($row["producer"]!="") {
						print " ; <span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
					}
				print "</td>" ;
				print "<td>" ;
					try {
						$dataType=array("gibbonLibraryTypeID"=>$row["gibbonLibraryTypeID"]); 
						$sqlType="SELECT name FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID" ;
						$resultType=$connection2->prepare($sqlType);
						$resultType->execute($dataType);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultType->rowCount()==1) {
						$rowType=$resultType->fetch() ;
						print $rowType["name"] ;
					}
				print "</td>" ;
				print "<td>" ;
					if ($row["gibbonSpaceID"]!="") {
						try {
							$dataSpace=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
							$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
							$resultSpace=$connection2->prepare($sqlSpace);
							$resultSpace->execute($dataSpace);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSpace->rowCount()==1) {
							$rowSpace=$resultSpace->fetch() ;
							print $rowSpace["name"] ;
						}
					}
					if ($row["locationDetail"]!="") {
						print " ; <span style='font-size: 85%; font-style: italic'>" . $row["locationDetail"] . "</span>" ;
					}
				print "</td>" ;
				print "<td>" ;
					if ($row["ownershipType"]=="School") {
						print $_SESSION[$guid]["organisationNameShort"] ;
					}
					else if ($row["ownershipType"]=="Individual") {
						print "Individual" ;
					}
					if ($row["gibbonPersonIDOwnership"]!="") {
						try {
							$dataPerson=array("gibbonPersonID"=>$row["gibbonPersonIDOwnership"]); 
							$sqlPerson="SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
							$resultPerson=$connection2->prepare($sqlPerson);
							$resultPerson->execute($dataPerson);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultPerson->rowCount()==1) {
							$rowPerson=$resultPerson->fetch() ;
							print "; <span style='font-size: 85%; font-style: italic'>" . formatName($rowPerson["title"], $rowPerson["preferredName"], $rowPerson["surname"], "Staff", FALSE, TRUE) . "</span>" ;
						}
					}
				print "</td>" ;
				print "<td>" ;
					print $row["status"] ;
					print " ; <span style='font-size: 85%; font-style: italic'>" . $row["borrowable"] . "</span>" ;
				print "</td>" ;
				print "<td>" ;
					if ($row["purchaseDate"]=="") {
						print "<i>" . _('Unknown') . "</i>" ;
					}
					else {
						print dateConvertBack($guid, $row["purchaseDate"]) . " ; " ;
					}
					if ($row["vendor"]!="") {
						print "; <span style='font-size: 85%; font-style: italic'>" . $row["vendor"] . "</span>" ;
					}
				print "</td>" ;
				print "<td>" ;
					$typeFields=unserialize($row["typeFields"]) ;
					$fields=unserialize($row["fields"]) ;
					foreach ($typeFields as $typeField) {
						if ($fields[$typeField["name"]]!="") {
							print "<b>" . $typeField["name"] . ": </b>" ;
							print $fields[$typeField["name"]] . " ; " ;
						}
					}
				print "</td>" ;
			print "</tr>" ;
		}
		if ($count==0) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=2>" ;
					print _("There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}
?>