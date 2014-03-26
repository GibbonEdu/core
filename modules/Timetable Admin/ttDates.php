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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttDates.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Tie Days to Dates</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
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
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	
	if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowcount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record does not exist.") ;
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
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttDates.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Previous Year') . "</a> " ;
			}
			else {
				print "Previous Year " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttDates.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Next Year') . "</a> " ;
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
				print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			while ($row=$result->fetch()) {
				print "<h3>" ;
					print $row["name"] ;
				print "</h3>" ;
				list($firstDayYear, $firstDayMonth, $firstDayDay)=explode('-', $row["firstDay"]);
				$firstDayStamp=mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
				list($lastDayYear, $lastDayMonth, $lastDayDay)=explode('-', $row["lastDay"]);
				$lastDayStamp=mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);
				
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
							list($specialDayYear, $specialDayMonth, $specialDayDay)=explode('-', $rowSpecial["date"]);
							$specialDayStamp=mktime(0, 0, 0, $specialDayMonth, $specialDayDay, $specialDayYear);
						}
						
						if ($i<$firstDayStamp OR $i>$lastDayStamp OR $days[date("D",$i)]=="N") {
							print "<td style='background-color: #bbbbbb'>" ;
							print "</td>" ;
								
							if ($i==$specialDayStamp) {
								$rowSpecial=$resultSpecial->fetch() ;
							}
							
						}
						else {
							if ($i==$specialDayStamp AND $rowSpecial["type"]=="School Closure") {
								print "<td style='vertical-align: top; text-align: center; background-color: #bbbbbb; font-size: 10px'>" ;
									print "<span style='color: #fff'>" . date("d/m/Y",$i) . "<br/>" . $rowSpecial["name"] . "</span>" ;
									print "<br/>" ;
									$rowSpecial=$resultSpecial->fetch() ;
								print "</td>" ;
							}
							else {
								print "<td style='vertical-align: top; text-align: center; background-color: #eeeeee; font-size: 10px'>" ;
									if ($i==$specialDayStamp AND $rowSpecial["type"]=="Timing Change") {
										 print "<span style='color: #000000'>" . date("d/m/Y",$i) . "<br/></span><span style='color: #f00'>Timing Change</span>" ;
									}
									else {
										print "<span style='color: #000000'>" . date("d/m/Y",$i) . "<br/>School Day</span>" ;
									}
									print "<br/>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=" . $i . "'><img style='margin-top: 3px' title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a><br/>" ;
									
									try {
										$dataDay=array("date"=>date("Y-m-d",$i)); 
										$sqlDay="SELECT gibbonTTDay.nameShort AS dayName, gibbonTT.nameShort AS ttName FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE date=:date" ;
										$resultDay=$connection2->prepare($sqlDay);
										$resultDay->execute($dataDay);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									while ($rowDay=$resultDay->fetch()) {
										print "<b>" . $rowDay["ttName"] . " " . $rowDay["dayName"] ."</b><br/>" ;
									}
								print "</td>" ;
							}
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