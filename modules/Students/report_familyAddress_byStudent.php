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

$_SESSION[$guid]["report_student_emergencySummary.php_choices"]="" ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/report_familyAddress_byStudent.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Family Address by Student</div>" ;
	print "</div>" ;
	print "<p>" ;
	print _("This report attempts prints the family address(es) based on parents who are labelled as Contract Priority 1.") ;
	print "</p>" ;
	
	print "<h2>" ;
	print _("Choose Students") ;
	print "</h2>" ;
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_familyAddress_byStudent.php"?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Students') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
						<optgroup label='--<?php print _('Students by Roll Group') ?>--'>
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
						<optgroup label='--<?php print _('Students by Name') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
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
				<td colspan=2 class="right">
					<input type="submit" value="<?php print _("Submit") ; ?>">
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
		$_SESSION[$guid]["report_student_emergencySummary.php_choices"]=$choices ;
		
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		try {
			$data=array(); 
			$sqlWhere="(" ;
			for ($i=0; $i<count($choices); $i++) {
				$data[$choices[$i]]=$choices[$i] ;
				$sqlWhere=$sqlWhere . "gibbonFamilyChild.gibbonPersonID=:" . $choices[$i] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-4) ;
			$sqlWhere=$sqlWhere . ")" ;
			$sql="SELECT gibbonFamily.gibbonFamilyID, name, surname, preferredName, nameAddress, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE $sqlWhere ORDER BY name, surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		$array=array() ;
		$count=0 ;
		while ($row=$result->fetch()) {
			$array[$count]["gibbonFamilyID"]=$row["gibbonFamilyID"] ;
			$array[$count]["name"]=$row["name"] ;
			$array[$count]["nameAddress"]=$row["nameAddress"] ;
			$array[$count]["surname"]=$row["surname"] ;
			$array[$count]["preferredName"]=$row["preferredName"] ;
			$array[$count]["homeAddress"]=$row["homeAddress"] ;
			$array[$count]["homeAddressDistrict"]=$row["homeAddressDistrict"] ;
			$array[$count]["homeAddressCountry"]=$row["homeAddressCountry"] ;
			$count++ ;
		}

		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Family") ;
				print "</th>" ;
				print "<th>" ;
					print _("Selected Students") ;
				print "</th>" ;
				print "<th>" ;
					print _("Home Address") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0 ;
			$rowNum="odd" ;
			$students="" ;
			for ($i=0; $i<count($array); $i++) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				
				$current=$array[$i]["gibbonFamilyID"] ;
				$next="" ;
				if (isset($array[($i+1)]["gibbonFamilyID"])) {
					$next=$array[($i+1)]["gibbonFamilyID"] ;
				}
				if ($current==$next) {
					$students.=formatName("", $array[$i]["preferredName"], $array[$i]["surname"], "Student") . "<br/>" ;
				}
				else {
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $array[$i]["name"] ;
						print "</td>" ;
						print "<td>" ;
							print $students ;
							print formatName("", $array[$i]["preferredName"], $array[$i]["surname"], "Student") . "<br/>" ;
						print "</td>" ;
						print "<td>" ;
							//Print Name
							if ($array[$i]["nameAddress"]!="") {
								print $array[$i]["nameAddress"] . "<br/>" ;
							}
							else if ($array[$i]["name"]!="") {
								print $array[$i]["name"] . "<br/>" ;
							}
							
							//Print address
							$addressBits=explode(",", trim($array[$i]["homeAddress"])) ;
							$addressBits=array_diff($addressBits, array(""));
							$charsInLine=0 ;
							$buffer="" ;
							foreach ($addressBits as $addressBit) {
								if ($buffer=="") {
									$buffer=$addressBit ;
								}
								else {
									if (strlen($buffer . ", " . $addressBit)>26) {
										print $buffer . "<br/>" ; 
										$buffer=$addressBit ;
									}
									else {
										$buffer.=", " . $addressBit ;
									}
								}
							}
							print $buffer . "<br/>" ;
							
							//Print district and country
							if ($array[$i]["homeAddressDistrict"]!="") {
								print $array[$i]["homeAddressDistrict"] . "<br/>" ;
							}
							if ($array[$i]["homeAddressCountry"]!="") {
								print $array[$i]["homeAddressCountry"] . "<br/>";
							}
						print "</td>" ;
					print "</tr>" ;
					$students="" ;
					$count++ ;
				}
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=3>" ;
						print _("There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>