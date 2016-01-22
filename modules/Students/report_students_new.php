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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_new")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('New Students') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print _("Choose Options") ;
	print "</h2>" ;
	
	$type=NULL ;
	if (isset($_GET["type"])) {
		$type=$_GET["type"] ;
	}
	$ignoreEnrolment=NULL ;
	if (isset($_GET["ignoreEnrolment"])) {
		$ignoreEnrolment=$_GET["ignoreEnrolment"] ;
	}
	$startDateFrom=NULL ;
	if (isset($_GET["startDateFrom"])) {
		$startDateFrom=$_GET["startDateFrom"] ;
	}
	$startDateTo=NULL ;
	if (isset($_GET["startDateTo"])) {
		$startDateTo=$_GET["startDateTo"] ;
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($('select.type option:selected').val()=="Date Range" ) {
							$("#startDateFromRow").slideDown("fast", $("#startDateFromRow").css("display","table-row")); 
							$("#startDateToRow").slideDown("fast", $("#startDateToRow").css("display","table-row")); 
						} else {
							$("#startDateFromRow").css("display","none");
							$("#startDateToRow").css("display","none");
						} 
					 });
				});
			</script>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="type" id="type" class="type">
						<?php
						print "<option" ; if ($type=="Current School Year") { print " selected" ; } print " value='Current School Year'>" . _('Current School Year') . "</option>" ;
						print "<option" ; if ($type=="Date Range") { print " selected" ; } print " value='Date Range'>" . _('Date Range') . "</option>" ;
						?>
					</select>
				</td>
			</tr>
			<tr id='startDateFromRow' <?php if ($type!="Date Range") { print "style='display: none'" ; } ?>>
				<td> 
					<b><?php print _('From Date') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Earliest student start date to include.') ?><br/><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="startDateFrom" id="startDateFrom" maxlength=10 value="<?php print $startDateFrom ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var startDateFrom=new LiveValidation('startDateFrom');
						startDateFrom.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateFrom" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr id='startDateToRow' <?php if ($type!="Date Range") { print "style='display: none'" ; } ?>>
				<td> 
					<b><?php print _('To Date') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Latest student start date to include.') ?><br/><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="startDateTo" id="startDateTo" maxlength=10 value="<?php print $startDateTo ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var startDateTo=new LiveValidation('startDateTo');
						startDateTo.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateTo" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Ignore Enrolment') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('This is useful for picking up students who are set to Full, have a start date but are not yet enroled.') ?></span>
				</td>
				<td class="right">
					<input <?php if ($ignoreEnrolment=="on") { print "checked" ; } ?> name="ignoreEnrolment" id="ignoreEnrolment" type="checkbox">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_students_new.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($type!="") {
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		$proceed=TRUE ;
		if ($type=="Date Range") {
			print "<p>" ;
				print _("This report shows all students whose Start Date is on or between the indicated dates.") ;
			print "</p>" ;
			
			if ($startDateFrom=="" OR $startDateTo=="") {
				$proceed=FALSE ;
			}
		}
		else if ($type=="Current School Year") {
			print "<p>" ;
				print _("This report shows all students who are newly arrived in the school during the current academic year (e.g. they were not enroled in the previous academic year).") ;
			print "</p>" ;
		}
	
		
		if ($proceed==FALSE) {
			print "<div class='error'>" ;
				print _("Your request failed because your inputs were invalid.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($type=="Date Range") {
					if ($ignoreEnrolment!="on") {
						$data=array("startDateFrom"=>dateConvert($guid, $startDateFrom), "startDateTo"=>dateConvert($guid, $startDateTo)); 
						$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName" ;
					}
					else {
						$data=array("startDateFrom"=>dateConvert($guid, $startDateFrom), "startDateTo"=>dateConvert($guid, $startDateTo)); 
						$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName" ;
					}
				}
				else if ($type=="Current School Year") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY rollGroup, surname, preferredName" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
			if ($result->rowCount()>0) {
				if ($type=="Current School Year") {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Count") ;
							print "</th>" ;
							print "<th>" ;
								print _("Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Roll Group") ;
							print "</th>" ;
							print "<th>" ;
								print _("Username") ;
							print "</th>" ;
							print "<th>" ;
								print _("Start Date") ;
							print "</th>" ;
							print "<th>" ;
								print _("Last School") ;
							print "</th>" ;
							print "<th>" ;
								print _("Parents") ;
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
							try {
								$data2=array("gibbonSchoolYearID"=>getPreviousSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2), "gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sql2="SELECT surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY rollGroup, surname, preferredName" ;
								$result2=$connection2->prepare($sql2);
								$result2->execute($data2); 
							}
							catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
							if ($result2->rowCount()==0) {
								$count++ ;
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print $count ;
									print "</td>" ;
									print "<td>" ;
										print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
									print "</td>" ;
									print "<td>" ;
										print $row["rollGroup"] ;
									print "</td>" ;
									print "<td>" ;
										print $row["username"] ;
									print "</td>" ;
									print "<td>" ;
										print dateConvertBack($guid, $row["dateStart"]) ;
									print "</td>" ;
									print "<td>" ;
										print $row["lastSchool"] ;
									print "</td>" ;
									print "<td>" ;
										try {
											$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
											$sqlFamily="SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataFamily2=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
												$sqlFamily2="SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
												$resultFamily2=$connection2->prepare($sqlFamily2);
												$resultFamily2->execute($dataFamily2);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											while ($rowFamily2=$resultFamily2->fetch()) {
												print "<u>" . formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent") . "</u><br/>" ;
												$numbers=0 ;
												for ($i=1; $i<5; $i++) {
													if ($rowFamily2["phone" . $i]!="") {
														if ($rowFamily2["phone" . $i . "Type"]!="") {
															print "<i>" . $rowFamily2["phone" . $i . "Type"] . ":</i> " ;
														}
														if ($rowFamily2["phone" . $i . "CountryCode"]!="") {
															print "+" . $rowFamily2["phone" . $i . "CountryCode"] . " " ;
														}
														print $rowFamily2["phone" . $i] . "<br/>" ;
														$numbers++ ;
													}
												}
												if ($rowFamily2["citizenship1"]!="" OR $rowFamily2["citizenship1Passport"]!="") {
													print "<i>Passport</i>: " . $rowFamily2["citizenship1"] . " " . $rowFamily2["citizenship1Passport"] . "<br/>" ;
												}
												if ($rowFamily2["nationalIDCardNumber"]!="") {
													if ($_SESSION[$guid]["country"]=="") {
														print "<i>National ID Card</i>: " ;
													}
													else {
														print "<i>" . $_SESSION[$guid]["country"] . " ID Card</i>: " ;
													}
													print $rowFamily2["nationalIDCardNumber"] . "<br/>" ;
												}
											}
										}
									print "</td>" ;
								print "</tr>" ;
							}
						}
					print "</table>" ; 			
				}
				else if ($type=="Date Range") {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Count" ;
							print "</th>" ;
							print "<th>" ;
								print _("Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Roll Group") ;
							print "</th>" ;
							print "<th>" ;
								print _("Username") ;
							print "</th>" ;
							print "<th>" ;
								print "Start Date" ;
							print "</th>" ;
							print "<th>" ;
								print _("Last School") ;
							print "</th>" ;
							print "<th>" ;
								print _("Parents") ;
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
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print $count ;
								print "</td>" ;
								print "<td>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
								print "</td>" ;
								print "<td>" ;
									print $row["rollGroup"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["username"] ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($guid, $row["dateStart"]) ;
								print "</td>" ;
								print "<td>" ;
									print $row["lastSchool"] ;
								print "</td>" ;
								print "<td>" ;
									try {
										$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
										$sqlFamily="SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID" ;
										$resultFamily=$connection2->prepare($sqlFamily);
										$resultFamily->execute($dataFamily);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									while ($rowFamily=$resultFamily->fetch()) {
										try {
											$dataFamily2=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
											$sqlFamily2="SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
											$resultFamily2=$connection2->prepare($sqlFamily2);
											$resultFamily2->execute($dataFamily2);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										while ($rowFamily2=$resultFamily2->fetch()) {
											print "<u>" . formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent") . "</u><br/>" ;
											$numbers=0 ;
											for ($i=1; $i<5; $i++) {
												if ($rowFamily2["phone" . $i]!="") {
													if ($rowFamily2["phone" . $i . "Type"]!="") {
														print "<i>" . $rowFamily2["phone" . $i . "Type"] . ":</i> " ;
													}
													if ($rowFamily2["phone" . $i . "CountryCode"]!="") {
														print "+" . $rowFamily2["phone" . $i . "CountryCode"] . " " ;
													}
													print $rowFamily2["phone" . $i] . "<br/>" ;
													$numbers++ ;
												}
											}
											if ($rowFamily2["citizenship1"]!="" OR $rowFamily2["citizenship1Passport"]!="") {
												print "<i>Passport</i>: " . $rowFamily2["citizenship1"] . " " . $rowFamily2["citizenship1Passport"] . "<br/>" ;
											}
											if ($rowFamily2["nationalIDCardNumber"]!="") {
												if ($_SESSION[$guid]["country"]=="") {
													print "<i>National ID Card</i>: " ;
												}
												else {
													print "<i>" . $_SESSION[$guid]["country"] . " ID Card</i>: " ;
												}
												print $rowFamily2["nationalIDCardNumber"] . "<br/>" ;
											}
										}
									}
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ; 		
				}
			}
			else {
				print "<div class='warning'>" ;
					print _("There are no records to display.") ;
				print "</div>" ;
			}
		}
	}
}
?>