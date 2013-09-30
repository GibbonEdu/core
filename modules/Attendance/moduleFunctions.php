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

function report_studentHistory($guid, $gibbonPersonID, $print, $printURL, $connection2) {
	if ($print) {
		print "<div class='linkTop'>" ;
		print "<a target=_blank href='$printURL'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;
	}
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "firstDay"=>date("Y-m-d")); 
		$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND firstDay<=:firstDay" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($result->rowCount()<1) {
		$output=$output . "<div class='error'>" ;
			$output=$output . "There are no terms in the specied year." ;
		$output=$output . "</div>" ;
	}
	else {
		$countSchoolDays=0 ;
		$countAbsent=0 ;
		$countPresent=0 ;
		while ($row=$result->fetch()) {
			$output=$output . "<h4>" ;
				$output=$output . $row["name"] ;
			$output=$output . "</h4>" ;
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
				$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID AND type='School Closure' ORDER BY date" ;
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
			$days["count"]=7 ;
			try {
				$dataDays=array("username"=>$username); 
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
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Tue") {
					$days["Tue"]="N" ;
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Wed") {
					$days["Wed"]="N" ;
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Thu") {
					$days["Thu"]="N" ;
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Fri") {
					$days["Fri"]="N" ;
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Sat") {
					$days["Sat"]="N" ;
					$days["count"]-- ;
				}
				else if ($rowDays["nameShort"]=="Sun") {
					$days["Sun"]="N" ;
					$days["count"]-- ;
				}
			}
			
			$days["count"] ;
			$count=0;
			$weeks=2;
			
			$output=$output . "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manageProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID'>" ;
			$output=$output . "<table cellspacing='0' style='width: 100%'>" ;
			$output=$output . "<tr class='head'>" ;
				for ($w=0; $w<$weeks; $w++) {
					if ($days["Mon"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Mon" ;
						$output=$output . "</th>" ;
					}
					if ($days["Tue"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Tue" ;
						$output=$output . "</th>" ;
				
					}
					if ($days["Wed"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Wed" ;
						$output=$output . "</th>" ;
				
					}
					if ($days["Thu"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Thu" ;
						$output=$output . "</th>" ;
					}
					if ($days["Fri"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Fri" ;
						$output=$output . "</th>" ;
					}
					if ($days["Sat"]=="Y") {
						$output=$output . "<th style='width: 14px'>" ;
							$output=$output . "Sat" ;
						$output=$output . "</th>" ;
					}
					if ($days["Sun"]=="Y") {
						$output=$output . "<th style='width: 15px'>" ;
							$output=$output . "Sun" ;
						$output=$output . "</th>" ;
					}
				}
			$output=$output . "</tr>" ;
			
			//Make sure we are not showing future dates
			$now=mktime(0, 0, 0, date("m"), date("d"), date("Y"));
			$end=$endDayStamp ;
			if ($now<$endDayStamp) {
				$end=$now ;
			}
			//Display grid
			for ($i=$startDayStamp;$i<=$end;$i=$i+86400) {
				if (($count%($days["count"]*$weeks))==0 AND $days[date("D",$i)]=="Y") {
					$output=$output . "<tr style='height: 45px'>" ;
				}
				
				if ($rowSpecial==TRUE) {
					list($specialDayYear, $specialDayMonth, $specialDayDay) = explode('-', $rowSpecial["date"]);
					$specialDayStamp = mktime(0, 0, 0, $specialDayMonth, $specialDayDay, $specialDayYear);
				}
				
				if ($i<$firstDayStamp OR $i>$lastDayStamp) {
					$output=$output . "<td style='background-color: #bbbbbb'>" ;
					$output=$output . "</td>" ;
					$count++ ;
						
					if ($i==$specialDayStamp) {
						$rowSpecial=$resultSpecial->fetch() ;
					}
				}
				else {
					if ($i==$specialDayStamp) {
						$output=$output . "<td style='background-color: #bbbbbb'>" ;
						$output=$output . "</td>" ;
						$count++ ;
						$rowSpecial=$resultSpecial->fetch() ;
					}
					else {
						if ($days[date("D",$i)]=="Y") {
							$countSchoolDays++ ;
							
							$log=array() ;
							$logCount=0 ;
							try {
								$dataLog=array("date"=>date("Y-m-d", $i), "gibbonPersonID"=>$gibbonPersonID); 
								$sqlLog="SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC" ;
								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultLog->rowCount()<1) {
								$extraStyle="border: 1px solid #555; color: #555; background-color: #eee; " ;
							}
							else {
								while ($rowLog=$resultLog->fetch()) {
									$log[$logCount]=$rowLog["type"] ;
									$logCount++ ;
								}
							
								if ($log[0]=="Absent") {
									$countAbsent++ ;
									$extraStyle="border: 1px solid #c00; color: #c00; background-color: #F6CECB; " ;
								}
								else {
									$countPresent++ ;
									$extraStyle="border: 1px solid #390; color: #390; background-color: #D4F6DC; " ;
								}
							}
							$output=$output . "<td style='text-align: center; font-size: 10px; $extraStyle'>" ;
							$output=$output . date("d/m/Y",$i) . "<br/>" ;
							if (count($log)>0) {
								$output=$output . "<b>" . $log[0] . "</b><br>" ;
								for ($x=count($log); $x>=0; $x--) {
									if ($log[$x]=="Present") {
										$output=$output . "P" ;
									}
									else if ($log[$x]=="Present - Late") {
										$output=$output . "PL" ;
									}
									else if ($log[$x]=="Present - Offsite") {
										$output=$output . "PS" ;
									}
									else if ($log[$x]=="Left") {
										$output=$output . "L" ;
									}
									else if ($log[$x]=="Left - Early") {
										$output=$output . "LE" ;
									}
									else if ($log[$x]=="Absent") {
										$output=$output . "A" ;
									}
									if ($x!=0 AND $x!=count($log)) {
										$output=$output . " : " ;	
									}
								}
							}
							$output=$output . "</td>" ;
							$count++ ;
						}
					}
				}
				
				if (($count%($days["count"]*$weeks))==0 AND $days[date("D",$i)]=="Y") {
					$output=$output . "</tr>" ;
				}
			}
		
			$output=$output . "</table>" ;	
			$output=$output . "</form>" ;		
		}
	}
	
	print "<table cellspacing='0'>" ;
		print "<tr>" ;
			print "<td style='vertical-align: top'>" ;
				print "<h3>" ;
					print "Summary" ;
				print "</h2>" ;
				print "<p>" ;
					if ($countSchoolDays!=($countPresent+$countAbsent)) {
						print "<i>It appears that this student is missing attendance data for some school days:</i><br/>" ;
						print "<br/>" ;
					}
					print "<b>Total number of school days to date: $countSchoolDays</b><br/>" ;
					print "Total number of school days attended: $countPresent<br/>" ;
					print "Total number of school days absent: $countAbsent<br/>" ;
				print "</p>" ;
			print "</td>" ;
			print "<td style='width: 10px'>" ;
			print "</td>" ;
			print "<td style='vertical-align: top'>" ;
				print "<h3>" ;
					print "Key" ;
				print "</h2>" ;
				print "<p>" ;
					print "<img style='border: 1px solid #eee' alt='Data Key' src='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Attendance/img/dataKey.png'>" ;
				print "</p>" ;
			print "</td>" ;
		print "</tr>" ;
	print "</table>" ;
	
	print $output ;
}
?>
