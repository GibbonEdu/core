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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/report_viewAvailableSpaces.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Available Facilities') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print __($guid, "Choose Options") ;
	print "</h2>" ;
	
	$gibbonTTID=NULL ;
	if (isset($_GET["gibbonTTID"])) {
		$gibbonTTID=$_GET["gibbonTTID"] ;
	}
	$spaceType=NULL ;
	if (isset($_GET["spaceType"])) {
		$spaceType=$_GET["spaceType"] ;
	}
	
	$ttDate=NULL ;
	if (isset($_GET["ttDate"])) {
		$ttDate=$_GET["ttDate"] ;
	}
	if ($ttDate=="") {
		$ttDate=date($_SESSION[$guid]["i18n"]["dateFormatPHP"]) ;
	}

	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Timetable') ?></b><br/>
				</td>
				<td class="right">
					<select name="gibbonTTID" id="gibbonTTID" style="width: 302px">
						<option value='Please select...'><?php print __($guid, 'Please select...') ?></option>
						<?php
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonTT WHERE gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " ORDER BY name" ;
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
							print "<option $selected value='" . $rowSelect["gibbonTTID"] . "'>" . $rowSelect["name"] . "</option>" ; 
						}
						?>
					</select>
					<script type="text/javascript">
						var gibbonTTID=new LiveValidation('gibbonTTID');
						gibbonTTID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>	
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Facility Type') ?></b><br/>
				</td>
				<td class="right">
					<select name="spaceType" id="spaceType" style="width: 302px">
						<option <?php if ($spaceType=="") { print "selected" ; } ?> value=''><?php print __($guid, 'All') ?></option>
						<option <?php if ($spaceType=="Classroom") { print "selected" ; } ?> value='Classroom'><?php print __($guid, 'Classroom') ?></option>
						<option <?php if ($spaceType=="Performance") { print "selected" ; } ?> value='Performance'><?php print __($guid, 'Performance') ?></option>
						<option <?php if ($spaceType=="Hall") { print "selected" ; } ?> value='Hall'><?php print __($guid, 'Hall') ?></option>
						<option <?php if ($spaceType=="Outdoor") { print "selected" ; } ?> value='Outdoor'><?php print __($guid, 'Outdoor') ?></option>
						<option <?php if ($spaceType=="Undercover") { print "selected" ; } ?> value='Undercover'><?php print __($guid, 'Undercover') ?></option>
						<option <?php if ($spaceType=="Storage") { print "selected" ; } ?> value='Storage'><?php print __($guid, 'Storage') ?></option>
						<option <?php if ($spaceType=="Office") { print "selected" ; } ?> value='Office'><?php print __($guid, 'Office') ?></option>
						<option <?php if ($spaceType=="Staffroom") { print "selected" ; } ?> value='Staffroom'><?php print __($guid, 'Staffroom') ?></option>
						<option <?php if ($spaceType=="Study") { print "selected" ; } ?> value='Study'><?php print __($guid, 'Study') ?></option>
						<option <?php if ($spaceType=="Library") { print "selected" ; } ?> value='Library'><?php print __($guid, 'Library') ?></option>
						<option <?php if ($spaceType=="Other") { print "selected" ; } ?> value='Other'><?php print __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Date') ?></b><br/>
				</td>
				<td class="right">
					<input name="ttDate" id="ttDate" maxlength=10 value="<?php print $ttDate ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var ttDate=new LiveValidation('ttDate');
						ttDate.add(Validate.Presence);
						ttDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#ttDate" ).datepicker();
						});
					</script>
				</td>
			</tr>
			
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_viewAvailableSpaces.php">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonTTID!="") {
		print "<h2>" ;
		print __($guid, "Report Data") ;
		print "</h2>" ;
		print "<p>" ;
		print __($guid, "This report does not take facility bookings into account: please confirm that an available facility has not been booked by looking at View Timetable by Facility.") ;
		print "</p>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
			print __($guid, "The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$startDayStamp=strtotime(dateConvert($guid, $ttDate)) ;
						
			//Check which days are school days
			$daysInWeek=0;
			$days=array() ;
			$timeStart="" ;
			$timeEnd="" ;
			try {
				$dataDays=array();
				$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber" ;
				$resultDays=$connection2->prepare($sqlDays);
				$resultDays->execute($dataDays);
			}
			catch(PDOException $e) {
				print "<div class='error'>" . $e->getMessage() . "</div>" ;
			}
			$days=$resultDays->fetchAll() ;
			$daysInWeek=$resultDays->rowCount() ;
			foreach ($days AS $day) {
				if ($timeStart=="" OR $timeEnd=="") {
					$timeStart=$day["schoolStart"] ;
					$timeEnd=$day["schoolEnd"] ;
				}
				else {
					if ($day["schoolStart"]<$timeStart) {
						$timeStart=$day["schoolStart"] ;
					}
					if ($day["schoolEnd"]>$timeEnd) {
						$timeEnd=$day["schoolEnd"] ;
					}
				}
			}
			
			//Count back to first dayOfWeek before specified calendar date
			while (date("D",$startDayStamp)!=$days[0]["nameShort"]) {
				$startDayStamp=$startDayStamp-86400 ;
			}
			
			//Count forward to the end of the week
			$endDayStamp=$startDayStamp+(86400*($daysInWeek-1)) ;
	
			$schoolCalendarAlpha=0.85 ;
			$ttAlpha=1.0 ;
	
			//Max diff time for week based on timetables
			try {
				$dataDiff=array("date1"=>date("Y-m-d", ($startDayStamp+(86400*0))), "date2"=>date("Y-m-d", ($endDayStamp+(86400*1))), "gibbonTTID"=>$row["gibbonTTID"]);
				$sqlDiff="SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID" ;
				$resultDiff=$connection2->prepare($sqlDiff);
				$resultDiff->execute($dataDiff);
			}
			catch(PDOException $e) {
				print "<div class='error'>" . $e->getMessage() . "</div>" ;
			}
			while ($rowDiff=$resultDiff->fetch()) {
				try {
					$dataDiffDay=array("gibbonTTColumnID"=>$rowDiff["gibbonTTColumnID"]);
					$sqlDiffDay="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart" ;
					$resultDiffDay=$connection2->prepare($sqlDiffDay);
					$resultDiffDay->execute($dataDiffDay);
				}
				catch(PDOException $e) {
					print "<div class='error'>" . $e->getMessage() . "</div>" ;
				}
				while ($rowDiffDay=$resultDiffDay->fetch()) {
					if ($rowDiffDay["timeStart"]<$timeStart) {
						$timeStart=$rowDiffDay["timeStart"] ;
					}
					if ($rowDiffDay["timeEnd"]>$timeEnd) {
						$timeEnd=$rowDiffDay["timeEnd"] ;
					}
				}
			}
	
			//Final calc
			$diffTime=strtotime($timeEnd)-strtotime($timeStart) ;
			$width=(ceil(690/$daysInWeek)-20) . "px" ;
	
			$count=0;
	
			print "<table class='mini' cellspacing='0' style='width: 760px; margin: 0px 0px 30px 0px;'>" ;
				print "<tr class='head'>" ;
					print "<th style='vertical-align: top; width: 70px; text-align: center'>" ;
						//Calculate week number
						$week=getWeekNumber ($startDayStamp, $connection2, $guid) ;
						if ($week!=false) {
							print __($guid, "Week") . " " . $week ."<br/>" ;
						}
						print "<span style='font-weight: normal; font-style: italic;'>" . __($guid, 'Time') . "<span>" ;
					print "</th>" ;
					foreach ($days AS $day) {
						$dateCorrection=($day["sequenceNumber"]-1) ;
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print __($guid, $day["nameShort"]) . "<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date($_SESSION[$guid]["i18n"]["dateFormatPHP"], ($startDayStamp+(86400*$dateCorrection))) . "</span><br/>" ;
						print "</th>" ;
					}
				print "</tr>" ;

		
				print "<tr style='height:" . (ceil($diffTime/60)+14) . "px'>" ;
					print "<td style='height: 300px; width: 75px; text-align: center; vertical-align: top'>" ;
						print "<div style='position: relative; width: 71px'>" ;
							$countTime=0 ;
							$time=$timeStart ;
							print "<div style='position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
								print substr($time,0,5) . "<br/>" ;
							print "</div>" ;
							$time=date("H:i:s", strtotime($time)+3600) ;
							$spinControl=0 ;
							while ($time<=$timeEnd AND $spinControl<@(23-date("H",$timeStart))) {
								$countTime++ ;
								print "<div style='position: absolute; top:" . (($countTime*60)-5) . "px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
									print substr($time,0,5) . "<br/>" ;
								print "</div>" ;
								$time=date("H:i:s", strtotime($time)+3600) ;
								$spinControl++ ;
							}
					
						print "</div>" ;
					print "</td>" ;
			
					//Check to see if week is at all in term time...if it is, then display the grid
					$isWeekInTerm=FALSE ;
					try {
						$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
						$resultTerm=$connection2->prepare($sqlTerm);
						$resultTerm->execute($dataTerm);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					$weekStart=date("Y-m-d", ($startDayStamp+(86400*0))) ;
					$weekEnd=date("Y-m-d", ($startDayStamp+(86400*6))) ;
					while ($rowTerm=$resultTerm->fetch()) {
						if ($weekStart<=$rowTerm["firstDay"] AND $weekEnd>=$rowTerm["firstDay"]) {
							$isWeekInTerm=TRUE ;
						}
						else if ($weekStart>=$rowTerm["firstDay"] AND $weekEnd<=$rowTerm["lastDay"]) {
							$isWeekInTerm=TRUE ;
						}
						else if ($weekStart<=$rowTerm["lastDay"] AND $weekEnd>=$rowTerm["lastDay"]) {
							$isWeekInTerm=TRUE ;
						}
					}
					if ($isWeekInTerm==TRUE) {
						$blank=FALSE ;
					}
			
					//Run through days of the week
					foreach ($days AS $day) {
						$dayOut="" ;
						if ($day["schoolDay"]=="Y") {
							$dateCorrection=($day["sequenceNumber"]-1) ;
					
							//Check to see if day is term time
							$isDayInTerm=FALSE ;
							try {
								$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlTerm="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID" ; 
								$resultTerm=$connection2->prepare($sqlTerm);
								$resultTerm->execute($dataTerm);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowTerm=$resultTerm->fetch()) {
								if (date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))>=$rowTerm["firstDay"] AND date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))<=$rowTerm["lastDay"]) {
									$isDayInTerm=TRUE ;
								}
							}
					
							if ($isDayInTerm==TRUE) {
								//Check for school closure day
								try {
									$dataClosure=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))); 
									$sqlClosure="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date and type='School Closure'" ;
									$resultClosure=$connection2->prepare($sqlClosure);
									$resultClosure->execute($dataClosure);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClosure->rowCount()==1) {
									$rowClosure=$resultClosure->fetch() ;
									$dayOut.="<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										$dayOut.="<div style='position: relative'>" ;
											$dayOut.="<div style='z-index: $zCount; position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>" ;
												$dayOut.="<div style='position: relative; top: 50%'>" ;
													$dayOut.="<span style='color: rgba(255,0,0,$ttAlpha);'>" . $rowClosure["name"] . "</span>" ;
												$dayOut.="</div>" ;
											$dayOut.="</div>" ;
										$dayOut.="</div>" ;
									$dayOut.="</td>" ;
								}
								else {
									$schoolCalendarAlpha=0.85 ;
									$ttAlpha=1.0 ;

									$date=date("Y/m/d", ($startDayStamp+(86400*$dateCorrection))) ;

									$output="" ;
									$blank=TRUE ;
									
									//Make array of space changes
									$spaceChanges=array() ;
									try {
										$dataSpaceChange=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))); 
										$sqlSpaceChange="SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date" ;
										$resultSpaceChange=$connection2->prepare($sqlSpaceChange);
										$resultSpaceChange->execute($dataSpaceChange);
									}
									catch(PDOException $e) { }
									while ($rowSpaceChange=$resultSpaceChange->fetch()) {
										$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][0]=$rowSpaceChange["space"] ;
										$spaceChanges[$rowSpaceChange["gibbonTTDayRowClassID"]][1]=$rowSpaceChange["phoneInternal"] ;
									}
									
									//Get day start and end!
									$dayTimeStart="" ;
									$dayTimeEnd="" ;
									try {
										$dataDiff=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$dateCorrection))), "gibbonTTID"=>$gibbonTTID); 
										$sqlDiff="SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID" ;
										$resultDiff=$connection2->prepare($sqlDiff);
										$resultDiff->execute($dataDiff);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									while ($rowDiff=$resultDiff->fetch()) {
										if ($dayTimeStart=="") {
											$dayTimeStart=$rowDiff["timeStart"] ;
										}
										if ($rowDiff["timeStart"]<$dayTimeStart) {
											$dayTimeStart=$rowDiff["timeStart"] ;
										}
										if ($dayTimeEnd=="") {
											$dayTimeEnd=$rowDiff["timeEnd"] ;
										}
										if ($rowDiff["timeEnd"]>$dayTimeEnd) {
											$dayTimeEnd=$rowDiff["timeEnd"] ;
										}
									}

									$dayDiffTime=strtotime($dayTimeEnd)-strtotime($dayTimeStart) ;

									$startPad=strtotime($dayTimeStart)-strtotime($timeStart);

									$dayOut.="<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										try {
											$dataDay=array("gibbonTTID"=>$gibbonTTID, "date"=>date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))); 
											$sqlDay="SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date" ;
											$resultDay=$connection2->prepare($sqlDay);
											$resultDay->execute($dataDay);
										}
										catch(PDOException $e) { 
											$dayOut.="<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
	
										if ($resultDay->rowCount()==1) {
											$rowDay=$resultDay->fetch() ;
											$zCount=0 ;
											$dayOut.="<div style='position: relative;'>" ;
		
											//Draw outline of the day
											try {
												$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "date"=>date("Y-m-d", ($startDayStamp+(86400*$dateCorrection)))); 
												$sqlPeriods="SELECT gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd" ;
												$resultPeriods=$connection2->prepare($sqlPeriods);
												$resultPeriods->execute($dataPeriods);
											}
											catch(PDOException $e) { 
												$dayOut.="<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											while ($rowPeriods=$resultPeriods->fetch()) {
												$isSlotInTime=FALSE ;
												if ($rowPeriods["timeStart"]<=$dayTimeStart AND $rowPeriods["timeEnd"]>$dayTimeStart) {
													$isSlotInTime=TRUE ;
												}
												else if ($rowPeriods["timeStart"]>=$dayTimeStart AND $rowPeriods["timeEnd"]<=$dayTimeEnd) {
													$isSlotInTime=TRUE ;
												}
												else if ($rowPeriods["timeStart"]<$dayTimeEnd AND $rowPeriods["timeEnd"]>=$dayTimeEnd) {
													$isSlotInTime=TRUE ;
												}
			
												if ($isSlotInTime==TRUE) {
													$effectiveStart=$rowPeriods["timeStart"] ;
													$effectiveEnd=$rowPeriods["timeEnd"] ;
													if ($dayTimeStart>$rowPeriods["timeStart"]) {
														$effectiveStart=$dayTimeStart ;
													}
													if ($dayTimeEnd<$rowPeriods["timeEnd"]) {
														$effectiveEnd=$dayTimeEnd ;
													}
				
													$width=(ceil(690/$daysInWeek)-20) . "px" ;
													$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
													$top=ceil(((strtotime($effectiveStart)-strtotime($dayTimeStart))+$startPad)/60) . "px" ;
													$bg="rgba(238,238,238,$ttAlpha)" ;
													if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND $rowPeriods["date"]==date("Y-m-d")) {
														$bg="rgba(179,239,194,$ttAlpha)" ;
													}
													$style="" ;
													if ($rowPeriods["type"]=="Lesson") {
														$style="" ;
													}
													$dayOut.="<div style='color: rgba(0,0,0,$ttAlpha); z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px; background-color: $bg; color: rgba(136,136,136, $ttAlpha) $style'>" ;
													if ($height>15) {
														$dayOut.=$rowPeriods["name"] . "<br/>" ;
													}
													if ($rowPeriods["type"]=="Lesson") {
														$vacancies="" ;
														try {
															if ($spaceType=="") {
																$dataSelect=array(); 
																$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
															}
															else {
																$dataSelect=array("type"=>$spaceType); 
																$sqlSelect="SELECT * FROM gibbonSpace WHERE type=:type ORDER BY name" ;
															}
															$resultSelect=$connection2->prepare($sqlSelect);
															$resultSelect->execute($dataSelect);
														}
														catch(PDOException $e) { }
														$removers=array() ;
														$adders=array() ;
														while ($rowSelect=$resultSelect->fetch()) {
															try {
																$dataUnique=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "gibbonTTColumnRowID"=>$rowPeriods["gibbonTTColumnRowID"], "gibbonSpaceID"=>$rowSelect["gibbonSpaceID"]); 
																$sqlUnique="SELECT gibbonTTDayRowClass.*, gibbonSpace.name AS roomName FROM gibbonTTDayRowClass JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayRowClass.gibbonSpaceID=:gibbonSpaceID" ;
																$resultUnique=$connection2->prepare($sqlUnique);
																$resultUnique->execute($dataUnique);
															}
															catch(PDOException $e) { }
															if ($resultUnique->rowCount()!=1) {
																$vacancies.=$rowSelect["name"] . ", " ;
															}
															else {
																//Check if space freed up here
																$rowUnique=$resultUnique->fetch() ;
																if (isset($spaceChanges[$rowUnique["gibbonTTDayRowClassID"]])) {
																	//Save newly used space
																	$removers[$spaceChanges[$rowUnique["gibbonTTDayRowClassID"]][0]]=$spaceChanges[$rowUnique["gibbonTTDayRowClassID"]][0] ;
																	
																	//Save newly freed space
																	$adders[$rowUnique["roomName"]]=$rowUnique["roomName"] ;
																}
															}
														}
														
														//Remove any cancelling moves
														foreach ($removers AS $remove) {
															if (isset($adders[$remove])) {
																$adders[$remove]=NULL ;
																$removers[$remove]=NULL ; 
															}
														}
														foreach ($adders AS $adds) {
															if ($adds!="") {
																$vacancies.=$adds . ", " ;
															}
														}
														foreach ($removers AS $remove) {
															if ($remove!="") {
																$vacancies=str_replace($remove . ", ", "", $vacancies) ;
															}
														}
														
														//Explode vacancies into array and sort, get ready to output
														$vacancies=explode(",", substr($vacancies,0,-2)) ;
														natcasesort($vacancies) ;
														$vacanciesOutput="" ;
														foreach ($vacancies AS $vacancy) {
															$vacanciesOutput.=$vacancy . ", " ;
														}
														$vacanciesOutput=substr($vacanciesOutput,0,-2) ;
														
														$dayOut.="<div title='" . htmlPrep($vacanciesOutput) . "' style='color: black; font-weight: normal; line-height: 0.9'>" ;
															if (strlen($vacanciesOutput)<=50) {
																$dayOut.=$vacanciesOutput ;
															}
															else {
																$dayOut.=substr($vacanciesOutput,0,50) . "..." ;
															}
															
														$dayOut.="</div>" ;
													}
													$dayOut.="</div>" ;
													$zCount++ ;
												}
											}
										}
									$dayOut.="</td>" ;

								}
							}
							else {
								$dayOut.="<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
									$dayOut.="<div style='position: relative'>" ;
										$dayOut.="<div style='position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>" ;
											$dayOut.="<div style='position: relative; top: 50%'>" ;
												$dayOut.="<span style='color: rgba(255,0,0,$ttAlpha);'>" . __($guid, 'School Closed') . "</span>" ;
											$dayOut.="</div>" ;
										$dayOut.="</div>" ;
									$dayOut.="</div>" ;
								$dayOut.="</td>" ;
							}
					
							if ($dayOut=="") {
								$dayOut.="<td style='text-align: center; vertical-align: top; font-size: 11px'></td>" ;
							}
					
							print $dayOut ;
							
							$count++ ;
						}
					}
			
			
				print "</tr>" ;
				print "<tr style='height: 1px'>" ;
					print "<td style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888'>" ;
					print "</td>" ;
					print "<td colspan=$daysInWeek style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888'>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;			
		}
	}
}
?>