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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Lending & Activity Log') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	print "<h3>" ;
		print __($guid, "Search & Filter") ;
	print "</h3>" ;
	
	//Get current filter values
	$name=NULL ;
	if (isset($_POST["name"])) {
		$name=trim($_POST["name"]) ;
	}
	if ($name=="") {
		if (isset($_GET["name"])) {
			$name=trim($_GET["name"]) ;
		}
	}
	$gibbonLibraryTypeID=NULL ;
	if (isset($_POST["gibbonLibraryTypeID"])) {
		$gibbonLibraryTypeID=trim($_POST["gibbonLibraryTypeID"]) ;
	}
	if ($gibbonLibraryTypeID=="") {
		if (isset($_GET["gibbonLibraryTypeID"])) {
			$gibbonLibraryTypeID=trim($_GET["gibbonLibraryTypeID"]) ;
		}
	}
	$gibbonSpaceID=NULL ;
	if (isset($_POST["gibbonSpaceID"])) {
		$gibbonSpaceID=trim($_POST["gibbonSpaceID"]) ;
	}
	if ($gibbonSpaceID=="") {
		if (isset($_GET["gibbonSpaceID"])) {
			$gibbonSpaceID=trim($_GET["gibbonSpaceID"]) ;
		}
	}
	$status=NULL ;
	if (isset($_POST["status"])) {
		$status=trim($_POST["status"]) ;
	}
	if ($status=="") {
		if (isset($_GET["status"])) {
			$status=trim($_GET["status"]) ;
		}
	}
	
	//Display filters
	print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending.php'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			?>
			<tr>
				<td> 
					<b><?php print __($guid, 'ID/Name/Producer') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					print "<input type='text' name='name' id='name' value='" . htmlPrep($name) . "' style='width:300px;'/>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Type') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataType=array(); 
						$sqlType="SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
						$resultType=$connection2->prepare($sqlType);
						$resultType->execute($dataType);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='gibbonLibraryTypeID' id='gibbonLibraryTypeID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowType=$resultType->fetch()) {
							$selected="" ;
							if ($rowType["gibbonLibraryTypeID"]==$gibbonLibraryTypeID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowType["gibbonLibraryTypeID"] . "'>" . __($guid, $rowType["name"]) . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Location') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					try {
						$dataLocation=array(); 
						$sqlLocation="SELECT * FROM gibbonSpace ORDER BY name" ;
						$resultLocation=$connection2->prepare($sqlLocation);
						$resultLocation->execute($dataLocation);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='gibbonSpaceID' id='gibbonSpaceID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowLocation=$resultLocation->fetch()) {
							$selected="" ;
							if ($rowLocation["gibbonSpaceID"]==$gibbonSpaceID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowLocation["gibbonSpaceID"] . "'>" . $rowLocation["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Status</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<?php
					print "<select name='status' id='status' style='width:302px'>" ;
						print "<option value=''></option>" ;
						print "<option " ; if ($status=="Available") { print "selected " ; } print "value='Available'>" . __($guid, 'Available') . "</option>" ;
						print "<option " ; if ($status=="On Loan") { print "selected " ; } print "value='On Loan'>" . __($guid, 'On Loan') . "</option>" ;
						print "<option " ; if ($status=="Repair") { print "selected " ; } print "value='Repair'>" . __($guid, 'Repair') . "</option>" ;
						print "<option " ; if ($status=="Reserved") { print "selected " ; } print "value='Reserved'>" . __($guid, 'Reserved') . "</option>" ;
					print "</select>" ;
					?>
				</td>
			</tr>
			<?php
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending.php'>" . __($guid, 'Clear Filters') . "</a> " ;
					print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	print "<h3>" ;
		print __($guid, "View") ;
	print "</h3>" ;
	
	//Search with filters applied
	try {
		$data=array(); 
		$sqlWhere="AND " ;
		$sqlWhere2="AND " ;
		if ($name!="") {
			$data["name"]="%" . $name . "%" ;
			$data["producer"]="%" . $name . "%" ;
			$data["id"]="%" . $name . "%" ;
			$data["name2"]="%" . $name . "%" ;
			$data["producer2"]="%" . $name . "%" ;
			$data["id2"]="%" . $name . "%" ;
			$sqlWhere.="(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND " ;
			$sqlWhere2.="(name LIKE :name2  OR producer LIKE :producer2 OR id LIKE :id2) AND " ; 
		}
		if ($gibbonLibraryTypeID!="") {
			$data["gibbonLibraryTypeID"]=$gibbonLibraryTypeID;
			$data["gibbonLibraryTypeID2"]=$gibbonLibraryTypeID;
			$sqlWhere.="gibbonLibraryTypeID=:gibbonLibraryTypeID AND " ; 
			$sqlWhere2.="gibbonLibraryTypeID=:gibbonLibraryTypeID2 AND " ; 
		}
		if ($gibbonSpaceID!="") {
			$data["gibbonSpaceID"]=$gibbonSpaceID;
			$data["gibbonSpaceID2"]=$gibbonSpaceID;
			$sqlWhere.="gibbonSpaceID=:gibbonSpaceID AND " ; 
			$sqlWhere2.="gibbonSpaceID=:gibbonSpaceID2 AND " ; 
		}
		if ($status!="") {
			$data["status"]=$status;
			$data["status2"]=$status;
			$sqlWhere.="status=:status AND " ; 
			$sqlWhere.="status=:status2 AND " ; 
		}
		if ($sqlWhere=="AND ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		if ($sqlWhere2=="AND ") {
			$sqlWhere2="" ;
		}
		else {
			$sqlWhere2=substr($sqlWhere2,0,-5) ;
		}
		
		$sql="(SELECT gibbonLibraryItem.*, NULL AS borrower FROM gibbonLibraryItem WHERE (status='Available' OR status='Repair' OR status='Reserved') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere)
			UNION
			(SELECT gibbonLibraryItem.*, concat(preferredName, ' ', surname) AS borrower FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE (gibbonLibraryItem.status='On Loan') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere2) ORDER BY name, producer" ; 
		$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
		
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Number") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "ID") ;
				print "</th>" ;
				print "<th style='width: 250px'>" ;
					print __($guid, "Name") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Producer') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Type") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Location") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Status") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Return Date') . "<br/>" . __($guid, 'Borrower') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			try {
				$resultPage=$connection2->prepare($sqlPage);
				$resultPage->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($row=$resultPage->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				if ((strtotime(date("Y-m-d"))-strtotime($row["returnExpected"]))/(60*60*24)>0 AND $row["status"]=="On Loan") {
					$rowNum="error" ;
				}
				
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
						print "<td>" ;
						print ($count+1) ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["id"] . "</b>" ;
					print "</td>" ;
					print "<td>" ;
						if (strlen($row["name"])>30) {
							print "<b>" . substr($row["name"],0, 30) . "...</b><br/>" ;
						}
						else {
							print "<b>" . $row["name"] . "</b><br/>" ;
						}
						print "<span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
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
							print __($guid, $rowType["name"]) . "<br/>" ;
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
								print $rowSpace["name"] . "<br/>" ;
							}
						}
						if ($row["locationDetail"]!="") {
							print "<span style='font-size: 85%; font-style: italic'>" . $row["locationDetail"] . "</span>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print $row["status"] . "<br/>" ;
						if ($row["returnExpected"]!="" OR $row["borrower"]!="") {
							print "<span style='font-size: 85%; font-style: italic'>" ;
								if ($row["returnExpected"]!="") {
							 		print dateConvertBack($guid, $row["returnExpected"]) . "<br/>" ;
							 	}
							 	if ($row["borrower"]!="") {
							 		print $row["borrower"] ;
							 	}
							print "</span>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item.php&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status") ;
		}
	}
}
?>