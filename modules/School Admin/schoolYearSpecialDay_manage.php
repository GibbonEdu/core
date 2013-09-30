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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearSpecialDay_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Special Days</div>" ;
	print "</div>" ;
	
	$addReturn = $_GET["addReturn"] ;
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Add failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Add failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Add was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	$deleteReturn = $_GET["deleteReturn"] ;
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Delete was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	if ($gibbonSchoolYearID=="") {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	if ($_GET["gibbonSchoolYearID"]!="") {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified year does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Previous Year</a> " ;
			}
			else {
				print "Previous Year " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Next Year</a> " ;
			}
			else {
				print "Next Year " ;
			}
		print "</div>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print "There are no terms in the specied year." ;
			print "</div>" ;
		}
		else {
			while ($row=$result->fetch()) {
				print "<h3>" ;
					print $row["name"] ;
				print "</h3>" ;
				list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $row["firstDay"]);
				$firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
				list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $row["lastDay"]);
				$lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);
				
				//Count back to first Monday before first day
				$startDayStamp=$firstDayStamp;
				while (date("D",$startDayStamp)!="Mon") {
					$startDayStamp=$startDayStamp-86400 ;
				}
				
				//Count forward to first Sunday after last day
				$endDayStamp=$lastDayStamp;
				while (date("D",$endDayStamp)!="Sun") {
					$endDayStamp=$endDayStamp+86400 ;
				}
				
				//Get the special days
				try {
					$dataSpecial=array("gibbonSchoolYearTermID"=>$row["gibbonSchoolYearTermID"]); 
					$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID ORDER BY date" ;
					$resultSpecial=$connection2->prepare($sqlSpecial);
					$resultSpecial->execute($dataSpecial);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultSpecial->rowCount()>0) {
					$rowSpecial=$resultSpecial->fetch() ;
				}
				
				//Check which days are school days
				$days=array() ;
				$days["Mon"]="Y" ;
				$days["Tue"]="Y" ;
				$days["Wed"]="Y" ;
				$days["Thu"]="Y" ;
				$days["Fri"]="Y" ;
				$days["Sat"]="Y" ;
				$days["Sun"]="Y" ;
				try {
					$dataDays=array(); 
					$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'" ;
					$resultDays=$connection2->prepare($sqlDays);
					$resultDays->execute($dataDays);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				while ($rowDays=$resultDays->fetch()) {
					if ($rowDays["nameShort"]=="Mon") {
						$days["Mon"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Tue") {
						$days["Tue"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Wed") {
						$days["Wed"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Thu") {
						$days["Thu"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Fri") {
						$days["Fri"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Sat") {
						$days["Sat"]="N" ;
					}
					else if ($rowDays["nameShort"]=="Sun") {
						$days["Sun"]="N" ;
					}
				}
				
				$count=1;
				
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manageProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID'>" ;
				print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th style='width: 14px'>" ;
						print "Monday" ;
					print "</th>" ;
					print "<th style='width: 14px'>" ;
						print "Tuesday" ;
					print "</th>" ;
					print "<th style='width: 14px'>" ;
						print "Wednesday" ;
					print "</th>" ;
					print "<th style='width: 14px'>" ;
						print "Thursday" ;
					print "</th>" ;
					print "<th style='width: 14px'>" ;
						print "Friday" ;
					print "</th>" ;
					print "<th style='width: 14px'>" ;
						print "Saturday" ;
					print "</th>" ;
					print "<th style='width: 15px'>" ;
						print "Sunday" ;
					print "</th>" ;
				print "</tr>" ;
				
				for ($i=$startDayStamp;$i<=$endDayStamp;$i=$i+86400) {
					if (date("D",$i)=="Mon") {
						print "<tr style='height: 60px'>" ;
					}
					
					if ($rowSpecial==TRUE) {
						list($specialDayYear, $specialDayMonth, $specialDayDay) = explode('-', $rowSpecial["date"]);
						$specialDayStamp = mktime(0, 0, 0, $specialDayMonth, $specialDayDay, $specialDayYear);
					}
					
					if ($i<$firstDayStamp OR $i>$lastDayStamp OR $days[date("D",$i)]=="N") {
						print "<td style='background-color: #bbbbbb'>" ;
						print "</td>" ;
							
						if ($i==$specialDayStamp) {
							$rowSpecial=$resultSpecial->fetch() ;
						}
						
					}
					else {
						print "<td style='text-align: center; background-color: #eeeeee; font-size: 10px'>" ;
							if ($i==$specialDayStamp) {
								print "<span style='color: #ff0000'>" . date("d/m/Y",$i) . "<br/>" . $rowSpecial["name"] . "</span>" ;
								print "<br/>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage_edit.php&gibbonSchoolYearSpecialDayID=" . $rowSpecial["gibbonSchoolYearSpecialDayID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img style='margin-top: 3px' title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage_delete.php&gibbonSchoolYearSpecialDayID=" . $rowSpecial["gibbonSchoolYearSpecialDayID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img style='margin-top: 3px' title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
								$rowSpecial=$resultSpecial->fetch() ;
							}
							else {
								print "<span style='color: #000000'>" . date("d/m/Y",$i) . "<br/>School Day</span>" ;
								print "<br/>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=" . $i . "&gibbonSchoolYearTermID=" . $row["gibbonSchoolYearTermID"] . "&firstDay=$firstDayStamp&lastDay=$lastDayStamp'><img style='margin-top: 3px' title='Add' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a> " ;
							}
						print "</td>" ;
					}
					
					if (date("D",$i)=="Sun") {
						print "</tr>" ;
					}
					$count++ ;
				}
			
				print "</table>" ;	
				print "</form>" ;		
			}
		}
	}
}
?>