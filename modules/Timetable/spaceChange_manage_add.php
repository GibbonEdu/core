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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceChange_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/spaceChange_manage.php'>" . __($guid, 'Manage Facility Changes') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Facility Change') . "</div>" ;
		print "</div>" ;
	
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
	
		$step=NULL ;
		if (isset($_GET["step"])) {
			$step=$_GET["step"] ;
		}
		if ($step!=1 AND $step!=2) {
			$step=1 ;
		}
	
		//Step 1
		if ($step==1) {
			print "<h2>" ;
				print __($guid, "Step 1 - Choose Class") ;
			print "</h2>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/spaceChange_manage_add.php&step=2" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php print __($guid, 'Class') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gibbonCourseClassID" id="gibbonCourseClassID" class="standardWidth">
								<option value='Please select...'><?php print __($guid, 'Please select...') ?></option>
								<?php
								try {
									if ($highestAction=="Manage Space Changes_allClasses") {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ; 
									}
									else {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ; 
									}
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . $rowSelect["course"] . "." . $rowSelect["class"] . "</option>" ; 
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
								gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
		<?php
		}
		else if ($step==2) {
			print "<h2>" ;
				print __($guid, "Step 2 - Choose Options") ;
			print "</h2>" ;
			print "<p>" ;
				print __($guid, "When choosing a facility, remember that they are not mutually exclusive: you can change two classes into one facility, change one class to join another class in their normal room, or assign no facility at all. The facilities listed below are not necessarily free at the requested time: please use the View Available Facilities report to check availability.") ;
			print "</p>" ;
			
			$gibbonCourseClassID=NULL ;
			if (isset($_POST["gibbonCourseClassID"])) {
				$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
			}
			
			try {
				if ($highestAction=="Manage Space Changes_allClasses") {
					$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ; 
				}
				else {
					$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ; 
				}
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" ;
					print __($guid, "Your request failed due to a database error.") ;
				print "</div>" ;
			}
			
			if ($resultSelect->rowCount()!=1) {
				print "<div class='error'>" ;
					print __($guid, "Your request failed due to a database error.") ;
				print "</div>" ;
			}
			else {
				$rowSelect=$resultSelect->fetch() ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/spaceChange_manage_addProcess.php" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
								<b><?php print __($guid, 'Class') ?> *</b><br/>
								<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="class" id="class" value="<?php print $rowSelect["course"] . "." . $rowSelect["class"] ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Upcoming Class Slots') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="gibbonTTDayRowClassID" id="gibbonTTDayRowClassID" class="standardWidth">
									<option value='Please select...'><?php print __($guid, 'Please select...') ?></option>
									<?php
									try {
										$dataSelect=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "time"=>date("H:i:s")); 
										$sqlSelect="SELECT gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.name AS period, timeStart, timeEnd, gibbonTTDay.name AS day, gibbonTTDayDate.date, gibbonTTSpaceChangeID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID AND (gibbonTTDayDate.date>:date1 OR (gibbonTTDayDate.date=:date2 AND timeEnd>:time)) ORDER BY gibbonTTDayDate.date, timeStart" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										if ($rowSelect["gibbonTTSpaceChangeID"]=="") {
											print "<option value='" . $rowSelect["gibbonTTDayRowClassID"] . "-" . $rowSelect["date"] . "'>" . dateConvertBack($guid, $rowSelect["date"]) . " (" . $rowSelect["day"] . " - " . $rowSelect["period"] . ")</option>" ; 
										}
									}
									?>
								</select>
								<script type="text/javascript">
									var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
									gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Facility') ?></b><br/>
							</td>
							<td class="right">
								<select name="gibbonSpaceID" id="gibbonSpaceID" class="standardWidth">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							</td>
						</tr>
						<tr>
							<td>
								<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	}
}
?>