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

if (isActionAccessible($guid, $connection2, "/modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Parent Weekly Email Summary') . "</div>" ;
	print "</div>" ;
	print "<p>" ;
	print _("This report shows responses to the weekly summary email, organised by calendar week and role group.") ;
	print "</p>" ;
	
	print "<h2>" ;
	print _("Choose Roll Group & Week") ;
	print "</h2>" ;
	
	$gibbonRollGroupID=NULL ;
	if (isset($_GET["gibbonRollGroupID"])) {
		$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	}
	$weekOfYear=NULL ;
	if (isset($_GET["weekOfYear"])) {
		$weekOfYear=$_GET["weekOfYear"] ;
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Roll Group') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonRollGroupID">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonRollGroupID==$rowSelect["gibbonRollGroupID"]) {
								print "<option selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Calendar Week') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="weekOfYear">
						<?php
						print "<option value=''></option>" ;
						for ($i=0; $i<10; $i++) {
							if ($weekOfYear==date('W', strtotime("-$i week"))) {
								print "<option selected value='" . date('W', strtotime("-$i week")) . "'>" . date('W', strtotime("-$i week")) . "</option>" ;
							}
							else {
								print "<option value='" . date('W', strtotime("-$i week")) . "'>" . date('W', strtotime("-$i week")) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_parentWeeklyEmailSummaryConfirmation.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonRollGroupID!="") {
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
			$sql="SELECT student.surname AS studentSurname, student.preferredName AS studentPreferredName, parent.surname AS parentSurname, parent.preferredName AS parentPreferredName, parent.title AS parentTitle, gibbonRollGroup.name, student.gibbonPersonID AS gibbonPersonIDStudent, parent.gibbonPersonID AS gibbonPersonIDParent FROM gibbonPerson AS student JOIN gibbonStudentEnrolment ON (student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) LEFT JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE (gibbonFamilyAdult.contactPriority=1 OR gibbonFamilyAdult.contactPriority IS NULL) AND student.status='Full' AND parent.status='Full' AND (student.dateStart IS NULL OR student.dateStart<='" . date("Y-m-d") . "') AND (student.dateEnd IS NULL OR student.dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY student.surname, student.preferredName, parent.surname, parent.preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Student") ;
				print "</th>" ;
				print "<th>" ;
					print _("Parent") ;
				print "</th>" ;
				print "<th>" ;
					print _("Sent") ;
				print "</th>" ;
				print "<th>" ;
					print _("Confirmed") ;
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
						print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonIDStudent"] . "&subpage=Homework'>" . formatName("", $row["studentPreferredName"], $row["studentSurname"], "Student", true) . "</a>" ;
					print "</td>" ;
					print "<td>" ;
						print formatName($row["parentTitle"], $row["parentPreferredName"], $row["parentSurname"], "Parent", true)  ;
					print "</td>" ;
					print "<td style='width:15%'>" ;
						try {
							$dataData=array("gibbonPersonIDStudent"=>$row["gibbonPersonIDStudent"], "gibbonPersonIDParent"=>$row["gibbonPersonIDParent"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "weekOfYear"=>$weekOfYear); 
							$sqlData="SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND gibbonSchoolYearID=:gibbonSchoolYearID AND weekOfYear=:weekOfYear" ;
							$resultData=$connection2->prepare($sqlData);
							$resultData->execute($dataData);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultData->rowCount()==1) {
							$rowData=$resultData->fetch() ;
							print "<img title='" . _('Sent') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
							
						}
						else {
							$rowData=NULL ;
							print "<img title='" . _('Not Sent') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
						}
					print "</td>" ;
					print "<td style='width:15%'>" ;
						if (is_null($rowData)) {
							print _("NA") ;
						}
						else {
							if ($rowData["confirmed"]=="Y") {
								print "<img title='" . _('Confirmed') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
							}
							else {
								print "<img title='" . _('Not Confirmed') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
							}
						}
					print "</td>" ;
				print "</tr>" ;
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=4>" ;
						print _("There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>