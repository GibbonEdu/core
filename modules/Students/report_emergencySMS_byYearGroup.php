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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_emergencySMS_byYearGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Emergency SMS by Year Group</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report prints all parent mobile phone numbers, whether or not they are set to receive messages from the school. It is useful when send emergency SMS messages to groups of students. If no parent mobile is available it will display the emergency numbers given in the student record, and this will appear in red." ;
	print "</p>" ;
	
	print "<h2>" ;
	print "Choose Year Group" ;
	print "</h2>" ;
	
	$gibbonYearGroupID=NULL ;
	if (isset($_GET["gibbonYearGroupID"])) {
		$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	}
	$prefix=NULL ;
	if (isset($_GET["prefix"])) {
		$prefix=$_GET["prefix"] ;
	}
	$append=NULL ;
	if (isset($_GET["append"])) {
		$append=$_GET["append"] ;
	}
	$hideName=NULL ;
	if (isset($_GET["hideName"])) {
		$hideName=$_GET["hideName"] ;
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Year Group') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonYearGroupID">
						<?php
						print "<option value=''></option>" ;
						if ($gibbonYearGroupID=="*") {
							print "<option selected value='*'>All</option>" ;
						}
						else {
							print "<option value='*'>All</option>" ;
						}
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonYearGroupID==$rowSelect["gibbonYearGroupID"]) {
								print "<option selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Prefix</b><br/>
				</td>
				<td class="right">
					<input name='prefix' style='width: 302px' type='text' maxlength='30' value=<?php print $prefix ?>>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Append</b><br/>
				</td>
				<td class="right">
					<input name='append' style='width: 302px' type='text' maxlength='30' value=<?php print $append ?>>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Hide student name?</b><br/>
				</td>
				<td class="right">
					<?php
					$checked="" ;
					if ($hideName=="on") {
						$checked="checked " ;
					}
					?>
					<input <?php print $checked ?> type='checkbox' name='hideName'>
				</td>
			</tr>
			
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_emergencySMS_byYearGroup.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonYearGroupID!="") {
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		try {
			if ($gibbonYearGroupID=="*") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, emergency1Number1, emergency2Number1 FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
			}
			else {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$gibbonYearGroupID); 
				$sql="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, emergency1Number1, emergency2Number1 FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				if ($hideName!="on") {
					print "<th>" ;
						print "Student" ;
					print "</th>" ;
				}
				print "<th>" ;
					print "Parent Mobile Numbers" ;
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
					if ($hideName!="on") {
						print "<td>" ;
							print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
						print "</td>" ;
					}
					print "<td>" ;
						try {
							$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
							$sqlFamily="SELECT gibbonPerson.* FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID) AND (phone1Type='Mobile' OR phone2Type='Mobile' OR phone3Type='Mobile' OR phone4Type='Mobile') AND status='Full'";
							$resultFamily=$connection2->prepare($sqlFamily);
							$resultFamily->execute($dataFamily);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultFamily->rowCount()>0) {
							while ($rowFamily=$resultFamily->fetch()) {
								for ($i=1; $i<5; $i++) {
									if ($rowFamily["phone" . $i]!="" AND $rowFamily["phone" . $i . "Type"]=="Mobile") {
										print $prefix . preg_replace( '/\s+/', '', $rowFamily["phone" . $i]) . $append . "<br/>" ;
									}
								}
							}
						}
						else {
							print "<span style='color: #c00'>" . $prefix . preg_replace( '/\s+/', '', $row["emergency1Number1"]) . $append . "</span><br/>" ;
							print "<span style='color: #c00'>" . $prefix . preg_replace( '/\s+/', '', $row["emergency2Number1"]) . $append . "</span><br/>" ;
						}
					print "</td>" ;
					
				print "</tr>" ;
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=2>" ;
						print _("There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>