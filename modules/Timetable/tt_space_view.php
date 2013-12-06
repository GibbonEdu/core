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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_space_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		$gibbonSpaceID=$_GET["gibbonSpaceID"] ;
		
		try {
			$data=array("gibbonSpaceID"=>$gibbonSpaceID); 
			$sql="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
			print "The specified room does not seem to exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_space.php'>View Timetable by Space</a> > </div><div class='trailEnd'>" . $row["name"] . "</div>" ;
			print "</div>" ;
			
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt_space.php&search=" . $_GET["search"] . "'>Back to Search Results</a>" ;
				print "</div>" ;
			}
		
			
			$ttDate=NULL ;
			if (isset($_POST["ttDate"])) {
				$ttDate=dateConvertToTimestamp(dateConvert($_POST["ttDate"]));
			}
			
			if (isset($_POST["fromTT"])) {
				if ($_POST["fromTT"]=="Y") {
					if ($_POST["schoolCalendar"]=="on" OR $_POST["schoolCalendar"]=="Y") {
						$_SESSION[$guid]["viewCalendarSchool"]="Y" ;
					}
					else {
						$_SESSION[$guid]["viewCalendarSchool"]="N" ;
					}
				
					if ($_POST["personalCalendar"]=="on" OR $_POST["personalCalendar"]=="Y") {
						$_SESSION[$guid]["viewCalendarPersonal"]="Y" ;
					}
					else {
						$_SESSION[$guid]["viewCalendarPersonal"]="N" ;
					}
				}
			}
			
			$tt=renderTTSpace($guid, $connection2, $gibbonSpaceID, NULL, FALSE, $ttDate, "/modules/Timetable/tt_space_view.php", "&gibbonSpaceID=$gibbonSpaceID") ;
			
			if ($tt!=FALSE) {
				print $tt ;
			}
			else {
				print "<div class='error'>" ;
					print "There is no timetable information in the current academic year for the date specified." ;
				print "</div>" ;
			}
		}
	}
}
?>