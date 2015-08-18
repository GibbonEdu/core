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


if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_duplicate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
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
				print _("You have not specified one or more required parameters.") ;
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
				$otherYearDuplicateSuccess=FALSE ;
				//Deal with duplicate to other year
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage="" ;
				$class="success" ;
				if (!($updateReturn=="")) {
					if ($updateReturn=="success0") {
						$updateReturnMessage=_("Your request was completed successfully, but the target class is in another year, so you cannot see the results here.") ;	
						$otherYearDuplicateSuccess=TRUE ;
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				}
				if ($otherYearDuplicateSuccess!=TRUE) {
					print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
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
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>" . _('Planner') . " $extra</a> > </div><div class='trailEnd'>" . _('Duplicate Lesson Plan') . "</div>" ;
				print "</div>" ;
				
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage="" ;
				$class="error" ;
				if (!($updateReturn=="")) {
					if ($updateReturn=="fail0") {
						$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
					}
					else if ($updateReturn=="fail1") {
						$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="fail2") {
						$updateReturnMessage=_("Your request failed due to a database error.") ;	
					}
					else if ($updateReturn=="fail3") {
						$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="fail4") {
						$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="fail5") {
						$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
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
					<?php print _('This process will duplicate all aspects of the selected lesson, with the exception of Smart Blocks content, which belongs to the unit, not the lesson.') ?>
					</p>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicate.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&step=2" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Target Year') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" style="width: 302px">
										<?php
										print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
										try {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlSelect="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY sequenceNumber" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { print $e->getMessage() ;}
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["gibbonSchoolYearID"]==$_SESSION[$guid]["gibbonSchoolYearID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}		
										?>				
									</select>
									<script type="text/javascript">
										var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
										gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
									 </script>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Target Class') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
										<?php
										print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
										try {
											if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY gibbonSchoolYear.gibbonSchoolYearID, course, class" ;
											}
											else {	
												$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
											}
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { print $e->getMessage() ; }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["gibbonCourseClassID"]==$row["gibbonCourseClassID"]) {
												$selected="selected" ;
											}
											print "<option $selected class='" . $rowSelect["gibbonSchoolYearID"] . "' value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
										}		
										?>				
									</select>
									<script type="text/javascript">
										var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
										gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
									</script> 
									<script type="text/javascript">
										$("#gibbonCourseClassID").chainedTo("#gibbonSchoolYearID");
									</script>
								</td>
							</tr>
							<?php
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
										<b><?php print _('Duplicate Markbook Columns?') ?></b><br/>
										<span style="font-size: 90%"><i><?php print _('Will duplicate any columns linked to this lesson.') ?><br/></i></span>
									</td>
									<td class="right">
										<select name="duplicate" id="duplicate" style="width: 302px">
											<option value='N'>N</option>	
											<option value='Y'>Y</option>	
										</select>
									</td>
								</tr>
								<?php
							}
							?>
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input name="viewBy" id="viewBy" value="<?php print $viewBy ?>" type="hidden">
									<input name="gibbonPlannerEntryID_org" id="gibbonPlannerEntryID_org" value="<?php print $gibbonPlannerEntryID ?>" type="hidden">
									<input name="subView" id="subView" value="<?php print $subView ?>" type="hidden">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<?php print _('Next') ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
				else if ($step==2) {
					$gibbonPlannerEntryID_org=$_POST["gibbonPlannerEntryID_org"] ;
					$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
					$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
					$duplicate=NULL ;
					if (isset($_POST["duplicate"])) {
						$duplicate=$_POST["duplicate"] ;
					}
					if ($gibbonCourseClassID=="" OR $gibbonSchoolYearID=="") {
						print "<div class='error'>" ;
							print _("You have not specified one or more required parameters.") ;
						print "</div>" ;
					}
					else {
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicateProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID" ?>">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr>
									<td style='width: 275px'> 
										<b><?php print _('Class') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<?php
										try {
											if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
												$dataSelect=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonSchoolYearID"=>$gibbonSchoolYearID); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
											}
											else {	
												$dataSelect=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
											}
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { print $e->getMEssage() ; }
										if ($resultSelect->rowCount()==1) {
											$rowSelect=$resultSelect->fetch()
											?>
											<input readonly name="class" id="class" maxlength=50 value="<?php print htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) ?>" type="text" style="width: 300px">
											<?php
										}		
										?>		
									</td>
								</tr>
								
								<?php
								if ($row["gibbonUnitID"]!="" AND $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
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
												<b><?php print _('Keep lesson in original unit?') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Only available if source and target classes are in the same course.') ?><br/></i></span>
											</td>
											<td class="right">
												<select name="keepUnit" id="keepUnit" style="width: 302px">
													<option value='Y'><?php print _('Yes') ?></option>	
													<option value='N'><?php print _('No') ?></option>
												</select>
											</td>
										</tr>
										<?php
									}
								}
								?>
								
								<tr>
									<td> 
										<b><?php print _('Name') ?> *</b><br/>
									</td>
									<td class="right">
										<input name="name" id="name" maxlength=20 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var name2=new LiveValidation('name');
											name2.add(Validate.Presence);
										 </script>
									</td>
								</tr>
								
								<?php
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
										<b><?php print _('Date') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
									</td>
									<td class="right">
										<input name="date" id="date" maxlength=10 value="<?php print dateConvertBack($guid, $nextDate) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var date=new LiveValidation('date');
											date.add(Validate.Presence);
											date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
										<b><?php print _('Start Time') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
									</td>
									<td class="right">
										<input name="timeStart" id="timeStart" maxlength=5 value="<?php print substr($nextTimeStart,0,5) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var timeStart=new LiveValidation('timeStart');
											timeStart.add(Validate.Presence);
											timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
										 </script>
										<script type="text/javascript">
											$(function() {
												var availableTags=[
													<?php
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
										<b><?php print _('End Time') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
									</td>
									<td class="right">
										<input name="timeEnd" id="timeEnd" maxlength=5 value="<?php print substr($nextTimeEnd,0,5) ?>" type="text" style="width: 300px">
										<script type="text/javascript">
											var timeEnd=new LiveValidation('timeEnd');
											timeEnd.add(Validate.Presence);
											timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
										 </script>
										<script type="text/javascript">
											$(function() {
												var availableTags=[
													<?php
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
										<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
									</td>
									<td class="right">
										<input name="duplicate" id="duplicate" value="<?php print $duplicate ?>" type="hidden">
										<input name="gibbonPlannerEntryID_org" id="gibbonPlannerEntryID_org" value="<?php print $gibbonPlannerEntryID_org ?>" type="hidden">
										<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<?php print $gibbonCourseClassID ?>" type="hidden">
										<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
										<input name="viewBy" id="viewBy" value="<?php print $viewBy ?>" type="hidden">
										<input name="subView" id="subView" value="<?php print $subView ?>" type="hidden">
										<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<?php print _("Submit") ; ?>">
									</td>
								</tr>
							</table>
						</form>
						<?php
					}
				}
			}
		}
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
	}
}
?>