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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/report_viewAvailableSpaces.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>View Available Spaces</div>" ;
	print "</div>" ;
	
	print "<p>" ;
	print "This report shows all available spaces in a given time, in a given timetable. Please note that whilst school closures are shown, timing changes are not. For cells which contain too many free spaces, hover your mouse over the cell to see all entries." ;
	print "</p>" ;		
			
	print "<h2 class='top'>" ;
	print "Choose Options" ;
	print "</h2>" ;
	
	$gibbonTTID=$_GET["gibbonTTID"] ;
	$spaceType=$_GET["spaceType"] ;
	$ttDate=$_GET["ttDate"] ;
	if ($ttDate=="") {
		$ttDate=date("d/m/Y") ;
	}

	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b>Timetable</b><br/>
					<span style="font-size: 90%"><i>Choose a timetable to view</span>
				</td>
				<td class="right">
					<select name="gibbonTTID" id="gibbonTTID" style="width: 302px">
						<option value='Please select...'>Please select...</option>
						<?
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonTT WHERE gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
					
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($gibbonTTID==$rowSelect["gibbonTTID"]) {
								$selected="selected" ;
							}	
							print "<option $selected value='" . $rowSelect["gibbonTTID"] . "'>" . $rowSelect["name"] . "</option>" ; 
						}
						?>
					</select>
					<script type="text/javascript">
						var gibbonTTID= new LiveValidation('gibbonTTID');
						gibbonTTID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>	
				</td>
			</tr>
			<tr>
				<td> 
					<b>Space Type</b><br/>
					<span style="font-size: 90%"><i>Restrict search to particular space types</span>
				</td>
				<td class="right">
					<select name="spaceType" id="spaceType" style="width: 302px">
						<option <? if ($spaceType=="") { print "selected" ; } ?> value=''>All</option>
						<option <? if ($spaceType=="Classroom") { print "selected" ; } ?> value='Classroom'>Classroom</option>
						<option <? if ($spaceType=="Performance") { print "selected" ; } ?> value='Performance'>Performance</option>
						<option <? if ($spaceType=="Hall") { print "selected" ; } ?> value='Hall'>Hall</option>
						<option <? if ($spaceType=="Outdoor") { print "selected" ; } ?> value='Outdoor'>Outdoor</option>
						<option <? if ($spaceType=="Undercover") { print "selected" ; } ?> value='Undercover'>Undercover</option>
						<option <? if ($spaceType=="Storage") { print "selected" ; } ?> value='Storage'>Storage</option>
						<option <? if ($spaceType=="Office") { print "selected" ; } ?> value='Office'>Office</option>
						<option <? if ($spaceType=="Staffroom") { print "selected" ; } ?> value='Staffroom'>Staffroom</option>
						<option <? if ($spaceType=="Study") { print "selected" ; } ?> value='Study'>Study</option>
						<option <? if ($spaceType=="Library") { print "selected" ; } ?> value='Library'>Library</option>
						<option <? if ($spaceType=="Other") { print "selected" ; } ?> value='Other'>Other</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Date</b><br/>
					<span style="font-size: 90%"><i>Choose a timetable to view</span>
				</td>
				<td class="right">
					<input name="ttDate" id="ttDate" maxlength=10 value="<? print $ttDate ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var ttDate = new LiveValidation('ttDate');
						ttDate.add(Validate.Presence);
						ttDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_viewAvailableSpaces.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonTTID!="") {
		print "<h2>" ;
		print "Results" ;
		print "<h2>" ;
		
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
			print "The specified timetable does not seem to exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$startDayStamp=strtotime(dateConvert($ttDate)) ;
						
			//Count back to first Monday before first day
			while (date("D",$startDayStamp)!="Mon") {
				$startDayStamp=$startDayStamp-86400 ;
			}
				
			//Check which days are school days
			$daysInWeek=0;
			$days=array() ;
			$timeStart="" ;
			$timeEnd="" ;
			$days["Mon"]="N" ;
			$days["Tue"]="N" ;
			$days["Wed"]="N" ;
			$days["Thu"]="N" ;
			$days["Fri"]="N" ;
			$days["Sat"]="N" ;
			$days["Sun"]="N" ;
			try {
				$dataDays=array(); 
				$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y'" ;
				$resultDays=$connection2->prepare($sqlDays);
				$resultDays->execute($dataDays);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowDays=$resultDays->fetch()) {
				//Max diff time for week based on days of week
				if ($timeStart=="") {
					$timeStart=$rowDays["schoolStart"] ;
				}
				if ($rowDays["schoolStart"]<$timeStart) {
					$timeStart=$rowDays["schoolStart"] ;
				}
				if ($timeEnd=="") {
					$timeEnd=$rowDays["schoolEnd"] ;
				}
				if ($rowDays["schoolEnd"]>$timeEnd) {
					$timeEnd=$rowDays["schoolEnd"] ;
				}
		
				//See which days are school days
				if ($rowDays["nameShort"]=="Mon") {
					$days["Mon"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Tue") {
					$days["Tue"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Wed") {
					$days["Wed"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Thu") {
					$days["Thu"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Fri") {
					$days["Fri"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Sat") {
					$days["Sat"]="Y" ;
					$daysInWeek++ ;
				}
				else if ($rowDays["nameShort"]=="Sun") {
					$days["Sun"]="Y" ;
					$daysInWeek++ ;
				}
			}
	
			//Count forward to the end of the week
			$endDayStamp=$startDayStamp+(86400*$daysInWeek) ;
	
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
			$width=(ceil(690/$daysInWeek)-12) . "px" ;
	
			$count=0;
	
			print "<table style='width: 760px; margin: 0px 0px 30px 0px;'>" ;
				print "<tr class='head'>" ;
					print "<th style='vertical-align: top; width: 70px; text-align: center'>" ;
						//Calculate week number
						$week=getWeekNumber ($startDayStamp, $connection2, $guid) ;
						if ($week!=false) {
							print "Week " . $week ."<br/>" ;
						}
						print "<span style='font-weight: normal; font-style: italic;'>Time<span>" ;
					print "</th>" ;
					if ($days["Mon"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Mo<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*0))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Tue"]=="Y") {	
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Tu<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*1))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Wed"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "We<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*2))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Thu"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Th<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*3))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Fri"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Fr<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*4))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Sat"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Sa<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*5))) . "</span><br/>" ;
						print "</th>" ;
					}
					if ($days["Sun"]=="Y") {
						print "<th style='vertical-align: top; text-align: center; width: " . (550/$daysInWeek) . "px'>" ;
							print "Su<br/>" ;
							print "<span style='font-size: 80%; font-style: italic'>". date("d/m", ($startDayStamp+(86400*6))) . "</span><br/>" ;
						print "</th>" ;
					}
				print "</tr>" ;
		
				print "<tr style='height:" . (ceil($diffTime/60)+14) . "px'>" ;
					print "<td style='height: 300px; width: 75px; text-align: center; vertical-align: top'>" ;
						print "<div style='position: relative; width: 71px'>" ;
							$countTime=0 ;
							$time=$timeStart ;
							print "<div $title style='z-index: $zCount; position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
								print substr($time,0,5) . "<br/>" ;
							print "</div>" ;
							$time = date("H:i:s", strtotime($time)+3600) ;
							$spinControl=0 ;
							while ($time<=$timeEnd AND $spinControl<(23-date("H",$timeStart))) {
								$countTime++ ;
								print "<div $title style='z-index: $zCount; position: absolute; top:" . (($countTime*60)-5) . "px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>" ;
									print substr($time,0,5) . "<br/>" ;
								print "</div>" ;
								$time = date("H:i:s", strtotime($time)+3600) ;
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
					$dayOfWeek="" ;
					for ($d=0; $d<7; $d++) {
						$day="" ;
						if ($d==0) { $dayOfWeek="Mon" ; }
						else if ($d==1) { $dayOfWeek="Tue" ; }
						else if ($d==2) { $dayOfWeek="Wed" ; }
						else if ($d==3) { $dayOfWeek="Thu" ; }
						else if ($d==4) { $dayOfWeek="Fri" ; }
						else if ($d==5) { $dayOfWeek="Sat" ; }
						else if ($d==6) { $dayOfWeek="Sun" ; }
				
				
						if ($days[$dayOfWeek]=="Y") {
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
								if (date("Y-m-d", ($startDayStamp+(86400*$count)))>=$rowTerm["firstDay"] AND date("Y-m-d", ($startDayStamp+(86400*$count)))<=$rowTerm["lastDay"]) {
									$isDayInTerm=TRUE ;
								}
							}
					
							if ($isDayInTerm==TRUE) {
								//Check for school closure day
								try {
									$dataClosure=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
									$sqlClosure="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date and type='School Closure'" ;
									$resultClosure=$connection2->prepare($sqlClosure);
									$resultClosure->execute($dataClosure);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClosure->rowCount()==1) {
									$rowClosure=$resultClosure->fetch() ;
									$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										$day=$day . "<div style='position: relative'>" ;
											$day=$day . "<div style='z-index: $zCount; position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>" ;
												$day=$day . "<div style='position: relative; top: 50%'>" ;
													$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>" . $rowClosure["name"] . "</span>" ;
												$day=$day . "</div>" ;
											$day=$day . "</div>" ;
										$day=$day . "</div>" ;
									$day=$day . "</td>" ;
								}
								else {
									$schoolCalendarAlpha=0.85 ;
									$ttAlpha=1.0 ;

									$date=date("Y/m/d", ($startDayStamp+(86400*$count))) ;


									$output="" ;
									$blank=TRUE ;
									//Get day start and end!
									$dayTimeStart="" ;
									$dayTimeEnd="" ;
									try {
										$dataDiff=array("date"=>date("Y-m-d", ($startDayStamp+(86400*$count))), "gibbonTTID"=>$gibbonTTID); 
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
									if ($specialDayStart!="") {
										$dayTimeStart=$specialDayStart ;
									}
									if ($specialDayEnd!="") {
										$dayTimeEnd=$specialDayEnd ;
									}

									$dayDiffTime=strtotime($dayTimeEnd)-strtotime($dayTimeStart) ;

									$startPad=strtotime($dayTimeStart)-strtotime($timeStart);

									$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
										try {
											$dataDay=array("gibbonTTID"=>$gibbonTTID, "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
											$sqlDay="SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date" ;
											$resultDay=$connection2->prepare($sqlDay);
											$resultDay->execute($dataDay);
										}
										catch(PDOException $e) { 
											$day=$day . "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
	
										if ($resultDay->rowCount()==1) {
											$rowDay=$resultDay->fetch() ;
											$zCount=0 ;
											$day=$day . "<div style='position: relative'>" ;
		
											//Draw outline of the day
											try {
												$dataPeriods=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "date"=>date("Y-m-d", ($startDayStamp+(86400*$count)))); 
												$sqlPeriods="SELECT gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd" ;
												$resultPeriods=$connection2->prepare($sqlPeriods);
												$resultPeriods->execute($dataPeriods);
											}
											catch(PDOException $e) { 
												$day=$day . "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
				
													$width=(ceil(690/$daysInWeek)-12) . "px" ;
													$height=ceil((strtotime($effectiveEnd)-strtotime($effectiveStart))/60) . "px" ;
													$top=ceil(((strtotime($effectiveStart)-strtotime($dayTimeStart))+$startPad)/60) . "px" ;
													$title="" ;
													if ($rowPeriods["type"]!="Lesson" AND $height>15 AND $height<30) {
														$title="title='" . substr($effectiveStart,0,5) . " - " . substr($effectiveEnd,0,5) . "'" ;
													}
													else if ($rowPeriods["type"]!="Lesson" AND $height<=15) {
														$title="title='" . $rowPeriods["name"] . " (" . substr($effectiveStart,0,5) . "-" . substr($effectiveEnd,0,5) . ")'" ;
													}
													$bg="rgba(238,238,238,$ttAlpha)" ;
													if ((date("H:i:s")>$effectiveStart) AND (date("H:i:s")<$effectiveEnd) AND $rowPeriods["date"]==date("Y-m-d")) {
														$bg="rgba(179,239,194,$ttAlpha)" ;
													}
													$style="" ;
													if ($rowPeriods["type"]=="Lesson") {
														$style="" ;
													}
													$day=$day . "<div $title style='color: rgba(0,0,0,$ttAlpha); z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px; background-color: $bg; color: rgba(136,136,136, $ttAlpha) $style'>" ;
													if ($height>15) {
														$day=$day . $rowPeriods["name"] . "<br>" ;
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
														while ($rowSelect=$resultSelect->fetch()) {
															try {
																$dataUnique=array("gibbonTTDayID"=>$rowDay["gibbonTTDayID"], "gibbonTTColumnRowID"=>$rowPeriods["gibbonTTColumnRowID"], "gibbonSpaceID"=>$rowSelect["gibbonSpaceID"]); 
																$sqlUnique="SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonSpaceID=:gibbonSpaceID" ;
																$resultUnique=$connection2->prepare($sqlUnique);
																$resultUnique->execute($dataUnique);
															}
															catch(PDOException $e) { }
															if ($resultUnique->rowCount()<1) {
																$vacancies.=$rowSelect["name"] . ", " ;
															}
														}
														$vacancies=substr($vacancies,0,-2) ;
														$day=$day . "<div title='" . htmlPrep($vacancies) . "' style='color: black; font-weight: normal'>" ;
															if (strlen($vacancies)<=50) {
																$day.=$vacancies ;
															}
															else {
																$day.=substr($vacancies,0,50) . "..." ;
															}
															
														$day=$day . "</div>" ;
													}
													$day=$day . "</div>" ;
													$zCount++ ;
												}
											}
										}
									$day=$day . "</td>" ;

								}
							}
							else {
								$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'>" ;
									$day=$day . "<div style='position: relative'>" ;
										$day=$day . "<div style='z-index: $zCount; position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: " . ceil($diffTime/60) . "px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>" ;
											$day=$day . "<div style='position: relative; top: 50%'>" ;
												$day=$day . "<span style='color: rgba(255,0,0,$ttAlpha);'>School Closed</span>" ;
											$day=$day . "</div>" ;
										$day=$day . "</div>" ;
									$day=$day . "</div>" ;
								$day=$day . "</td>" ;
							}
					
							if ($day=="") {
								$day=$day . "<td style='text-align: center; vertical-align: top; font-size: 11px'></td>" ;
							}
					
							print $day ;
							
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