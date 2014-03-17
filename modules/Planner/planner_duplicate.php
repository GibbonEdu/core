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


if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_duplicate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Set variables
		$today=date("Y-m-d");
			
		//Proceed!
		//Get viewBy, date and class variables
		$params="" ;
		$viewBy=NULL ;
		if (isset($_GET["viewBy"])) {
			$viewBy=$_GET["viewBy"] ;
		}
		$subView=NULL ;
		if (isset($_GET["subView"])) {
			$subView=$_GET["subView"] ;
		}
		if ($viewBy!="date" AND $viewBy!="class") {
			$viewBy="date" ;
		}
		$gibbonCourseClassID=NULL ;
		$date=NULL ;
		$dateStamp=NULL ;
		if ($viewBy=="date") {
			$date=$_GET["date"] ;
			if (isset($_GET["dateHuman"])) {
				$date=dateConvert($guid, $_GET["dateHuman"]) ;
			}
			if ($date=="") {
				$date=date("Y-m-d");
			}
			list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
			$dateStamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);	
			$params="&viewBy=date&date=$date" ;
		}
		else if ($viewBy=="class") {
			$class=NULL ;
			if (isset($_GET["class"])) {
				$class=$_GET["class"] ;
			}
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
			$params="&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
		}
		
		list($todayYear, $todayMonth, $todayDay)=explode('-', $today);
		$todayStamp=mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
		
		//Check if school year specified
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"];
		$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
		if ($gibbonPlannerEntryID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="Y")) {
			print "<div class='error'>" ;
				print "You have not specified one or more required parameters." ;
			print "</div>" ;
		}
		else {
			try {
				if ($viewBy=="date") {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("date"=>$date, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("date"=>$date, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
				}
				else {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print "The selected planner entry does not exist, or you do not have access to it." ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$row=$result->fetch() ;
				
				if ($viewBy=="date") {
					$extra=dateConvertBack($guid, $date) ;
				}
				else {
					$extra=$row["course"] . "." . $row["class"] ;
				}
				
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>Planner $extra</a> > </div><div class='trailEnd'>Duplicate Lesson Plan</div>" ;
				print "</div>" ;
				
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage="" ;
				$class="error" ;
				if (!($updateReturn=="")) {
					if ($updateReturn=="fail0") {
						$updateReturnMessage="Duplicate failed because you do not have access to this action." ;	
					}
					else if ($updateReturn=="fail1") {
						$updateReturnMessage="Duplicate failed because a required parameter was not set." ;	
					}
					else if ($updateReturn=="fail2") {
						$updateReturnMessage="Duplicate failed due to a database error." ;	
					}
					else if ($updateReturn=="fail3") {
						$updateReturnMessage="Duplicate failed because your inputs were invalid." ;	
					}
					else if ($updateReturn=="fail4") {
						$updateReturnMessage="Duplicate failed some values need to be unique but were not." ;	
					}
					else if ($updateReturn=="fail5") {
						$updateReturnMessage="Duplicate failed because your attachment could not be uploaded." ;	
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				}
				
				$step=NULL ;
				if (isset($_GET["step"])) {
					$step=$_GET["step"] ;
				}
				if ($step!=1 AND $step!=2) {
					$step=1 ;
				}
				
				if ($step==1) {
					?>
					<p>
					This process will duplicate all aspects of the selected lesson, with the exception of Smart Blocks content, which belongs to the unit, not the lesson. 
					</p>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicate.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&step=2" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									<b>Class *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
										<?
										print "<option value='Please select...'></option>" ;
										try {
											if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
											}
											else {	
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
											}
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["gibbonCourseClassID"]==$row["gibbonCourseClassID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
										}		
										?>				
									</select>
									<script type="text/javascript">
										var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
										gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
									 </script>
								</td>
							</tr>
							<?
							//DUPLICATE MARKBOOK COLUMN?
							try {
								$dataMarkbook=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
								$sqlMarkbook="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
								$resultMarkbook=$connection2->prepare($sqlMarkbook);
								$resultMarkbook->execute($dataMarkbook);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultMarkbook->rowCount()>=1) {
								?>
								<tr>
									<td> 
										<b>Duplicate Markbook Columns?</b><br/>
										<span style="font-size: 90%"><i>Will duplicate any columns linked to this lesson.<br/></i></span>
									</td>
									<td class="right">
										<select name="duplicate" id="duplicate" style="width: 302px">
											<option value='N'>N</option>	
											<option value='Y'>Y</option>	
										</select>
									</td>
								</tr>
								<?
							}
							?>
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input name="viewBy" id="viewBy" value="<? print $viewBy ?>" type="hidden">
									<input name="subView" id="subView" value="<? print $subView ?>" type="hidden">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="Next">
								</td>
							</tr>
						</table>
					</form>
					<?
				}
				else if ($step==2) {
					$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
					$duplicate=NULL ;
					if (isset($_POST["duplicate"])) {
						$duplicate=$_POST["duplicate"] ;
					}
					if ($gibbonCourseClassID=="") {
						print "<div class='error'>" ;
							print "You have not specified one or more required parameters." ;
						print "</div>" ;
					}
					else {
						?>
						<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicateProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID" ?>">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr>
									<td> 
										<b>Class *</b><br/>
										<span style="font-size: 90%"><i>This value cannot be changed<br/></i></span>
									</td>
									<td class="right">
										<?
										print "<option value='Please select...'></option>" ;
										try {
											if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
											}
											else {	
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
											}
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID) {
												?>
												<input readonly name="class" id="class" maxlength=50 value="<? print htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) ?>" type="text" style="width: 300px">
												<?
											}
										}		
										?>		
									</td>
								</tr>
								
								<?
								if ($row["gibbonUnitID"]!="") {
									//KEEP IN UNIT
									try {
										$dataMarkbook=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonUnitID"=>$row["gibbonUnitID"]); 
										$sqlMarkbook="SELECT * FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
										$resultMarkbook=$connection2->prepare($sqlMarkbook);
										$resultMarkbook->execute($dataMarkbook);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
							
									if ($resultMarkbook->rowCount()==1) {
										$rowMarkbook=$resultMarkbook->fetch() ;
										print "<input name=\"gibbonUnitClassID\" id=\"gibbonUnitClassID\" value=\"" . $rowMarkbook["gibbonUnitClassID"] . "\" type=\"hidden\">" ;
										?>
										<tr>
											<td> 
												<b>Keep lesson in original unit?</b><br/>
												<span style="font-size: 90%"><i>Only available if source and target classes are in the same course.<br/></i></span>
											</td>
											<td class="right">
												<select name="keepUnit" id="keepUnit" style="width: 302px">
													<option value='Y'>Y</option>	
													<option value='N'>N</option>
												</select>
											</td>
										</tr>
										<?
									}
								}
								?>
								
								<tr>
									<td> 
										<b>Name *</b><br/>
									</td>
									<td class="right">
										<input name="name" id="name" maxlength=20 value="<? print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var name=new LiveValidation('name');
											name.add(Validate.Presence);
										 </script>
									</td>
								</tr>
								
								<?
								//Try and find the next unplanned slot for this class.
								try {
									$dataNext=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>date("Y-m-d")); 
									$sqlNext="SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10" ;
									$resultNext=$connection2->prepare($sqlNext);
									$resultNext->execute($dataNext);
								}
								catch(PDOException $e) { }
								$nextDate="" ;
								$nextTimeStart="" ;
								$nextTimeEnd="" ;
								while ($rowNext=$resultNext->fetch()) {
									try {
										$dataPlanner=array("date"=>$rowNext["date"], "timeStart"=>$rowNext["timeStart"], "timeEnd"=>$rowNext["timeEnd"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
										$sqlPlanner="SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultPlanner=$connection2->prepare($sqlPlanner);
										$resultPlanner->execute($dataPlanner);
									}
									catch(PDOException $e) { }
									if ($resultPlanner->rowCount()==0) {
										$nextDate=$rowNext["date"] ;
										$nextTimeStart=$rowNext["timeStart"] ;
										$nextTimeEnd=$rowNext["timeEnd"] ;	
										break ;
									}
								}
								?>
								<tr>
									<td> 
										<b>Date *</b><br/>
										<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
									</td>
									<td class="right">
										<input name="date" id="date" maxlength=10 value="<? print dateConvertBack($guid, $nextDate) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var date=new LiveValidation('date');
											date.add(Validate.Presence);
											date.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
										 </script>
										 <script type="text/javascript">
											$(function() {
												$( "#date" ).datepicker();
											});
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b>Start Time *</b><br/>
										<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
									</td>
									<td class="right">
										<input name="timeStart" id="timeStart" maxlength=5 value="<? print substr($nextTimeStart,0,5) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var timeStart=new LiveValidation('timeStart');
											timeStart.add(Validate.Presence);
											timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
										 </script>
										<script type="text/javascript">
											$(function() {
												var availableTags=[
													<?
													try {
														$dataAuto=array(); 
														$sqlAuto="SELECT DISTINCT timeStart FROM gibbonPlannerEntry ORDER BY timeStart" ;
														$resultAuto=$connection2->prepare($sqlAuto);
														$resultAuto->execute($dataAuto);
													}
													catch(PDOException $e) { }
													while ($rowAuto=$resultAuto->fetch()) {
														print "\"" . substr($rowAuto["timeStart"],0,5) . "\", " ;
													}
													?>
												];
												$( "#timeStart" ).autocomplete({source: availableTags});
											});
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b>End Time *</b><br/>
										<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
									</td>
									<td class="right">
										<input name="timeEnd" id="timeEnd" maxlength=5 value="<? print substr($nextTimeEnd,0,5) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var timeEnd=new LiveValidation('timeEnd');
											timeEnd.add(Validate.Presence);
											timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
										 </script>
										<script type="text/javascript">
											$(function() {
												var availableTags=[
													<?
													try {
														$dataAuto=array(); 
														$sqlAuto="SELECT DISTINCT timeEnd FROM gibbonPlannerEntry ORDER BY timeEnd" ;
														$resultAuto=$connection2->prepare($sqlAuto);
														$resultAuto->execute($dataAuto);
													}
													catch(PDOException $e) { }
													while ($rowAuto=$resultAuto->fetch()) {
														print "\"" . substr($rowAuto["timeEnd"],0,5) . "\", " ;
													}
													?>
												];
												$( "#timeEnd" ).autocomplete({source: availableTags});
											});
										</script>
									</td>
								</tr>
								<tr>
									<td>
										<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
									</td>
									<td class="right">
										<input name="duplicate" id="duplicate" value="<? print $duplicate ?>" type="hidden">
										<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<? print $gibbonCourseClassID ?>" type="hidden">
										<input name="viewBy" id="viewBy" value="<? print $viewBy ?>" type="hidden">
										<input name="subView" id="subView" value="<? print $subView ?>" type="hidden">
										<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<? print _("Submit") ; ?>">
									</td>
								</tr>
							</table>
						</form>
						<?
					}
				}
			}
		}
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
	}
}
?>