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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_left.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Left Students') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print __($guid, "Choose Options") ;
	print "</h2>" ;
	
	$endDateFrom=NULL ;
	if (isset($_GET["endDateFrom"])) {
		$endDateFrom=$_GET["endDateFrom"] ;
	}
	$endDateTo=NULL ;
	if (isset($_GET["endDateTo"])) {
		$endDateTo=$_GET["endDateTo"] ;
	}
	$ignoreStatus=NULL ;
	if (isset($_GET["ignoreStatus"])) {
		$ignoreStatus=$_GET["ignoreStatus"] ;
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'From Date') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Earliest student end date to include.') ?><br/><?php print __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="endDateFrom" id="endDateFrom" maxlength=10 value="<?php print $endDateFrom ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var endDateFrom=new LiveValidation('endDateFrom');
						endDateFrom.add(Validate.Presence);
						endDateFrom.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#endDateFrom" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'To Date') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Latest student end date to include.') ?><br/><?php print __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="endDateTo" id="endDateTo" maxlength=10 value="<?php print $endDateTo ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var endDateTo=new LiveValidation('endDateTo');
						endDateTo.add(Validate.Presence);
						endDateTo.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#endDateTo" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Ignore Status') ?></b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'This is useful for picking up students who have not yet left, but have an End Date set.') ?></span>
				</td>
				<td class="right">
					<input <?php if ($ignoreStatus=="on") { print "checked" ; } ?> name="ignoreStatus" id="ignoreStatus" type="checkbox">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_students_left.php">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($endDateFrom!="" AND $endDateTo!="") {
		print "<h2>" ;
		print __($guid, "Results") ;
		print "</h2>" ;
		
		try {
			$data=array("endDateFrom"=>dateConvert($guid, $endDateFrom), "endDateTo"=>dateConvert($guid, $endDateTo)); 
			if ($ignoreStatus=="on") {
				$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool, departureReason FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo ORDER BY surname, preferredName" ;
			}
			else {
				$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool, departureReason FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo AND status='Left' ORDER BY surname, preferredName" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data); 
		}
		catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
		if ($result->rowCount()>0) {
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Count") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Name") ;
						print "<span style='font-style: italic; font-size: 85%'>" . __($guid, 'Roll Group') . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Username") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "End Date") . "<br/>" ;
						print "<span style='font-style: italic; font-size: 85%'>" . __($guid, 'Departure Reason') . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Next School") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Parents") ;
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
							print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) . "<br/>" ;
							try {
								$dataCurrent=array("gibbonPersonID"=>$row["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlCurrent="SELECT name FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID" ;
								$resultCurrent=$connection2->prepare($sqlCurrent);
								$resultCurrent->execute($dataCurrent);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCurrent->rowCount()==1) {
								$rowCurrent=$resultCurrent->fetch() ;
								print "<span style='font-style: italic; font-size: 85%'>" . $rowCurrent["name"] . "</span>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print $row["username"] ;
						print "</td>" ;
						print "<td>" ;
							print dateConvertBack($guid, $row["dateEnd"]) . "<br/>" ;
							print "<span style='font-style: italic; font-size: 85%'>" . $row["departureReason"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["nextSchool"] ;
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
										print "<i>" . __($guid, 'Passport') . "</i>: " . $rowFamily2["citizenship1"] . " " . $rowFamily2["citizenship1Passport"] . "<br/>" ;
									}
									if ($rowFamily2["nationalIDCardNumber"]!="") {
										if ($_SESSION[$guid]["country"]=="") {
											print "<i>" . __($guid, 'National ID Card') . "</i>: " ;
										}
										else {
											print "<i>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card') . "</i>: " ;
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
		else {
			print "<div class='warning'>" ;
				print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
	}
}
?>