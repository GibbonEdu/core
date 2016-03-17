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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_student_dataUpdaterHistory.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Student Data Updater History') . "</div>" ;
	print "</div>" ;
	print "<p>" ;
	print __($guid, "This report allows a user to select a range of students and check whether or not they have had their personal and medical data updated after a specified date.") ;
	print "</p>" ;
	
	print "<h2>" ;
	print __($guid, "Choose Students") ;
	print "</h2>" ;
	
	$nonCompliant=NULL ;
	if (isset($_POST["nonCompliant"])) {
		$nonCompliant=$_POST["nonCompliant"] ;
	}
	$date=NULL ;
	if (isset($_POST["date"])) {
		$date=$_POST["date"] ;
	}
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_student_dataUpdaterHistory.php"?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Students') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></i></span>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
						<optgroup label='--<?php print __($guid, 'Students by Roll Group') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
							}
							?>
						</optgroup>
						<optgroup label='--<?php print __($guid, 'Students by Name') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Date') ?> *</b><br/>
					<span style="font-size: 85%"><i><?php print __($guid, 'Earliest acceptable update') ?><br/><?php print __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="date" id="date" maxlength=10 value="<?php if ($date!="") { print $date ; } else { print date($_SESSION[$guid]["i18n"]["dateFormatPHP"], (time()-(604800*26))) ; } ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 	date.add(Validate.Presence);
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
				<b><?php print __($guid, 'Show Only Non-Compliant?') ?></b><br/>
				<span style="font-size: 85%"><i><?php print __($guid, 'If not checked, show all. If checked, show only non-compliant students.') ?></i><br/>
				</i></span>
			</td>
			<td class="right">
				<input <?php if ($nonCompliant=="Y") { print "checked" ; } ?> type='checkbox' name='nonCompliant' value='Y'/>
			</td>
		</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	$choices=NULL ;
	if (isset($_POST["Members"])) {
		$choices=$_POST["Members"] ;
	}
	
	if (count($choices)>0) {
		
		print "<h2>" ;
		print __($guid, "Report Data") ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlWhere=" AND (" ;
			for ($i=0; $i<count($choices); $i++) {
				$data[$choices[$i]]=$choices[$i] ;
				$sqlWhere=$sqlWhere . "gibbonPerson.gibbonPersonID=:" . $choices[$i] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-4) ;
			$sqlWhere=$sqlWhere . ")" ;
			$sql="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRollGroup.name AS name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Student") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Roll Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Personal Data") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Medical Data") ;
				print "</th>" ;
					print "<th>" ;
						print __($guid, "Parent Emails") ;
					print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				//Calculate personal
				$personal="" ;
				$personalFail=FALSE ;
				try {
					$dataPersonal=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
					$sqlPersonal="SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC" ;
					$resultPersonal=$connection2->prepare($sqlPersonal);
					$resultPersonal->execute($dataPersonal);
				}
				catch(PDOException $e) { }
				if ($resultPersonal->rowCount()>0) {
					$rowPersonal=$resultPersonal->fetch() ;
					if (dateConvert($guid, $date)<=substr($rowPersonal["timestamp"],0,10)) {
						$personal=dateConvertBack($guid, substr($rowPersonal["timestamp"],0,10)) ;
					}
					else {
						$personal="<span style='color: #ff0000; font-weight: bold'>" . dateConvertBack($guid, substr($rowPersonal["timestamp"],0,10)) . "</span>" ;
						$personalFail=TRUE ;
					}
				}
				else {
					$personal="<span style='color: #ff0000; font-weight: bold'>" . __($guid, "No data") . "</span>" ;
					$personalFail=TRUE ;
				}
				
				//Calculate medical
				$medical="" ;
				$medicalFail=FALSE ;
				try {
					$dataMedical=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
					$sqlMedical="SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC" ;
					$resultMedical=$connection2->prepare($sqlMedical);
					$resultMedical->execute($dataMedical);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultMedical->rowCount()>0) {
					$rowMedical=$resultMedical->fetch() ;
					if (dateConvert($guid, $date)<=substr($rowMedical["timestamp"],0,10)) {
						$medical=dateConvertBack($guid, substr($rowMedical["timestamp"],0,10)) ;
					}
					else {
						$medical="<span style='color: #ff0000; font-weight: bold'>" . dateConvertBack($guid, substr($rowMedical["timestamp"],0,10)) . "</span>" ;
						$medicalFail=TRUE ;
					}
				}
				else {
					$medical="<span style='color: #ff0000; font-weight: bold'>" . __($guid, "No data") . "</span>" ;
					$medicalFail=TRUE ;
				}
			
				if ($personalFail OR $medicalFail OR $nonCompliant=="") {
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
							print $count ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "</a>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["name"] ;
						print "</td>" ;
						print "<td>" ;
							print $personal ;
						print "</td>" ;
						print "<td>" ;
							print $medical ;
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
									$sqlFamily2="SELECT * FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
									$resultFamily2=$connection2->prepare($sqlFamily2);
									$resultFamily2->execute($dataFamily2);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								$emails="" ;
								while ($rowFamily2=$resultFamily2->fetch()) {
									if ($rowFamily2["contactPriority"]==1) {
										if ($rowFamily2["email"]!="") {
											$emails.=$rowFamily2["email"] . ", " ;
										}
									}
									else if ($rowFamily2["contactEmail"]=="Y") {
										if ($rowFamily2["email"]!="") {
											$emails.=$rowFamily2["email"] . ", " ;
										}
									}
								}
								if ($emails!="") {
									print substr($emails,0,-2) ;
								}
							}
						print "</td>" ;
					
					print "</tr>" ;
				}
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=5>" ;
						print __($guid, "There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>