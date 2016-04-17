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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt_master.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Master Timetable') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print __($guid, "Choose Timetable") ;
	print "</h2>" ;
	
	$gibbonTTID=NULL ;
	if (isset($_GET["gibbonTTID"])) {
		$gibbonTTID=$_GET["gibbonTTID"] ;
	}
	if ($gibbonTTID==NULL) { //If TT not set, get the first timetable in the current year, and display that
		try {
			$dataSelect=array(); 
			$sqlSelect="SELECT gibbonTTID FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' ORDER BY gibbonTT.name LIMIT 0, 1" ;
			$resultSelect=$connection2->prepare($sqlSelect);
			$resultSelect->execute($dataSelect);
		}
		catch(PDOException $e) { }
		if ($resultSelect->rowCount()==1) {
			$rowSelect=$resultSelect->fetch() ;
			$gibbonTTID=$rowSelect["gibbonTTID"] ;
		}
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Timetable') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonTTID">
						<?php
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT gibbonTTID, gibbonTT.name AS TT, gibbonSchoolYear.name AS year FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYear.sequenceNumber, gibbonTT.name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							if ($resultSelect->rowCount()==1) {
								$gibbonTTID=$rowSelect["gibbonTTID"] ;
							}
							$selected="" ;
							if ($gibbonTTID==$rowSelect["gibbonTTID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonTTID"] . "'>" . htmlPrep($rowSelect["TT"]) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/tt_master.php">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonTTID!="") {
		//CHECK FOR TT
		try {
			$data=array("gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT gibbonTTID, gibbonTT.name AS TT, gibbonSchoolYear.name AS year FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonSchoolYear.sequenceNumber, gibbonTT.name" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" ;
				print $e->getMessage();
			print "</div>" ;
		}
	
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			//GET TT DAYS
			try {
				$dataDays=array("gibbonTTID"=>$gibbonTTID); 
				$sqlDays="SELECT gibbonTTDay.name AS name, gibbonTTColumn.gibbonTTColumnID, gibbonTTDayID FROM gibbonTTDay JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonTTID" ;
				$resultDays=$connection2->prepare($sqlDays);
				$resultDays->execute($dataDays);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" ;
					print $e->getMessage();
				print "</div>" ;
			}
		
			if ($resultDays->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				//Output days
				while ($rowDays=$resultDays->fetch()) {
					print "<h2 style='margin-top: 40px'>" ;
						print __($guid, $rowDays["name"]) ;
					print "</h2>" ;
				
					//GET PERIODS/ROWS
					try {
						$dataPeriods=array("gibbonTTColumnID"=>$rowDays["gibbonTTColumnID"]); 
						$sqlPeriods="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart, name" ; 
						$resultPeriods=$connection2->prepare($sqlPeriods);
						$resultPeriods->execute($dataPeriods);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
							print $e->getMessage();
						print "</div>" ;
					}
	
					if ($resultPeriods->rowCount()<1) {
						print "<div class='error'>" ;
						print __($guid, "There are no records to display.") ;
						print "</div>" ;
					}
					else {
						//Output periods/rows
						while ($rowPeriods=$resultPeriods->fetch()) {
							print "<h5 style='margin-top: 25px'>" ;
								print __($guid, $rowPeriods["name"]) ;
							print "</h5>" ;
						
							//GET CLASSES
							try {
								$dataClasses=array("gibbonTTColumnRowID"=>$rowPeriods["gibbonTTColumnRowID"], "gibbonTTDayID"=>$rowDays["gibbonTTDayID"]); 
								$sqlClasses="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID, gibbonSpace.name AS space FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID ORDER BY course, class" ;
								$resultClasses=$connection2->prepare($sqlClasses);
								$resultClasses->execute($dataClasses);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
			
							if ($resultClasses->rowCount()<1) {
								print "<div class='error'>" ;
									print __($guid, "Their are no classes associated with this period on this day.") ;
								print "</div>" ;
							}
							else {
								//Let's go!
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th style='width: 34%'>" ;
											print __($guid, "Class") ;
										print "</th>" ;
										print "<th style='width: 33%'>" ;
											print __($guid, "Location") ;
										print "</th>" ;
										print "<th style='width: 33%'>" ;
											print __($guid, "Teachers") ;
										print "</th>" ;
									print "</tr>" ;
					
									$count=0;
									$rowNum="odd" ;
									while ($rowClasses=$resultClasses->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
						
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td style='padding-top: 3px; padding-bottom: 4px'>" ;
												print $rowClasses["course"] . "." . $rowClasses["class"] ;
											print "</td>" ;
											print "<td style='padding-top: 3px; padding-bottom: 4px'>" ;
												if ($rowClasses["space"]!="") { 
													print $rowClasses["space"] ;
												}
											print "</td>" ;
											print "<td style='padding-top: 3px; padding-bottom: 4px'>" ;
												//Get teachers (accounting for exemptions)
												try {
													$dataTeachers=array("gibbonCourseClassID"=>$rowClasses["gibbonCourseClassID"], "gibbonTTDayRowClassID"=>$rowClasses["gibbonTTDayRowClassID"]); 
													$sqlTeachers="SELECT DISTINCT surname, preferredName, gibbonTTDayRowClassException.gibbonPersonID AS exception FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonTTDayRowClass.gibbonTTDayRowClassID=:gibbonTTDayRowClassID ORDER BY surname, preferredName" ;
													$resultTeachers=$connection2->prepare($sqlTeachers);
													$resultTeachers->execute($dataTeachers);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowTeachers=$resultTeachers->fetch()) {
													if ($rowTeachers["exception"]==NULL) {
														print formatName("", $rowTeachers["preferredName"], $rowTeachers["surname"], "Staff", false, true) ;
														print "<br/>" ;
													}
												}
											print "</td>" ;
										print "</tr>" ;
						
										$count++ ;
									}
								print "</table>" ;
							}
						}
					}
				}
			}
		}		
	}
}
?>