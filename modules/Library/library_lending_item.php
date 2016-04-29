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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending.php'>" . __($guid, 'Lending & Activity Log') . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Item') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, array("success0" => "Your request was completed successfully.")); }
		
	//Check if school year specified
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"];
	if ($gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
			$sql="SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			$overdue=(strtotime(date("Y-m-d"))-strtotime($row["returnExpected"]))/(60*60*24) ;
			if ($overdue>0 AND $row["status"]=="On Loan") {
				print "<div class='error'>" ;
				print sprintf(__($guid, 'This item is now %1$s%2$s days overdue'), "<u><b>", $overdue) . "</b></u>." ;
				print "</div>" ;
			}
			
			$name="" ;
			if (isset($_GET["name"])) {
				$name=$_GET["name"] ;
			}
			$gibbonLibraryTypeID="" ;
			if (isset($_GET["gibbonLibraryTypeID"])) {
				$gibbonLibraryTypeID=$_GET["gibbonLibraryTypeID"] ;
			}
			$gibbonSpaceID="" ;
			if (isset($_GET["gibbonSpaceID"])) {
				$gibbonSpaceID=$_GET["gibbonSpaceID"] ;
			}
			$status="" ;
			if (isset($_GET["status"])) {
				$status=$_GET["status"] ;
			}
			
			
			if ($name!="" OR $gibbonLibraryTypeID!="" OR $gibbonSpaceID!="" OR $status!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending.php&name=" . $name . "&gibbonLibraryTypeID=" . $gibbonLibraryTypeID . "&gibbonSpaceID=" . $gibbonSpaceID . "&status=" . $status . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			
			print "<h3>" ;
				print __($guid, "Item Details") ;
			print "</h3>" ;
			
			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Type') . "</span><br/>" ;
						print "<i>" . __($guid, $row["type"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'ID') . "</span><br/>" ;
						print "<i>" . $row["id"] . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Name') . "</span><br/>" ;
						print "<i>" . $row["name"] . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Author/Brand') . "</span><br/>" ;
						print "<i>" . $row["producer"] . "</i>" ;
					print "</td>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Status') . "</span><br/>" ;
						print "<i>" . $row["status"] . "</i>" ;
					print "</td>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Borrowable') . "</span><br/>" ;
						print "<i>" . $row["borrowable"] . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;

			
			print "<h3>" ;
				print __($guid, "Lending & Activity Log") ;
			print "</h3>" ;
			//Set pagination variable
			$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
			if ((!is_numeric($page)) OR $page<1) {
				$page=1 ;
			}
			try {
				$dataEvent=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
				$sqlEvent="SELECT * FROM gibbonLibraryItemEvent WHERE gibbonLibraryItemID=:gibbonLibraryItemID ORDER BY timestampOut DESC" ; 
				$sqlEventPage=$sqlEvent . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
				$resultEvent=$connection2->prepare($sqlEvent);
				$resultEvent->execute($dataEvent);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		
			print "<div class='linkTop'>" ;
				if ($row["status"]=="Available") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_signout.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $name . "&gibbonLibraryTypeID=" . $gibbonLibraryTypeID . "&gibbonSpaceID=" . $gibbonSpaceID . "&status=" . $status . "'>" . __($guid, 'Sign Out') . " <img  style='margin: 0 0 -4px 3px' title='" . __($guid, 'Sign Out') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_right.png'/></a>" ;
				}
				else {
					print "<i>" . __($guid, 'This item has already been signed out.') . "</i>" ;
				}
			print "</div>" ;
			
			if ($resultEvent->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				if ($resultEvent->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $resultEvent->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "") ;
				}
			
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th style='min-width: 90px'>" ;
							print __($guid, "User") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Status") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Date Out & In') . "</span><br/>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Due Date") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Return Action") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Recorded By") ;
						print "</th>" ;
						print "<th style='width: 110px'>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					try {
						$resultEventPage=$connection2->prepare($sqlEventPage);
						$resultEventPage->execute($dataEvent);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowEvent=$resultEventPage->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						$count++ ;
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							if ($rowEvent["gibbonPersonIDStatusResponsible"]!="") {
								try {
									$dataPerson=array("gibbonPersonID"=>$rowEvent["gibbonPersonIDStatusResponsible"]); 
									$sqlPerson="SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
									$resultPerson=$connection2->prepare($sqlPerson);
									$resultPerson->execute($dataPerson);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultPerson->rowCount()==1) {
									$rowPerson=$resultPerson->fetch() ;
								}
							}
							print "<td>" ;
								if (is_array($rowPerson)) {
									print getUserPhoto($guid, $rowPerson["image_240"], 75) ;
								}
								if (is_array($rowPerson)) {
									print "<div style='margin-top: 3px; font-weight: bold'>" . formatName($rowPerson["title"], $rowPerson["preferredName"], $rowPerson["surname"], "Staff", FALSE, TRUE) . "</div>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print $rowEvent["status"] . "<br/>" ;
								if ($rowEvent["timestampOut"]!="") {
									print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, substr($rowEvent["timestampOut"],0,10)) ;
									
									if ($rowEvent["timestampReturn"]!="") {
										print " - " . dateConvertBack($guid, substr($rowEvent["timestampReturn"],0,10)) ;
									}
									print "</span>" ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($rowEvent["status"]!="Returned" AND $rowEvent["returnExpected"]!="") {
									print dateConvertBack($guid, substr($rowEvent["returnExpected"],0,10)) . "<br/>" ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($rowEvent["status"]!="Returned" AND  $rowEvent["returnAction"]!="") {
									print $rowEvent["returnAction"] ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($rowEvent["gibbonPersonIDOut"]!="") {
									try {
										$dataPerson=array("gibbonPersonID"=>$rowEvent["gibbonPersonIDOut"]); 
										$sqlPerson="SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
										$resultPerson=$connection2->prepare($sqlPerson);
										$resultPerson->execute($dataPerson);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultPerson->rowCount()==1) {
										$rowPerson=$resultPerson->fetch() ;
									}
									print __($guid, "Out:") . " " . formatName($rowPerson["title"], $rowPerson["preferredName"], $rowPerson["surname"], "Staff", FALSE, TRUE) . "<br/>" ;
								}
								if ($rowEvent["gibbonPersonIDIn"]!="") {
									try {
										$dataPerson=array("gibbonPersonID"=>$rowEvent["gibbonPersonIDIn"]); 
										$sqlPerson="SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
										$resultPerson=$connection2->prepare($sqlPerson);
										$resultPerson->execute($dataPerson);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultPerson->rowCount()==1) {
										$rowPerson=$resultPerson->fetch() ;
									}
									print __($guid, "In:") . " " . formatName($rowPerson["title"], $rowPerson["preferredName"], $rowPerson["surname"], "Staff", FALSE, TRUE) ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($count==1 AND $rowEvent["status"]!="Returned") {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=" . $rowEvent["gibbonLibraryItemEventID"] . "&name=" . $name . "&gibbonLibraryTypeID=" . $gibbonLibraryTypeID . "&gibbonSpaceID=" . $gibbonSpaceID . "&status=" . $status . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_return.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=" . $rowEvent["gibbonLibraryItemEventID"] . "&name=" . $name . "&gibbonLibraryTypeID=" . $gibbonLibraryTypeID . "&gibbonSpaceID=" . $gibbonSpaceID . "&status=" . $status . "'><img title='Return' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_left.png'/></a>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_renew.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=" . $rowEvent["gibbonLibraryItemEventID"] . "&name=" . $name . "&gibbonLibraryTypeID=" . $gibbonLibraryTypeID . "&gibbonSpaceID=" . $gibbonSpaceID . "&status=" . $status . "'><img style='margin-left: 3px' title='Renew' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_right.png'/></a>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
				
				if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "") ;
				}
			}
			
			
			$_SESSION[$guid]["sidebarExtra"]="" ;
			$_SESSION[$guid]["sidebarExtra"].=getImage($guid, $row["imageType"], $row["imageLocation"]) ;
		}
	}
}
?>