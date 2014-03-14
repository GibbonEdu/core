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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/rollover.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Rollover</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage =_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage =_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Your request failed because the selected person is already registered." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Your request was successful, but some data was not properly saved." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Your request was completed successfully.You can now add another record if you wish." ;	
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
	if ($step!=1 AND $step!=2 AND $step!=3) {
		$step=1 ;
	}
	
	//Step 1
	if ($step==1) {
		print "<h3>" ;
		print "Step 1" ;
		print "</h3>" ;
		
		$nextYear=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2) ;
		if ($nextYear==FALSE) {
			print "<div class='error'>" ;
			print "The next school year cannot be determined, so this action cannot be performed." ;
			print "</div>" ;
			}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			if ($nameNext=="") {
				print "<div class='error'>" ;
				print "The next school year cannot be determined, so this action cannot be performed." ;
				print "</div>" ;
			}
			else {
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollover.php&step=2" ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td colspan=2 style='text-align: justify'> 
								<?
								print "By clicking the \"Proceed\" button below you will initiate the rollover from <b>" . $_SESSION[$guid]["gibbonSchoolYearName"] . "</b> to <b>$nameNext</b>. In a big school this operation may take some time to complete. This will change data in numerous tables across the system! <span style='color: #cc0000'><i>You are really, very strongly advised to backup all data before you proceed</i></span>." ;
								?>
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<input type="hidden" name="nextYear" value="<? print $nextYear ?>">
								<input type="submit" value="Proceed">
							</td>
						</tr>
					</table>
				<?
			}
		}
	}
	else if ($step==2) {
		print "<h3>" ;
		print "Step 2" ;
		print "</h3>" ;
		
		$nextYear=$_POST["nextYear"] ;
		if ($nextYear=="" OR $nextYear!=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2)) {
			print "<div class='error'>" ;
			print "The next school year cannot be determined, so this action cannot be performed." ;
			print "</div>" ;
		}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			$sequenceNext=$rowNext["sequenceNumber"] ;
			if ($nameNext=="" OR $sequenceNext=="") {
				print "<div class='error'>" ;
				print "The next school year cannot be determined, so this action cannot be performed." ;
				print "</div>" ;
				}
			else {
				print "<p>" ;
				print "In rolling over to $nameNext, the following actions will take place. You may need to adjust some fields below to get the result you desire." ;
				print "</p>" ;
				
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollover.php&step=3'>" ;
					
					//Set enrolment select values
					$yearGroupOptions="" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowSelect=$resultSelect->fetch()) {
						$yearGroupOptions=$yearGroupOptions . "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
					}		
					
					$rollGroupOptions="" ;
					try {
						$dataSelect=array("gibbonSchoolYearID"=>$nextYear); 
						$sqlSelect="SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowSelect=$resultSelect->fetch()) {
						$rollGroupOptions=$rollGroupOptions . "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
					}		
					
					//ADD YEAR FOLLOWING NEXT
					if (getNextSchoolYearID($nextYear, $connection2)==FALSE) {
						print "<h4>" ;
						print "Add Year Following $nameNext" ;
						print "</h4>" ;
						?>
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									<b>School Year Name *</b><br/>
									<span style="font-size: 90%"><i>Needs to be unique.</i></span>
								</td>
								<td class="right">
									<input name="nextname" id="nextname" maxlength=9 value="" type="text" style="width: 300px">
									<script type="text/javascript">
										var nextname=new LiveValidation('nextname');
										nextname.add(Validate.Presence);
									 </script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Status *</b>
								</td>
								<td class="right">
									<input readonly name="next-status" id="next-status" value="Upcoming" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>Sequence Number *</b><br/>
									<span style="font-size: 90%"><i>Needs to be unique. Controls the chronological ordering of years.</i></span>
								</td>
								<td class="right">
									<input readonly name="next-sequenceNumber" id="next-sequenceNumber" maxlength=3 value="<? print ($sequenceNext+1) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>First Day *</b><br/>
									<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
								</td>
								<td class="right">
									<input name="nextfirstDay" id="nextfirstDay" maxlength=10 value="" type="text" style="width: 300px">
									<script type="text/javascript">
										var nextfirstDay=new LiveValidation('nextfirstDay');
										nextfirstDay.add(Validate.Presence);
										nextfirstDay.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									 </script>
									 <script type="text/javascript">
										$(function() {
											$( "#nextfirstDay" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Last Day *</b><br/>
									<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
								</td>
								<td class="right">
									<input name="nextlastDay" id="nextlastDay" maxlength=10 value="" type="text" style="width: 300px">
									<script type="text/javascript">
										var nextlastDay=new LiveValidation('nextlastDay');
										nextlastDay.add(Validate.Presence);
										nextlastDay.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									 </script>
									 <script type="text/javascript">
										$(function() {
											$( "#nextlastDay" ).datepicker();
										});
									</script>
								</td>
							</tr>
						</table>
						<?
					}
					
					
					//SET EXPECTED USERS TO FULL
					print "<h4>" ;
					print "Set Expected Users To Full" ;
					print "</h4>" ;
					print "<p>" ;
					print "This step primes newcomers who have status set to \"Expected\" to be enrolled as students or added as staff (below)." ;
					print "</p>" ;
					
					
					try {
						$dataExpect=array(); 
						$sqlExpect="SELECT gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' ORDER BY name, surname, preferredName" ;
						$resultExpect=$connection2->prepare($sqlExpect);
						$resultExpect->execute($dataExpect);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($resultExpect->rowCount()<1) {
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Name" ;
								print "</th>" ;
								print "<th>" ;
									print "Primary Role" ;
								print "</th>" ;
								print "<th>" ;
									print "Current Status" ;
								print "</th>" ;
								print "<th>" ;
									print "New Status" ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($rowExpect=$resultExpect->fetch()) {
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
										print "<input type='hidden' name='$count-expect-gibbonPersonID' value='" . $rowExpect["gibbonPersonID"] . "'>" ;
										print formatName("", $rowExpect["preferredName"], $rowExpect["surname"], "Student", true) ;
									print "</td>" ;
									print "<td>" ;
										print $rowExpect["name"] ;
									print "</td>" ;
									print "<td>" ;
										print "Expected" ;
									print "</td>" ;
									print "<td>" ;
										print "<select name='$count-expect-status' id='$count-expect-status' style='float: left; width:110px'>" ;
											print "<option value='Expected'>Expected</option>" ;	
											print "<option selected value='Full'>Full</option>" ;
											print "<option value='Left'>Left</option>" ;
										print "</select>" ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						print "<input type='hidden' name='expect-count' value='$count'>" ;
					}
					
					//ENROL NEW STUDENTS
					print "<h4>" ;
					print "Enrol New Students (Status Expected)" ;
					print "</h4>" ;
					print "<p>" ;
					print "Take students who are marked expected and enrol them. All parents of new students who are enroled below will have their status set to \"Full\". If a student is not enrolled, they will be set to \"Left\"." ;
					print "</p>" ;
					
					if ($yearGroupOptions=="" OR $rollGroupOptions=="") {
						print "<div class='error'>Year groups or roll groups are not properly set up, so you cannot proceed with this section.</div>" ; 
					}
					else {
						try {
							$dataEnrol=array(); 
							$sqlEnrol="SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Student' ORDER BY surname, preferredName" ;
							$resultEnrol=$connection2->prepare($sqlEnrol);
							$resultEnrol->execute($dataEnrol);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultEnrol->rowCount()<1) {
							print "<div class='error'>" ;
							print _("There are no records to display.") ;
							print "</div>" ;
						}
						else {
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "Name" ;
									print "</th>" ;
									print "<th>" ;
										print "Primary Role" ;
									print "</th>" ;
									print "<th>" ;
										print "Enrol" ;
									print "</th>" ;
									print "<th>" ;
										print "Year Group" ;
									print "</th>" ;
									print "<th>" ;
										print "Roll Group" ;
									print "</th>" ;
								print "</tr>" ;
								
								$count=0;
								$rowNum="odd" ;
								while ($rowEnrol=$resultEnrol->fetch()) {
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
											print "<input type='hidden' name='$count-enrol-gibbonPersonID' value='" . $rowEnrol["gibbonPersonID"] . "'>" ;
											print formatName("", $rowEnrol["preferredName"], $rowEnrol["surname"], "Student", true) ;
										print "</td>" ;
										print "<td>" ;
											print $rowEnrol["name"] ;
										print "</td>" ;
										print "<td>" ;
											print "<input checked type='checkbox' name='$count-enrol-enrol' value='Y'>" ;
										print "</td>" ;
										print "<td>" ;
											print "<select name='$count-enrol-gibbonYearGroupID' id='$count-enrol-gibbonYearGroupID' style='float: left; width:110px'>" ;
												print $yearGroupOptions ;		
											print "</select>" ;
										print "</td>" ;
										print "<td>" ;
											print "<select name='$count-enrol-gibbonRollGroupID' id='$count-enrol-gibbonRollGroupID' style='float: left; width:110px'>" ;
												print $rollGroupOptions ;		
											print "</select>" ;
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							
							print "<input type='hidden' name='enrol-count' value='$count'>" ;
						}
					}
					
					print "<h4>" ;
					print "Enrol New Students (Status Full)" ;
					print "</h4>" ;
					print "<p>" ;
					print "Take new students who are already set as full, but who were not enrolled last year, and enrol them. These students probably came through the Online Application form, and may already be enrolled in next year: if this is the case, their enrolment will be updated as per the information below. All parents of new students who are enroled below will have their status set to \"Full\". If a student is not enrolled, they will be set to \"Left\"" ;
					print "</p>" ;
					
					if ($yearGroupOptions=="" OR $rollGroupOptions=="") {
						print "<div class='error'>Year groups or roll groups are not properly set up, so you cannot proceed with this section.</div>" ; 
					}
					else {
						$students=array() ;
						$count=0 ;
						try {
							$dataEnrol=array(); 
							$sqlEnrol="SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND category='Student' ORDER BY surname, preferredName" ;
							$resultEnrol=$connection2->prepare($sqlEnrol);
							$resultEnrol->execute($dataEnrol);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultEnrol->rowCount()<1) {
							print "<div class='error'>" ;
							print _("There are no records to display.") ;
							print "</div>" ;
						}
						else {
							while ($rowEnrol=$resultEnrol->fetch()) {
								try {
									$dataEnrolled=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$rowEnrol["gibbonPersonID"]); 
									$sqlEnrolled="SELECT gibbonStudentEnrolment.* FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
									$resultEnrolled=$connection2->prepare($sqlEnrolled);
									$resultEnrolled->execute($dataEnrolled);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultEnrolled->rowCount()<1) {
									$students[$count][0]=$rowEnrol["gibbonPersonID"] ; 
									$students[$count][1]=$rowEnrol["surname"] ; 
									$students[$count][2]=$rowEnrol["preferredName"] ; 
									$students[$count][3]=$rowEnrol["name"] ; 
									$count++ ;
								}		
							}
						}
						
						if ($count<1) {
							print "<div class='error'>" ;
							print "There are no users to display." ;
							print "</div>" ;
						}
						else {
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "Name" ;
									print "</th>" ;
									print "<th>" ;
										print "Primary Role" ;
									print "</th>" ;
									print "<th>" ;
										print "Enrol" ;
									print "</th>" ;
									print "<th>" ;
										print "Year Group" ;
									print "</th>" ;
									print "<th>" ;
										print "Roll Group" ;
									print "</th>" ;
								print "</tr>" ;
								
								$count=0;
								$rowNum="odd" ;
								foreach ($students as $student) {
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
											print "<input type='hidden' name='$count-enrolFull-gibbonPersonID' value='" . $student[0] . "'>" ;
											print formatName("", $student[2], $student[1], "Student", true) ;
										print "</td>" ;
										print "<td>" ;
											print $student[3] ;
										print "</td>" ;
										print "<td>" ;
											print "<input checked type='checkbox' name='$count-enrolFull-enrol' value='Y'>" ;
										print "</td>" ;
										//Check for enrolment in next year (caused by automated enrolment on application form accept)
										$yearGroupSelect="" ;
										$rollGroupSelect="" ;
										try {
											$dataEnrolled=array("gibbonSchoolYearID"=>$nextYear, "gibbonPersonID"=>$student[0]); 
											$sqlEnrolled="SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID" ;
											$resultEnrolled=$connection2->prepare($sqlEnrolled);
											$resultEnrolled->execute($dataEnrolled);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultEnrolled->rowCount()==1) {
											$rowEnrolled=$resultEnrolled->fetch() ;
											$yearGroupSelect=$rowEnrolled["gibbonYearGroupID"] ;
											$rollGroupSelect=$rowEnrolled["gibbonRollGroupID"] ;
										}
										print "<td>" ;
											print "<select name='$count-enrolFull-gibbonYearGroupID' id='$count-enrolFull-gibbonYearGroupID' style='float: left; width:110px'>" ;
												try {
													$dataSelect=array(); 
													$sqlSelect="SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($yearGroupSelect==$rowSelect["gibbonYearGroupID"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}
											print "</select>" ;
										print "</td>" ;
										print "<td>" ;
											print "<select name='$count-enrolFull-gibbonRollGroupID' id='$count-enrolFull-gibbonRollGroupID' style='float: left; width:110px'>" ;
												try {
													$dataSelect=array("gibbonSchoolYearID"=>$nextYear); 
													$sqlSelect="SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($rollGroupSelect==$rowSelect["gibbonRollGroupID"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}		
											print "</select>" ;
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							
							print "<input type='hidden' name='enrolFull-count' value='$count'>" ;
						}
					}
					
					
					//RE-ENROL OTHER STUDENTS
					print "<h4>" ;
					print "Re-Enrol Other Students" ;
					print "</h4>" ;
					print "<p>" ;
					print "Any students who are not re-enroled will have their status set to \"Left\"." ;
					print "</p>" ;
					
					$lastYearGroup=getLastYearGroupID($connection2) ;
					
					if ($yearGroupOptions=="" OR $rollGroupOptions=="") {
						print "<div class='error'>Year groups or roll groups are not properly set up, so you cannot proceed with this section.</div>" ; 
					}
					else {
						try {
							$dataReenrol=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$lastYearGroup); 
							$sqlReenrol="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRole.name, category, gibbonStudentEnrolment.gibbonYearGroupID, gibbonRollGroupIDNext FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND NOT gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName" ;
							$resultReenrol=$connection2->prepare($sqlReenrol);
							$resultReenrol->execute($dataReenrol);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultReenrol->rowCount()<1) {
							print "<div class='error'>" ;
							print _("There are no records to display.") ;
							print "</div>" ;
						}
						else {
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr class='head'>" ;
									print "<th>" ;
										print "Name" ;
									print "</th>" ;
									print "<th>" ;
										print "Primary Role" ;
									print "</th>" ;
									print "<th>" ;
										print "Reenrol" ;
									print "</th>" ;
									print "<th>" ;
										print "Year Group" ;
									print "</th>" ;
									print "<th>" ;
										print "Roll Group" ;
									print "</th>" ;
								print "</tr>" ;
								
								$count=0;
								$rowNum="odd" ;
								while ($rowReenrol=$resultReenrol->fetch()) {
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
											print "<input type='hidden' name='$count-reenrol-gibbonPersonID' value='" . $rowReenrol["gibbonPersonID"] . "'>" ;
											print formatName("", $rowReenrol["preferredName"], $rowReenrol["surname"], "Student", true) ;
										print "</td>" ;
										print "<td>" ;
											print $rowReenrol["name"] ;
										print "</td>" ;
										print "<td>" ;
											print "<input checked type='checkbox' name='$count-reenrol-enrol' value='Y'>" ;
										print "</td>" ;
										print "<td>" ;
											print "<select name='$count-reenrol-gibbonYearGroupID' id='$count-reenrol-gibbonYearGroupID' style='float: left; width:110px'>" ;
												try {
													$dataSelect=array(); 
													$sqlSelect="SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($rowSelect["gibbonYearGroupID"]==getNextYearGroupID($rowReenrol["gibbonYearGroupID"], $connection2)) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}	
											print "</select>" ;
										print "</td>" ;
										print "<td>" ;
											print "<select name='$count-reenrol-gibbonRollGroupID' id='$count-reenrol-gibbonRollGroupID' style='float: left; width:110px'>" ;
												try {
													$dataSelect=array("gibbonSchoolYearID"=>$nextYear); 
													$sqlSelect="SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												while ($rowSelect=$resultSelect->fetch()) {
													$selected="" ;
													if ($rowSelect["gibbonRollGroupID"]==$rowReenrol["gibbonRollGroupIDNext"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
												}			
											print "</select>" ;
										print "</td>" ;
									print "</tr>" ;
								}
							print "</table>" ;
							
							print "<input type='hidden' name='reenrol-count' value='$count'>" ;
						}
					}
					
					
					
					//SET FINAL YEAR STUDENTS TO LEFT
					print "<h4>" ;
					print "Set Final Year Students To Left" ;
					print "</h4>" ;
					
					try {
						$dataFinal=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$lastYearGroup); 
						$sqlFinal="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName" ;
						$resultFinal=$connection2->prepare($sqlFinal);
						$resultFinal->execute($dataFinal);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultFinal->rowCount()<1) {
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Name" ;
								print "</th>" ;
								print "<th>" ;
									print "Primary Role" ;
								print "</th>" ;
								print "<th>" ;
									print "Current Status" ;
								print "</th>" ;
								print "<th>" ;
									print "New Status" ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($rowFinal=$resultFinal->fetch()) {
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
										print "<input type='hidden' name='$count-final-gibbonPersonID' value='" . $rowFinal["gibbonPersonID"] . "'>" ;
										print formatName("", $rowFinal["preferredName"], $rowFinal["surname"], "Student", true) ;
									print "</td>" ;
									print "<td>" ;
										print $rowFinal["name"] ;
									print "</td>" ;
									print "<td>" ;
										print "Full" ;
									print "</td>" ;
									print "<td>" ;
										print "<select name='$count-final-status' id='$count-final-status' style='float: left; width:110px'>" ;
											print "<option value='Full'>Full</option>" ;
											print "<option selected value='Left'>Left</option>" ;
										print "</select>" ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						print "<input type='hidden' name='final-count' value='$count'>" ;
					}
				
					
					//REGISTER NEW STAFF
					print "<h4>" ;
					print "Register New Staff" ;
					print "</h4>" ;
					print "<p>" ;
					print "Any staff who are not registered will have their status set to \"Left\"." ;
					print "</p>" ;
					
					try {
						$dataRegister=array(); 
						$sqlRegister="SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Staff' ORDER BY surname, preferredName" ;
						$resultRegister=$connection2->prepare($sqlRegister);
						$resultRegister->execute($dataRegister);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultRegister->rowCount()<1) {
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Name" ;
								print "</th>" ;
								print "<th>" ;
									print "Primary Role" ;
								print "</th>" ;
								print "<th>" ;
									print "Register" ;
								print "</th>" ;
								print "<th>" ;
									print "Type" ;
								print "</th>" ;
								print "<th>" ;
									print "Job Title" ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($rowRegister=$resultRegister->fetch()) {
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
										print "<input type='hidden' name='$count-register-gibbonPersonID' value='" . $rowRegister["gibbonPersonID"] . "'>" ;
										print formatName("", $rowRegister["preferredName"], $rowRegister["surname"], "Student", true) ;
									print "</td>" ;
									print "<td>" ;
										print $rowRegister["name"] ;
									print "</td>" ;
									print "<td>" ;
										print "<input checked type='checkbox' name='$count-register-enrol' value='Y'>" ;
									print "</td>" ;
									print "<td>" ;
										print "<select name='$count-register-type' id='$count-register-type' style='float: left; width:110px'>" ;
											print "<option value='Teaching'>Teaching</option>" ;	
											print "<option value='Support'>Support</option>" ;
										print "</select>" ;
									print "</td>" ;
									print "<td>" ;
										print "<input name='$count-register-jobTitle' id='$count-register-jobTitle' maxlength=100 value='' type='text' style='float: left; width:110px'>" ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						print "<input type='hidden' name='register-count' value='$count'>" ;
					}
					
					print "<table cellspacing='0' style='width: 100%'>" ;	
						print "<tr>" ;
							print "<td>" ;
								print "<span style='font-size: 90%'><i>* <? print _("denotes a required field") ; ?></i></span>" ;
							print "</td>" ;
							print "<td class='right'>" ;
								print "<input type='hidden' name='nextYear' value='$nextYear'>" ;
								print "<input type='submit' value='Proceed'>" ;
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
				print "</form>" ;
			}
		}
	}
	else if ($step==3) {
		$nextYear=$_POST["nextYear"] ;
		if ($nextYear=="" OR $nextYear!=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2)) {
			print "<div class='error'>" ;
			print "The next school year cannot be determined, so this action cannot be performed." ;
			print "</div>" ;
		}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			$sequenceNext=$rowNext["sequenceNumber"] ;
			if ($nameNext=="" OR $sequenceNext=="") {
				print "<div class='error'>" ;
				print "The next school year cannot be determined, so this action cannot be performed." ;
				print "</div>" ;
			}
			else {
				print "<h3>" ;
				print "Step 3" ;
				print "</h3>" ;
				
				//ADD YEAR FOLLOWING NEXT
				if (getNextSchoolYearID($nextYear, $connection2)==FALSE) {
					//ADD YEAR FOLLOWING NEXT
					print "<h4>" ;
					print "Add Year Following $nameNext" ;
					print "</h4>" ;
					
					$name=$_POST["nextname"] ;
					$status=$_POST["next-status"] ;
					$sequenceNumber=$_POST["next-sequenceNumber"] ;
					$firstDay=dateConvert($guid, $_POST["nextfirstDay"]) ;
					$lastDay=dateConvert($guid, $_POST["nextlastDay"]) ;
					
					if ($name=="" OR $status=="" OR $sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR $firstDay=="" OR $lastDay=="") {
						print "<div class='error'>" ;
						print _("Your request failed because your inputs were invalid.") ;
						print "</div>" ;
					}
					else {
						//Check unique inputs for uniqueness
						try {
							$data=array("name"=>$name, "sequenceNumber"=>$sequenceNumber); 
							$sql="SELECT * FROM gibbonSchoolYear WHERE name=:name OR sequenceNumber=:sequenceNumber" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($result->rowCount()>0) {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {	
							//Write to database
							$fail=false ;
							try {
								$data=array("name"=>$name, "status"=>$status, "sequenceNumber"=>$sequenceNumber, "firstDay"=>$firstDay, "lastDay"=>$lastDay); 
								$sql="INSERT INTO gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								$fail=true ;
							}
							if ($fail==false) {
								print "<div class='success'>" ;
								print "Add was successful." ;
								print "</div>" ;
							}
						}
					}
				}
				
				//Remember year end date of current year before advance
				$dateEnd=$_SESSION[$guid]["gibbonSchoolYearLastDay"] ;
				
				//ADVANCE SCHOOL YEAR
				print "<h4>" ;
				print "Advance School Year" ;
				print "</h4>" ;
				
				//Write to database
				$advance=true ;
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="UPDATE gibbonSchoolYear SET status='Past' WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" ;
					print "Advance failed due to a database error." ;
					print "</div>" ; 
					$advance=false ;
				}
				if ($advance) {
					$advance2=true ;
					try {
						$data=array("gibbonSchoolYearID"=>$nextYear); 
						$sql="UPDATE gibbonSchoolYear SET status='Current' WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
						print "Advance failed due to a database error." ;
						print "</div>" ; 
						$advance2=false ;
					}
					if ($advance2) {
						setCurrentSchoolYear($guid, $connection2) ;
						$_SESSION[$guid]["gibbonSchoolYearIDCurrent"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
						$_SESSION[$guid]["gibbonSchoolYearNameCurrent"]=$_SESSION[$guid]["gibbonSchoolYearName"] ;
						$_SESSION[$guid]["gibbonSchoolYearSequenceNumberCurrent"]=$_SESSION[$guid]["gibbonSchoolYearSequenceNumber"] ;
						
						print "<div class='success'>" ;
						print "Advance was successful, you are now in a new academic year!" ;
						print "</div>" ;
						
						
						//SET EXPECTED USERS TO FULL
						print "<h4>" ;
						print "Set Expected Users To Full" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["expect-count"])) {
							$count=$_POST["expect-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								$gibbonPersonID=$_POST["$i-expect-gibbonPersonID"] ;
								$status=$_POST["$i-expect-status"] ;
								
								//Write to database
								$expected=true ;
								try {
									if ($status=="Full") {
										$data=array("status"=>$status, "gibbonPersonID"=>$gibbonPersonID, "dateStart"=>$_SESSION[$guid]["gibbonSchoolYearFirstDay"]); 
										$sql="UPDATE gibbonPerson SET status=:status, dateStart=:dateStart WHERE gibbonPersonID=:gibbonPersonID" ;
									}
									else if ($status=="Left" OR $status=="Expected") {
										$data=array("status"=>$status, "gibbonPersonID"=>$gibbonPersonID); 
										$sql="UPDATE gibbonPerson SET status=:status WHERE gibbonPersonID=:gibbonPersonID" ;
									}
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$expected=false ;
								}
								if ($expected) {
									$success++ ;
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " updates failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print _("Your request was completed successfully.") ;
								print "</div>" ;
							}
						}
						
						
						//ENROL NEW STUDENTS
						print "<h4>" ;
						print "Enrol New Students (Status Expected)" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["enrol-count"])) {
							$count=$_POST["enrol-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								$gibbonPersonID=$_POST["$i-enrol-gibbonPersonID"] ;
								$enrol=$_POST["$i-enrol-enrol"] ;
								$gibbonYearGroupID=$_POST["$i-enrol-gibbonYearGroupID"] ;
								$gibbonRollGroupID=$_POST["$i-enrol-gibbonRollGroupID"] ;
								
								//Write to database
								if ($enrol=="Y") {
									$enroled=true ;
									try {
										$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonYearGroupID"=>$gibbonYearGroupID, "gibbonRollGroupID"=>$gibbonRollGroupID); 
										$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$enroled=false ;
									}
									if ($enroled) {
										$success++ ;
										
										try {
											$dataFamily=array("gibbonPersonID"=>$gibbonPersonID); 
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
												$sqlFamily2="SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID" ;
												$resultFamily2=$connection2->prepare($sqlFamily2);
												$resultFamily2->execute($dataFamily2);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											while ($rowFamily2=$resultFamily2->fetch()) {
												try {
													$dataFamily3=array("gibbonPersonID"=>$rowFamily2["gibbonPersonID"]); 
													$sqlFamily3="UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID" ;
													$resultFamily3=$connection2->prepare($sqlFamily3);
													$resultFamily3->execute($dataFamily3);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
											}
										}
									}
								}
								else {
									$ok=true ;
									try {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "dateEnd"=>$dateEnd); 
										$sql="UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$ok==false ;
									}
									if ($ok=true) {
										$success++ ;
									}
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " adds failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print "Add was successful." ;
								print "</div>" ;
							}
						}
						
						
						//ENROL NEW STUDENTS
						print "<h4>" ;
						print "Enrol New Students (Status Full)" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["enrolFull-count"])) {
							$count=$_POST["enrolFull-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								
								$gibbonPersonID=$_POST["$i-enrolFull-gibbonPersonID"] . "<br/>" ;
								$enrol=$_POST["$i-enrolFull-enrol"] ;
								$gibbonYearGroupID=$_POST["$i-enrolFull-gibbonYearGroupID"] ;
								$gibbonRollGroupID=$_POST["$i-enrolFull-gibbonRollGroupID"] ;
								
								//Write to database
								if ($enrol=="Y") {
									$enroled=true ;
									
									try {
										//Check for enrolment
										$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
										$sql="SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$enroled=false ;
									}
									if ($enroled) {
										if ($result->rowCount()==0) {
											try {
												$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonYearGroupID"=>$gibbonYearGroupID, "gibbonRollGroupID"=>$gibbonRollGroupID); 
												$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$enroled=false ;
											}
										}
										else if ($result->rowCount()==1) {
											try {
												$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonYearGroupID"=>$gibbonYearGroupID, "gibbonRollGroupID"=>$gibbonRollGroupID); 
												$sql="UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												$enroled=false ;
											}
										}
										else {
											$enroled=false ;
										}
									}
									
									if ($enroled) {
										$success++ ;
										try {
											$dataFamily=array("gibbonPersonID"=>$gibbonPersonID); 
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
												$sqlFamily2="SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID" ;
												$resultFamily2=$connection2->prepare($sqlFamily2);
												$resultFamily2->execute($dataFamily2);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											while ($rowFamily2=$resultFamily2->fetch()) {
												try {
													$dataFamily3=array("gibbonPersonID"=>$rowFamily2["gibbonPersonID"]); 
													$sqlFamily3="UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID" ;
													$resultFamily3=$connection2->prepare($sqlFamily3);
													$resultFamily3->execute($dataFamily3);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
											}
										}
									}
								}
								else {
									$ok=true ;
									try {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "dateEnd"=>$dateEnd); 
										$sql="UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$ok==false ;
									}
									if ($ok=true) {
										$success++ ;
									}
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " adds failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print "Add was successful." ;
								print "</div>" ;
							}
						}
						
						
						//RE-ENROL OTHER STUDENTS
						print "<h4>" ;
						print "Re-Enrol Other Students" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["reenrol-count"])) {
							$count=$_POST["reenrol-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								$gibbonPersonID=$_POST["$i-reenrol-gibbonPersonID"] ;
								$enrol=$_POST["$i-reenrol-enrol"] ;
								$gibbonYearGroupID=$_POST["$i-reenrol-gibbonYearGroupID"] ;
								$gibbonRollGroupID=$_POST["$i-reenrol-gibbonRollGroupID"] ;
								
								//Write to database
								if ($enrol=="Y") {
									$reenroled=true ;
									try {
										$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "gibbonYearGroupID"=>$gibbonYearGroupID, "gibbonRollGroupID"=>$gibbonRollGroupID); 
										$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$reenroled=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($reenroled) {
										$success++ ;
									}
								}
								else {
									$reenroled=true ;
									try {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "dateEnd"=>$dateEnd); 
										$sql="UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$reenroled=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($reenroled) {
										$success++ ;
									}
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " adds failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print "Add was successful." ;
								print "</div>" ;
							}
						}
						
						
						//SET FINAL YEAR STUDENTS TO LEFT
						print "<h4>" ;
						print "Set Final Year Students To Left" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["final-count"])) {
							$count=$_POST["final-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								$gibbonPersonID=$_POST["$i-final-gibbonPersonID"] ;
								$status=$_POST["$i-final-status"] ;
								
								//Write to database
								$left=true ;
								try {
									$data=array("gibbonPersonID"=>$gibbonPersonID, "dateEnd"=>$dateEnd, "status"=>$status); 
									$sql="UPDATE gibbonPerson SET status=:status, dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$left=false ;
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($left) {
									$success++ ;
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " updates failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print _("Your request was completed successfully.") ;
								print "</div>" ;
							}
						}
						
						
						//REGISTER NEW STAFF
						print "<h4>" ;
						print "Register New Staff" ;
						print "</h4>" ;
						
						$count=NULL ;
						if (isset($_POST["register-count"])) {
							$count=$_POST["register-count"] ;
						}
						if ($count=="") {
							print "<div class='error'>" ;
							print _("Your request failed because your inputs were invalid.") ;
							print "</div>" ;
						}
						else {
							$success=0 ;
							for ($i=1; $i<=$count; $i++) {
								$gibbonPersonID=$_POST["$i-register-gibbonPersonID"] ;
								$enrol=$_POST["$i-register-enrol"] ;
								$type=$_POST["$i-register-type"] ;
								$jobTitle=$_POST["$i-register-jobTitle"] ;
								
								//Write to database
								if ($enrol=="Y") {
									$enroled=true ;
									//Check for existing record
									try {
										$dataCheck=array("gibbonPersonID"=>$gibbonPersonID); 
										$sqlCheck="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID" ;
										$resultCheck=$connection2->prepare($sqlCheck);
										$resultCheck->execute($dataCheck);
									}
									catch(PDOException $e) { 
										$enroled=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultCheck->rowCount()==0) {
										try {
											$data=array("gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "jobTitle"=>$jobTitle); 
											$sql="INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type, jobTitle=:jobTitle" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											$enroled=false ;
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($enroled) {
											$success++ ;
										}
									}
									else if ($resultCheck->rowCount()==1) {
										try {
											$data=array("gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "jobTitle"=>$jobTitle); 
											$sql="UPDATE gibbonStaff SET type=:type, jobTitle=:jobTitle WHERE gibbonPersonID=:gibbonPersonID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											$enroled=false ;
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($enroled) {
											$success++ ;
										}
									}
								}
								else {
									$left=true ;
									try {
										$data=array("gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "jobTitle"=>$jobTitle, "dateEnd"=>$dateEnd); 
										$sql="UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=$gibbonPersonID" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$left=false ;
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($left) {
										$success++ ;
									}
								}
							}
							
							//Feedback result!
							if ($success==0) {
								print "<div class='error'>" ;
								print "Your request failed." ;
								print "</div>" ;
							}
							else if ($success<$count) {
								print "<div class='warning'>" ;
								print ($count-$success) . " adds failed." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
								print "Add was successful." ;
								print "</div>" ;
							}
						}
					}
				}				
			}
		}
	}
}
?>