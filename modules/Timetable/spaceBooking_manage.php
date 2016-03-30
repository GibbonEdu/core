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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceBooking_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Facility Bookings') . "</div>" ;
		print "</div>" ;
		
		if ($highestAction=="Manage Facility Bookings_allBookings") {
			print "<p>" . __($guid, "This page allows you to create facility and library bookings, whilst managing bookings created by all users. Only current and future bookings are shown: past bookings are hidden.") . "</p>" ;
		}
		else {
			print "<p>" . __($guid, "This page allows you to create and manage facility and library bookings. Only current and future changes are shown: past bookings are hidden.") . "</p>" ;
		}
	
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
	
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
	
		try {
			if ($highestAction=="Manage Facility Bookings_allBookings") {
				$data=array("date"=>date("Y-m-d")); 
				$sql="(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND date>=:date) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND date>=:date) ORDER BY date, name" ; 
			}
			else {
				$data=array("date"=>date("Y-m-d"), "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND date>=:date AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND date>=:date AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID) ORDER BY date, name" ; 
			}
			$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_add.php'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
		print "</div>" ;
	
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
			}
	
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Date") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Facility") ;
					print "</th>" ;
					if ($highestAction=="Manage Facility Bookings_allBookings") {
						print "<th>" ;
							print __($guid, "Person") ;
						print "</th>" ;
					}
					print "<th>" ;
						print __($guid, "Time") ;
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
					$count++ ;
				
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print dateConvertBack($guid, $row["date"]) ;
						print "</td>" ;
						print "<td>" ;
							print $row["name"] ;
						print "</td>" ;
						if ($highestAction=="Manage Facility Bookings_allBookings") {
							print "<td>" ;
								print formatName("", $row["preferredName"], $row["surname"], "Student", false) ;
							print "</td>" ;
						}
						print "<td>" ;
							print substr($row["timeStart"],0,5) . " - " . substr($row["timeEnd"],0,5) ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_delete.php&gibbonTTSpaceBookingID=" . $row["gibbonTTSpaceBookingID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
			}
		}
	}
}
?>