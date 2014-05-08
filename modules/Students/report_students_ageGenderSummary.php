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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_ageGenderSummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Age & Gender Summary') . "</div>" ;
	print "</div>" ;
	
	//Work out ages in school
	try {
		$dataList=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlList="SELECT gibbonStudentEnrolment.gibbonYearGroupID, dob, gender FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY dob DESC" ;
		$resultList=$connection2->prepare($sqlList);
		$resultList->execute($dataList); 
	}
	catch(PDOException $e) {}
	
	$today=time() ;
	$ages=array() ;
	$everything=array() ;
	$count=0 ;
	while ($rowList=$resultList->fetch()) {
		if ($rowList["dob"]!="") {
			$age=floor(($today-strtotime($rowList["dob"]))/31556926);
			if (isset($ages[$age])==FALSE) {
				$ages[$age]=$age ;
			}
		} 
		$everything[$count][0]=$rowList["dob"] ;
		$everything[$count][1]=$rowList["gender"] ;
		$everything[$count][2]=$rowList["gibbonYearGroupID"] ;
		$count++ ;
	}
	
	$years=getYearGroups($connection2) ;
	
	if (count($ages)<1 OR count($years)<1) {
		print "<div class='error'>" ;
			print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
		
		print "<table class='mini' cellspacing='0' style='max-width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 100%' rowspan=2>" ;
					print _("Age") . "<br/>" ;
					print "<span style='font-size: 75%; font-style: italic'>" . _('As of today') . "</span>" ;
				print "</th>" ;
				for ($i=1; $i<count($years); $i=$i+2) {
					print "<th colspan=2 style='text-align: center'>" ;
						print $years[$i] ;
					print "</th>" ;
				}
				print "<th colspan=2 style='text-align: center'>" ;
					print _("All Years") ;
				print "</th>" ;
			print "</tr>" ;
			
			print "<tr class='head'>" ;
				for ($i=1; $i<count($years); $i=$i+2) {
					print "<th style='text-align: center; height: 70px; max-width:30px!important'>" ;
						print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" ;
							print _("Male") ;
						print "</div>" ;
					print "</th>" ;
					print "<th style='text-align: center; height: 70px; max-width:30px!important'>" ;
						print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" ;
							print _("Female") ;
						print "</div>" ;
					print "</th>" ;
				}
				print "<th style='text-align: center; height: 70px; max-width:30px!important'>" ;
					print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" ;
						print _("Male") ;
					print "</div>" ;
				print "</th>" ;
				print "<th style='text-align: center; height: 70px; max-width:30px!important'>" ;
					print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>" ;
						print _("Female") ;
					print "</div>" ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			foreach ($ages as $age) {				
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
						print "<b>$age</b>" ;
					print "</td>" ;
					for ($i=1; $i<count($years); $i=$i+2) {
						print "<td style='text-align: center'>" ;
							$cellCount=0 ;
							foreach ($everything as $thing) {
								if ($thing[2]==$years[$i-1] AND $thing[1]=="M" AND floor(($today-strtotime($thing[0]))/31556926)==$age) {
									$cellCount++ ;
								}
							}
							if ($cellCount!=0) {
								print $cellCount ;
							}
						print "</td>" ;
						print "<td style='text-align: center'>" ;
							$cellCount=0 ;
							foreach ($everything as $thing) {
								if ($thing[2]==$years[$i-1] AND $thing[1]=="F" AND floor(($today-strtotime($thing[0]))/31556926)==$age) {
									$cellCount++ ;
								}
							}
							if ($cellCount!=0) {
								print $cellCount ;
							}
						print "</td>" ;
					}
					print "<td style='text-align: center'>" ;
						$cellCount=0 ;
						foreach ($everything as $thing) {
							if ($thing[1]=="M" AND floor(($today-strtotime($thing[0]))/31556926)==$age) {
								$cellCount++ ;
							}
						}
						if ($cellCount!=0) {
							print $cellCount ;
						}
					print "</td>" ;
					print "<td style='text-align: center'>" ;
						$cellCount=0 ;
						foreach ($everything as $thing) {
							if ($thing[1]=="F" AND floor(($today-strtotime($thing[0]))/31556926)==$age) {
								$cellCount++ ;
							}
						}
						if ($cellCount!=0) {
							print $cellCount ;
						}
					print "</td>" ;
				print "</tr>" ;
			}
			print "<tr style='background-color: #FFD2A9'>" ;
				print "<td rowspan=2>" ;
					print "<b>" . _('All Ages') . "</b>" ;
				print "</td>" ;
				for ($i=1; $i<count($years); $i=$i+2) {
					print "<td style='text-align: center; font-weight: bold'>" ;
						$cellCount=0 ;
						foreach ($everything as $thing) {
							if ($thing[2]==$years[$i-1] AND $thing[1]=="M") {
								$cellCount++ ;
							}
						}
						if ($cellCount!=0) {
							print $cellCount ;
						}
					print "</td>" ;
					print "<td style='text-align: center; font-weight: bold'>" ;
						$cellCount=0 ;
						foreach ($everything as $thing) {
							if ($thing[2]==$years[$i-1] AND $thing[1]=="F") {
								$cellCount++ ;
							}
						}
						if ($cellCount!=0) {
							print $cellCount ;
						}
					print "</td>" ;
				}
				print "<td style='text-align: center; font-weight: bold'>" ;
					$cellCount=0 ;
					foreach ($everything as $thing) {
						if ($thing[1]=="M") {
							$cellCount++ ;
						}
					}
					if ($cellCount!=0) {
						print $cellCount ;
					}
				print "</td>" ;
				print "<td style='text-align: center; font-weight: bold'>" ;
					$cellCount=0 ;
					foreach ($everything as $thing) {
						if ($thing[1]=="F") {
							$cellCount++ ;
						}
					}
					if ($cellCount!=0) {
						print $cellCount ;
					}
				print "</td>" ;
			print "</tr>" ;
			print "<tr style='background-color: #FFD2A9'>" ;
				for ($i=1; $i<count($years); $i=$i+2) {
					print "<td colspan=2 style='text-align: center; font-weight: bold'>" ;
						$cellCount=0 ;
						foreach ($everything as $thing) {
							if ($thing[2]==$years[$i-1]) {
								$cellCount++ ;
							}
						}
						if ($cellCount!=0) {
							print $cellCount ;
						}
					print "</td>" ;
				}
				print "<td colspan=2 style='text-align: center; font-weight: bold'>" ;
					if (count($everything)!=0) {
						print count($everything) ;
					}
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	}
}
?>