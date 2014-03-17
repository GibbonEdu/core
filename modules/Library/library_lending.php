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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Lending & Activity Log</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	print "<h3>" ;
		print "Search & Filter" ;
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
					<b>ID/Name/Producer</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					print "<input type='text' name='name' id='name' value='" . htmlPrep($name) . "' style='width:300px;'/>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Type</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
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
							print "<option $selected value='" . $rowType["gibbonLibraryTypeID"] . "'>" . $rowType["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Location</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
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
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					print "<select name='status' id='status' style='width:302px'>" ;
						print "<option value=''></option>" ;
						print "<option " ; if ($status=="Available") { print "selected " ; } print "value='Available'>Available</option>" ;
						print "<option " ; if ($status=="On Loan") { print "selected " ; } print "value='On Loan'>On Loan</option>" ;
						print "<option " ; if ($status=="Repair") { print "selected " ; } print "value='Repair'>Repair</option>" ;
						print "<option " ; if ($status=="Reserved") { print "selected " ; } print "value='Reserved'>Reserved</option>" ;
					print "</select>" ;
					?>
				</td>
			</tr>
			<?
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending.php'>Clear Filters</a> " ;
					print "<input type='submit' value='Go'>" ;
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
		print "View" ;
	print "</h3>" ;
	
	//Search with filters applied
	try {
		$data=array(); 
		$sqlWhere="AND " ;
		if ($name!="") {
			$data["name"]="%" . $name . "%" ;
			$data["producer"]="%" . $name . "%" ;
			$data["id"]="%" . $name . "%" ;
			$sqlWhere.="(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND " ; 
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
		if ($sqlWhere=="AND ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		
		
		$sql="SELECT * FROM gibbonLibraryItem WHERE (status='Available' OR status='On Loan' OR status='Repair' OR status='Reserved') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere ORDER BY id" ; 
		$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
		
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status") ;
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
					print "Type" ;
				print "</th>" ;
				print "<th>" ;
					print "Location" ;
				print "</th>" ;
				print "<th>" ;
					print "Status<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>Return Date</span>" ;
				print "</th>" ;
				print "<th>" ;
					print "Actions" ;
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
						print "<b>" . $row["id"] . "</b>" ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["name"] . "</b><br/>" ;
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
							print $rowType["name"] . "<br/>" ;
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
						if ($row["returnExpected"]!="") {
							print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, $row["returnExpected"]) . "</span>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item.php&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
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