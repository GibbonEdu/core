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


if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_edit.php")==FALSE) {
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
		$gibbonCourseClassID=NULL ;
		if (isset($_GET["gibbonCourseClassID"])) {
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"];
		}
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
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("date"=>$date, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
				}
				else {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
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
					print _("The selected record does not exist, or you do not have access to it.") ;
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
					$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
				}
				$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
				
				//CHECK IF UNIT IS GIBBON OR HOOKED
				if ($row["gibbonHookID"]==NULL) {
					$hooked=FALSE ;
					$gibbonUnitID=$row["gibbonUnitID"]; 
					
					//Get gibbonUnitClassID
					try {
						$dataUnitClass=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitID); 
						$sqlUnitClass="SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
						$resultUnitClass=$connection2->prepare($sqlUnitClass);
						$resultUnitClass->execute($dataUnitClass);
					}
					catch(PDOException $e) {}
					if ($resultUnitClass->rowCount()==1) {
						$rowUnitClass=$resultUnitClass->fetch() ;
						$gibbonUnitClassID=$rowUnitClass["gibbonUnitClassID"] ;
					}
				}
				else {
					$hooked=TRUE ;
					$gibbonUnitIDToken=$row["gibbonUnitID"]; 
					$gibbonHookIDToken=$row["gibbonHookID"]; 
					
					try {
						$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
						$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
						$resultHooks=$connection2->prepare($sqlHooks);
						$resultHooks->execute($dataHooks);
					}
					catch(PDOException $e) { }
					if ($resultHooks->rowCount()==1) {
						$rowHooks=$resultHooks->fetch() ;
						$hookOptions=unserialize($rowHooks["options"]) ;
						if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
							try {
								$data=array("unitIDField"=>$gibbonUnitIDToken); 
								$sql="SELECT " . $hookOptions["unitTable"] . ".*, gibbonCourse.nameShort FROM " . $hookOptions["unitTable"] . " JOIN gibbonCourse ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["unitIDField"] . "=:unitIDField" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { }									
						}
					}
					
					//Get gibbonUnitClassID
					try {
						$dataUnitClass=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonUnitID"=>$gibbonUnitIDToken); 
						$sqlUnitClass="SELECT " . $hookOptions["classLinkIDField"] . " FROM " . $hookOptions["classLinkTable"] . " WHERE " . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID AND " . $hookOptions["classLinkJoinFieldUnit"] . "=:gibbonUnitID" ;
						$resultUnitClass=$connection2->prepare($sqlUnitClass);
						$resultUnitClass->execute($dataUnitClass);
					}
					catch(PDOException $e) { print $e->getMessage() ;}
					if ($resultUnitClass->rowCount()==1) {
						$rowUnitClass=$resultUnitClass->fetch() ;
						$gibbonUnitClassID=$rowUnitClass[$hookOptions["classLinkIDField"]] ;
					}
				}
				
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>" . _('Planner') . " $extra</a> > </div><div class='trailEnd'>" . _('Edit Lesson Plan') . "</div>" ;
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
					else if ($updateReturn=="fail6") {
						$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
					}
					else if ($updateReturn=="success0") {
						$updateReturnMessage=_("Your request was completed successfully.") ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				} 
				
				if (isset($_GET["duplicateReturn"])) { $duplicateReturn=$_GET["duplicateReturn"] ; } else { $duplicateReturn="" ; }
				$duplicateReturnMessage="" ;
				$class="error" ;
				if (!($duplicateReturn=="")) {
					if ($duplicateReturn=="success0") {
						$duplicateReturnMessage=_("Your request was completed successfully.") . _('You can now edit more details of your newly duplicated entry.') ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $duplicateReturnMessage;
					print "</div>" ;
				} 
				
				if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
				$deleteReturnMessage="" ;
				$class="error" ;
				if (!($deleteReturn=="")) {
					if ($deleteReturn=="fail0") {
						$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
					}
					else if ($deleteReturn=="fail1") {
						$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($deleteReturn=="fail2") {
						$deleteReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
					}
					else if ($deleteReturn=="fail3") {
						$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
					}
					else if ($deleteReturn=="success0") {
						$deleteReturnMessage=_("Your request was completed successfully.") ;		
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $deleteReturnMessage;
					print "</div>" ;
				} 
				
				print "<div class='linkTop' style='margin-bottom: 7px'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID$params'><img style='margin: 0 0 -4px 3px' title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
				print "</div>" ;
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_editProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr class='break'>
							<td colspan=2> 
								<h3 style='margin-top: 0px'><?php print _('Basic Information') ?></h3>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php print _('Class') ?> *</b><br/>
							</td>
							<td class="right">
								<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
									<?php
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
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
									gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								 </script>
							</td>
						</tr>
					
						<tr>
							<td> 
								<b><?php print _('Unit') ?></b><br/>
							</td>
							<td class="right">
								<select name="gibbonUnitID" id="gibbonUnitID" style="width: 302px">
									<?php
									print "<option value=''></option>" ;
									print "<optgroup label='--" . _('Gibbon Units') . "--'>" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonUnitID"]==$row["gibbonUnitID"] AND $rowSelect["gibbonCourseClassID"]==$row["gibbonCourseClassID"]) {
											$selected="selected" ;
										}
										print "<option $selected class='" . $rowSelect["gibbonCourseClassID"] . "' value='" . $rowSelect["gibbonUnitID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
									}
									print "</optgroup>" ;	
									
									//List any hooked units
									$lastType="" ;
									$currentType="" ;
									try {
										$dataHooks=array(); 
										$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name" ;
										$resultHooks=$connection2->prepare($sqlHooks);
										$resultHooks->execute($dataHooks);
									}
									catch(PDOException $e) { }
									while ($rowHooks=$resultHooks->fetch()) {
										$hookOptions=unserialize($rowHooks["options"]) ;
										if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
											try {
												$dataHookUnits=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
												$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
												$resultHookUnits=$connection2->prepare($sqlHookUnits);
												$resultHookUnits->execute($dataHookUnits);
											}
											catch(PDOException $e) { }
											while ($rowHookUnits=$resultHookUnits->fetch()) {
												$selected="" ;
												if ($rowHookUnits[$hookOptions["unitIDField"]]==$row["gibbonUnitID"] AND $rowHooks["gibbonHookID"]==$row["gibbonHookID"] AND $rowHookUnits[$hookOptions["classLinkJoinFieldClass"]]==$row["gibbonCourseClassID"]) {
													$selected="selected" ;
												}
												$currentType=$rowHooks["name"] ;
												if ($currentType!=$lastType) {
													print "<optgroup label='--" . $currentType . "--'>" ;
												}
												print "<option $selected class='" . $rowHookUnits[$hookOptions["classLinkJoinFieldClass"]] . "' value='" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "'>" . htmlPrep($rowHookUnits[$hookOptions["unitNameField"]]) . "</option>" ;
												$lastType=$currentType ;
											}										
										}
									}
									
									?>
								</select>
								<script type="text/javascript">
									$("#gibbonUnitID").chainedTo("#gibbonCourseClassID");
								</script>			
								
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Name') ?> *</b><br/>
							</td>
							<td class="right">
								<input name="name" id="name" maxlength=50 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var name2=new LiveValidation('name');
									name2.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Summary') ?> *</b><br/>
							</td>
							<td class="right">
								<input name="summary" id="summary" maxlength=255 value="<?php print htmlPrep($row["summary"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var summary=new LiveValidation('summary');
									summary.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Date') ?> *</b><br/>
								<span style="font-size: 90%"><i>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
							</td>
							<td class="right">
								<input name="date" id="date" maxlength=10 value="<?php print dateConvertBack($guid, $row["date"]) ?>" type="text" style="width: 300px">
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
								<input name="timeStart" id="timeStart" maxlength=5 value="<?php print substr($row["timeStart"],0,5) ?>" type="text" style="width: 300px">
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
								<input name="timeEnd" id="timeEnd" maxlength=5 value="<?php print substr($row["timeEnd"],0,5) ?>" type="text" style="width: 300px">
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
						
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Lesson Content') ?></h3>
							</td>
						</tr>
						<?php
						print "<tr>" ;
							?>
							<td colspan=2> 
								<b><?php print _('Lesson Details') ?></b> 
								<?php print getEditor($guid,  TRUE, "description", $row["description"], 25, true, false, false) ?>
							</td>
							<?php
							print "</td>" ;
						print "</tr>" ;
						
						if ($row["gibbonUnitID"]!="") {
							try {
								if ($hooked==FALSE) {
									$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
									$sqlBlocks="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
								}
								else {
									$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
									$sqlBlocks="SELECT * FROM " . $hookOptions["classSmartBlockTable"] . " WHERE " . $hookOptions["classSmartBlockPlannerJoin"] . "=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
								}
								$resultBlocks=$connection2->prepare($sqlBlocks);
								$resultBlocks->execute($dataBlocks);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							print "<tr class='break'>" ;
								print "<td colspan=3>" ;
									print "<h3>" . _('Smart Blocks') . "</h3>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top' colspan=3>" ;
									print "<div style='padding: 5px; margin-top: 0px; text-align: right;'>" ;
										if ($hooked==FALSE) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&gibbonUnitClassID=$gibbonUnitClassID'>" . _('Edit Unit') . "</a> " ;
										}
										else {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonUnitID=" . $gibbonUnitIDToken . "-" . $gibbonHookIDToken . "&gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "&gibbonUnitClassID=$gibbonUnitClassID'>" . _('Edit Unit') . "</a> " ;
										}
									print "</div>" ;
								
									if ($resultBlocks->rowCount()<1) {
										print "<div class='error'>" ;
											print _("This lesson has not had any Smart Blocks content assigned to it.") ;
										print "</div>" ;
									}
									else {
										print "<div id='smartEdit'>" ;
											print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full_smartProcess.php'>" ;
												?>
												<style>
													#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
													#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
													div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
													html>body #sortable li { min-height: 58px; line-height: 1.2em; }
													#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
												</style>
												<script type="text/javascript">
													$(function() {
														$( "#sortable" ).sortable({
															placeholder: "ui-state-highlight",
															axis: 'y'
														});
													});
												</script>
											
												<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px'>
													<?php 
													$i=1 ;
													$minSeq=0 ;
													while ($rowBlocks=$resultBlocks->fetch()) {
														if ($i==1) {
															$minSeq=$rowBlocks["sequenceNumber"] ;
														}
														if ($hooked==FALSE) {
															makeBlock($guid, $connection2, $i, "plannerEdit", $rowBlocks["title"], $rowBlocks["type"], $rowBlocks["length"], $rowBlocks["contents"], $rowBlocks["complete"], "", $rowBlocks["gibbonUnitClassBlockID"], $rowBlocks["teachersNotes"]) ;
														}
														else {
															makeBlock($guid, $connection2, $i, "plannerEdit", $rowBlocks[$hookOptions["classSmartBlockTitleField"]], $rowBlocks[$hookOptions["classSmartBlockTypeField"]], $rowBlocks[$hookOptions["classSmartBlockLengthField"]], $rowBlocks[$hookOptions["classSmartBlockContentsField"]], $rowBlocks[$hookOptions["classSmartBlockCompleteField"]], "", $rowBlocks[$hookOptions["classSmartBlockIDField"]], $rowBlocks[$hookOptions["classSmartBlockTeachersNotesField"]]) ;
														}
														$i++ ;
													}
													?>
												</div>
												<?php
												print "<div style='text-align: right; margin-top: 3px'>" ;
													print "<input type='hidden' name='minSeq' value='$minSeq'>" ;
													print "<input type='hidden' name='params' value='$params'>" ;
													print "<input type='hidden' name='gibbonPlannerEntryID' value='$gibbonPlannerEntryID'>" ;
													print "<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
												print "</div>" ;
											print "</form>" ;
										print "</div>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						}
						?>
						
						<tr class='break'>
							<td colspan=3>
								<h3><?php print _('Teacher\'s Notes') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<?php print getEditor($guid,  TRUE, "teachersNotes", $row["teachersNotes"], 25, true, false, false ) ?>
							</td>
						</tr>
						
						<?php
						$checkedYes="" ;
						$checkedNo="" ;
						if ($row["homework"]=="Y") {
							$checkedYes="checked" ;
						}
						else {
							$checkedNo="checked" ;
						}
						
						$submissionYes="" ;
						$submissionNo="" ;
						if ($row["homeworkSubmission"]=="Y") {
							$submissionYes="checked" ;
						}
						else {
							$submissionNo="checked" ;
						}
						
						$crowdYes="" ;
						$crowdNo="" ;
						if ($row["homeworkCrowdAssess"]=="Y") {
							$crowdYes="checked" ;
						}
						else {
							$crowdNo="checked" ;
						}
						?>
								
						<script type="text/javascript">
							/* Homework Control */
							$(document).ready(function(){
								<?php
								if ($checkedNo=="checked") {
									?>
									$("#homeworkDueDateRow").css("display","none");
									$("#homeworkDueDateTimeRow").css("display","none");
									$("#homeworkDetailsRow").css("display","none");
									$("#homeworkSubmissionRow").css("display","none");
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
									<?php
								}
								else if ($submissionNo=="checked") {
									?>
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
									<?php
								}
								else if ($crowdNo=="checked") {
									?>
									$("#homeworkCrowdAssessControlRow").css("display","none");
									<?php
								}
								?>
								
								//Response to clicking on homework control
								$(".homework").click(function(){
									if ($('input[name=homework]:checked').val()=="Yes" ) {
										homeworkDueDate.enable();
										homeworkDetails.enable();
										$("#homeworkDueDateRow").slideDown("fast", $("#homeworkDueDateRow").css("display","table-row")); 
										$("#homeworkDueDateTimeRow").slideDown("fast", $("#homeworkDueDateTimeRow").css("display","table-row")); 
										$("#homeworkDetailsRow").slideDown("fast", $("#homeworkDetailsRow").css("display","table-row")); 
										$("#homeworkSubmissionRow").slideDown("fast", $("#homeworkSubmissionRow").css("display","table-row")); 					
									
										if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
											$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row")); 
											$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row")); 
											$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row")); 
											$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row")); 
											$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row")); 
											
											if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
												$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
												
											} else {
												$("#homeworkCrowdAssessControlRow").css("display","none");
											}
										} else {
											$("#homeworkSubmissionDateOpenRow").css("display","none");
											$("#homeworkSubmissionDraftsRow").css("display","none");
											$("#homeworkSubmissionTypeRow").css("display","none");
											$("#homeworkSubmissionRequiredRow").css("display","none");
											$("#homeworkCrowdAssessRow").css("display","none");
											$("#homeworkCrowdAssessControlRow").css("display","none");
										}
									} else {
										homeworkDueDate.disable();
										homeworkDetails.disable();
										$("#homeworkDueDateRow").css("display","none");
										$("#homeworkDueDateTimeRow").css("display","none");
										$("#homeworkDetailsRow").css("display","none");
										$("#homeworkSubmissionRow").css("display","none");
										$("#homeworkSubmissionDateOpenRow").css("display","none");
										$("#homeworkSubmissionDraftsRow").css("display","none");
										$("#homeworkSubmissionTypeRow").css("display","none");
										$("#homeworkSubmissionRequiredRow").css("display","none");
										$("#homeworkCrowdAssessRow").css("display","none");
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								 });
								 
								 //Response to clicking on online submission control
								 $(".homeworkSubmission").click(function(){
									if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
										$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row")); 
										$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row")); 
										$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row")); 
										$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row")); 
										$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row")); 
									
										if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
											$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
											
										} else {
											$("#homeworkCrowdAssessControlRow").css("display","none");
										}
									} else {
										$("#homeworkSubmissionDateOpenRow").css("display","none");
										$("#homeworkSubmissionDraftsRow").css("display","none");
										$("#homeworkSubmissionTypeRow").css("display","none");
										$("#homeworkSubmissionRequiredRow").css("display","none");
										$("#homeworkCrowdAssessRow").css("display","none");
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								 });
								 
								 //Response to clicking on crowd assessment control
								 $(".homeworkCrowdAssess").click(function(){
									if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
										$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
										
									} else {
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								 }); 
							});
						</script>
						
						<?php
						//Try and find the next slot for this class, to use as default HW deadline
						if ($row["homework"]=="N" AND $row["date"]!="" AND $row["timeStart"]!="" AND $row["timeEnd"]!="") {
							//Get $_GET values
							$homeworkDueDate="" ;
							$homeworkDueDateTime="" ;
							
							try {
								$dataNext=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "date"=>$row["date"]); 
								$sqlNext="SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>:date ORDER BY date, timeStart LIMIT 0, 10" ;
								$resultNext=$connection2->prepare($sqlNext);
								$resultNext->execute($dataNext);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultNext->rowCount()>0) {
								$rowNext=$resultNext->fetch() ;
								$homeworkDueDate=$rowNext["date"] ;
								$homeworkDueDateTime=$rowNext["timeStart"] ;
							}
						}
						?>
							
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Homework') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Homework?') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('If not previously set, this will default to the start of the next lesson.') ?></i></span>
							</td>
							<td class="right">
								<input <?php print $checkedYes ?> type="radio" name="homework" value="Yes" class="homework" /> <?php print _('Yes') ?>
								<input <?php print $checkedNo ?> type="radio" name="homework" value="No" class="homework" /> <?php print _('No') ?>
							</td>
						</tr>
						<tr id="homeworkDueDateRow">
							<td> 
								<b><?php print _('Homework Due Date') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
							</td>
							<td class="right">
								<input name="homeworkDueDate" id="homeworkDueDate" maxlength=10 value="<?php if ($row["homework"]=="Y") { print dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ; } else if ($homeworkDueDate!="") { print dateConvertBack($guid, $homeworkDueDate) ; } ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var homeworkDueDate=new LiveValidation('homeworkDueDate');
									homeworkDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									homeworkDueDate.add(Validate.Presence);
									<?php
									if ($row["homework"]!="Y") { 
										print "homeworkDueDate.disable();" ;
									}
									?>
								 </script>
								 <script type="text/javascript">
									$(function() {
										$( "#homeworkDueDate" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr id="homeworkDueDateTimeRow">
							<td> 
								<b><?php print _('Homework Due Date Time') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
							</td>
							<td class="right">
								<input name="homeworkDueDateTime" id="homeworkDueDateTime" maxlength=5 value="<?php if ($row["homework"]=="Y") { print substr($row["homeworkDueDateTime"],11,5) ; } else if ($homeworkDueDateTime!="") { print substr($homeworkDueDateTime,0,5) ; } ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var homeworkDueDateTime=new LiveValidation('homeworkDueDateTime');
									homeworkDueDateTime.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
								 </script>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT SUBSTRING(homeworkDueDateTime,12,5) AS homeworkDueTime FROM gibbonPlannerEntry ORDER BY homeworkDueDateTime" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["homeworkDueTime"] . "\", " ;
											}
											?>
										];
										$( "#homeworkDueDateTime" ).autocomplete({source: availableTags});
									});
								</script>
							</td>
						</tr>
						<tr id="homeworkDetailsRow">
							<td colspan=2> 
								<b><?php print _('Homework Details') ?> *</b> 
								<?php
								$initiallyHidden=true ;
								if ($row["homework"]=="Y") {
									$initiallyHidden=false ;
								}
								print getEditor($guid,  TRUE, "homeworkDetails", $row["homeworkDetails"], 25, true, true, $initiallyHidden) 
								?>
							</td>
						</tr>
						<tr id="homeworkSubmissionRow">
							<td> 
								<b><?php print _('Online Submission?') ?> *</b><br/>
							</td>
							<td class="right">
								<input <?php print $submissionYes ?> type="radio" name="homeworkSubmission" value="Yes" class="homeworkSubmission" /> <?php print _('Yes') ?>
								<input <?php print $submissionNo ?> type="radio" name="homeworkSubmission" value="No" class="homeworkSubmission" /> <?php print _('No') ?>
							</td>
						</tr>
						<tr id="homeworkSubmissionDateOpenRow">
							<td> 
								<b><?php print _('Sumbission Open Date') ?></b><br/>
								<span style="font-size: 90%"><i>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
							</td>
							<td class="right">
								<input name="homeworkSubmissionDateOpen" id="homeworkSubmissionDateOpen" maxlength=10 value="<?php print dateConvertBack($guid, $row["homeworkSubmissionDateOpen"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var homeworkSubmissionDateOpen=new LiveValidation('homeworkSubmissionDateOpen');
									homeworkSubmissionDateOpen.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
								 </script>
								 <script type="text/javascript">
									$(function() {
										$( "#homeworkSubmissionDateOpen" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr id="homeworkSubmissionDraftsRow">
							<td> 
								<b><?php print _('Drafts') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="homeworkSubmissionDrafts" id="homeworkSubmissionDrafts" style="width: 302px">
									<option <?php if ($row["homeworkSubmissionDrafts"]=="0") { print "selected " ;} ?>value="0"><?php print _('None') ?></option>
									<option <?php if ($row["homeworkSubmissionDrafts"]=="1") { print "selected " ;} ?>value="1">1</option>
									<option <?php if ($row["homeworkSubmissionDrafts"]=="2") { print "selected " ;} ?>value="2">2</option>
									<option <?php if ($row["homeworkSubmissionDrafts"]=="3") { print "selected " ;} ?>value="3">3</option>
								</select>
							</td>
						</tr>
						<tr id="homeworkSubmissionTypeRow">
							<td> 
								<b><?php print _('Submission Type') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="homeworkSubmissionType" id="homeworkSubmissionType" style="width: 302px">
									<option <?php if ($row["homeworkSubmissionType"]=="Link") { print "selected " ;} ?>value="Link"><?php print _('Link') ?></option>
									<option <?php if ($row["homeworkSubmissionType"]=="File") { print "selected " ;} ?>value="File"><?php print _('File') ?></option>
									<option <?php if ($row["homeworkSubmissionType"]=="Link/File") { print "selected " ;} ?>value="Link/File"><?php print _('Link/File') ?></option>
								</select>
							</td>
						</tr>
						<tr id="homeworkSubmissionRequiredRow">
							<td> 
								<b><?php print _('Submission Required') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="homeworkSubmissionRequired" id="homeworkSubmissionRequired" style="width: 302px">
									<option <?php if ($row["homeworkSubmissionRequired"]=="Optional") { print "selected " ;} ?>value="Optional"><?php print _('Optional') ?></option>
									<option <?php if ($row["homeworkSubmissionRequired"]=="Compulsory") { print "selected " ;} ?>value="Compulsory"><?php print _('Compulsory') ?></option>
								</select>
							</td>
						</tr>
						<?php if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess.php")) { ?>
							<tr id="homeworkCrowdAssessRow">
								<td> 
									<b><?php print _('Crowd Assessment?') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Allow crowd assessment of homework?') ?></i></span>
								</td>
								<td class="right">
									<input <?php print $crowdYes ?> type="radio" name="homeworkCrowdAssess" value="Yes" class="homeworkCrowdAssess" /> <?php print _('Yes') ?>
									<input <?php print $crowdNo ?> type="radio" name="homeworkCrowdAssess" value="No" class="homeworkCrowdAssess" /> <?php print _('No') ?>
								</td>
							</tr>
							<tr id="homeworkCrowdAssessControlRow">
								<td> 
									<b><?php print _('Access Controls?') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Decide who can see this homework.') ?></i></span>
								</td>
								<td class="right">
									<?php
									print "<table cellspacing='0' style='width: 308px' align=right>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print _("Role") ;
											print "</th>" ;
											print "<th style='text-align: center'>" ;
												print ("Access") ;
											print "</th>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td style='text-align: left'>" ;
												print _("Class Teachers") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input checked disabled='disabled' type='checkbox' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td style='text-align: left'>" ;
												print _("Submitter") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input checked disabled='disabled' type='checkbox' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='odd'>" ;
											print "<td style='text-align: left'>" ;
												print _("Classmates") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessClassmatesRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessClassmatesRead' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td style='text-align: left'>" ;
												print _("Other Students") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessOtherStudentsRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessOtherStudentsRead' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='odd'>" ;
											print "<td style='text-align: left'>" ;
												print _("Other Teachers") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessOtherTeachersRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessOtherTeachersRead' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td style='text-align: left'>" ;
												print _("Submitter's Parents") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessSubmitterParentsRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessSubmitterParentsRead' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='odd'>" ;
											print "<td style='text-align: left'>" ;
												print _("Classmates's Parents") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessClassmatesParentsRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessClassmatesParentsRead' />" ;
											print "</td>" ;
										print "</tr>" ;
										print "<tr class='even'>" ;
											print "<td style='text-align: left'>" ;
												print _("Other Parents") ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												print "<input " ;
												if ($row["homeworkCrowdAssessOtherParentsRead"]=="Y") { print "checked " ;}
												print "type='checkbox' name='homeworkCrowdAssessOtherParentsRead' />" ;
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
									?>
								</td>
							</tr>
						<?php } ?>
						
						<?php
						//OUTCOMES
						if ($viewBy=="date") {
							?>
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print _('Outcomes') ?></h3>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<div class='warning'>
										<?php print _('Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.') ?>
									</div>
								</td>
							</tr>
							<?php
						}
						else {
							?>
							<tr class='break'>
								<td colspan=2> 
									<h3><?php print _('Outcomes') ?></h3>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<p><?php print _('Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.') ?></p>
								</td>
							</tr>
							<?php 
							$type="outcome" ; 
							$allowOutcomeEditing=getSettingByScope($connection2, "Planner", "allowOutcomeEditing") ;
							$categories=array() ;
							$categoryCount=0 ;
							?> 
							<style>
								#<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
								#<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
								div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
								html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
								.<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
								.<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
							</style>
							<script>
								$(function() {
									$( "#<?php print $type ?>" ).sortable({
										placeholder: "<?php print $type ?>-ui-state-highlight",
										axis: 'y'
									});
								});
							</script>
							<tr>
								<td colspan=2> 
									<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
										<?php
										try {
											$dataBlocks=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID);  
											$sqlBlocks="SELECT gibbonPlannerEntryOutcome.*, scope, name, category FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ;
											$resultBlocks=$connection2->prepare($sqlBlocks);
											$resultBlocks->execute($dataBlocks);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										$i=1 ;
										$usedArrayFill="" ;
										if ($resultBlocks->rowCount()<1) {
											print "<div id='outcomeOuter0'>" ;
												print "<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>Outcomes listed here...</div>" ;
											print "</div>" ;
											
										}
										else {
											while ($rowBlocks=$resultBlocks->fetch()) {
												makeBlockOutcome($guid, $i, "outcome", $rowBlocks["gibbonOutcomeID"],  $rowBlocks["name"],  $rowBlocks["category"], $rowBlocks["content"], "", TRUE, $allowOutcomeEditing) ;
												$usedArrayFill.="\"" . $rowBlocks["gibbonOutcomeID"] . "\"," ;
												$i++ ;
											}
										}
										?>
									</div>
									<div style='width: 100%; padding: 0px 0px 0px 0px'>
										<div class="ui-state-default_dud" style='padding: 0px; min-height: 66px'>
											<table class='blank' cellspacing='0' style='width: 100%'>
												<tr>
													<td style='width: 50%'>
														<script type="text/javascript">
															<?php
															if ($i<1) {
																print "var outcomeCount=1;" ;
															}
															else {
																print "var outcomeCount=$i;" ;
															}
															?>
														</script>
														<select id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
															<option class='all' value='0'><?php print _('Choose an outcome to add it to this lesson.') ?></option>
															<?php
															$currentCategory="" ;
															$lastCategory="" ;
															$switchContents="" ;
															try {
																$countClause=0 ;
																$years=explode(",", $gibbonYearGroupIDList) ;
																$dataSelect=array();  
																$sqlSelect="" ;
																foreach ($years as $year) {
																	$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																	$sqlSelect.="(SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																	$countClause++ ;
																}
																$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY category, name");
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															print "<optgroup label='--" . _('SCHOOL OUTCOMES') . "--'>" ;
															while ($rowSelect=$resultSelect->fetch()) {
																$currentCategory=$rowSelect["category"] ;
																if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																	print "<optgroup label='--" . $currentCategory . "--'>" ;
																	print "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>" ;
																	$categories[$categoryCount]=$currentCategory ;
																	$categoryCount++ ;
																}
																print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
																$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . ($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
																$switchContents.="outcomeCount++ ;" ;
																$switchContents.="$('#newOutcome').val('0');" ;
																$switchContents.="break;" ;
																$lastCategory=$rowSelect["category"] ;
															}
														
															$currentCategory="" ;
															$lastCategory="" ;
															$currentLA="" ;
															$lastLA="" ;
															try {
																$countClause=0 ;
																$years=explode(",", $gibbonYearGroupIDList) ;
																$dataSelect=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
																$sqlSelect="" ;
																foreach ($years as $year) {
																	$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																	$sqlSelect.="(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																	$countClause++ ;
																}
																$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY learningArea, category, name");
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															while ($rowSelect=$resultSelect->fetch()) {
																$currentCategory=$rowSelect["category"] ;
																$currentLA=$rowSelect["learningArea"] ;
																if (($currentLA!=$lastLA) AND $currentLA!="") {
																	print "<optgroup label='--" . strToUpper($currentLA) . " " . _('OUTCOMES') . "--'>" ;
																}
																if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																	print "<optgroup label='--" . $currentCategory . "--'>" ;
																	print "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>" ;
																	$categories[$categoryCount]=$currentCategory ;
																	$categoryCount++ ;
																}
																print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
																$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
																$switchContents.="outcomeCount++ ;" ;
																$switchContents.="$('#newOutcome').val('0');" ;
																$switchContents.="break;" ;
																$lastCategory=$rowSelect["category"] ;
																$lastLA=$rowSelect["learningArea"] ;
															}
														
															?>
														</select><br/>
														<?php
														if (count($categories)>0) {
															?>
															<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
																<option value='all'><?php print _('View All') ?></option>
																<?php
																$categories=array_unique($categories) ;
																$categories=msort($categories) ;
																foreach ($categories AS $category) {
																	print "<option value='$category'>$category</option>" ;
																}
																?>
															</select>
															<script type="text/javascript">
																$("#newOutcome").chainedTo("#outcomeFilter");
															</script>
															<?php
														}
														?>
														<script type='text/javascript'>
															var <?php print $type ?>Used=new Array(<?php print substr($usedArrayFill,0,-1) ?>);
															var <?php print $type ?>UsedCount=<?php print $type ?>Used.length ;
															
															function outcomeDisplayElements(number) {
																$("#<?php print $type ?>Outer0").css("display", "none") ;
																if (<?php print $type ?>Used.indexOf(number)<0) {
																	<?php print $type ?>Used[<?php print $type ?>UsedCount]=number ;
																	<?php print $type ?>UsedCount++ ;
																	switch(number) {
																		<?php print $switchContents ?>
																	}
																}
																else {
																	alert("This element has already been selected!") ;
																	$('#newOutcome').val('0');
																}
															}
														</script>
													</td>
												</tr>
											</table>
										</div>
									</div>
								</td>
							</tr>
							<?php
						}
						?>
						
						
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Access') ?></h3>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Viewable to Students') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="viewableStudents" id="viewableStudents" style="width: 302px">
									<option <?php if ($row["viewableStudents"]=="N") { print "selected " ; } ?>value="N"><?php print _('No') ?></option>
									<option <?php if ($row["viewableStudents"]=="Y") { print "selected " ; } ?>value="Y"><?php print _('Yes') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Viewable to Parents') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="viewableParents" id="viewableParents" style="width: 302px">
									<option <?php if ($row["viewableParents"]=="N") { print "selected " ; } ?>value="N"><?php print _('No') ?></option>
									<option <?php if ($row["viewableParents"]=="Y") { print "selected " ; } ?>value="Y"><?php print _('Yes') ?></option>
								</select>
							</td>
						</tr>
						
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Current Guests') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<?php
								try {
									$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
									$sql="SELECT title, preferredName, surname, category, gibbonPlannerEntryGuest.* FROM gibbonPlannerEntryGuest JOIN gibbonPerson ON (gibbonPlannerEntryGuest.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY surname, preferredName" ; 
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
									print "<i><b>Warning</b>: If you delete a guest, any unsaved changes to this planner entry will be lost!</i>" ;
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print _("Name") ;
											print "</th>" ;
											print "<th>" ;
												print _("Role") ;
											print "</th>" ;
											print "<th>" ;
												print _("Actions") ;
											print "</th>" ;
										print "</tr>" ;
										
										$count=0;
										$rowNum="odd" ;
										while ($row=$result->fetch()) {
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
													print formatName(htmlPrep($row["title"]), htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), htmlPrep($row["category"]), true, true) ;
												print "</td>" ;
												print "<td>" ;
													print $row["role"] ;
												print "</td>" ;
												print "<td>" ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_edit_guest_deleteProcess.php?gibbonPlannerEntryGuestID=" . $row["gibbonPlannerEntryGuestID"] . "&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&address=" . $_GET["q"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
												print "</td>" ;
											print "</tr>" ;
										}
									print "</table>" ;
								}
								?>
							</td>
						</tr>
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('New Guests') ?></h3>
							</td>
						</tr>
						<tr>
						<td> 
							<b><?php print _('Guest List') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="right">
							<select name="guests[]" id="guests[]" multiple style="width: 302px; height: 150px">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, title, preferredName, surname, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), htmlPrep($rowSelect["category"]), true, true) . "</option>" ;
								}
								?>
							</select>
						</td>
						<tr>
							<td> 
								<b><?php print _('Role') ?></b><br/>
							</td>
							<td class="right">
								<select name="role" id="role" style="width: 302px">
									<option value="Guest Student"><?php print _('Guest Student') ?></option>
									<option value="Guest Teacher"><?php print _('Guest Teacher') ?></option>
									<option value="Guest Assistant"><?php print _('Guest Assistant') ?></option>
									<option value="Guest Technician"><?php print _('Guest Technician') ?></option>
									<option value="Guest Parent"><?php print _('Guest Parent') ?></option>
									<option value="Other Guest"><?php print _('Other Guest') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="submit" value="<?php print _("Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
	}
}
?>