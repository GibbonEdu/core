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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/daysOfWeek_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('External Assessment Settings') . "</div>" ;
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
			$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	$primaryExternalAssessmentByYearGroup=unserialize(getSettingByScope($connection2, "School Admin", "primaryExternalAssessmentByYearGroup")) ;
	//Let's go!
	?>
	<h2>
		<? print _('Primary External Assessement') ; ?>
	</h2>
	<p>
		<? print _('These settings allow a particular type of external assessment to be associated with each year group. The selected assessment wil be used as the primary assessment to be used as a baseline for comparison (for example, within the Markbook). In addition, a particular field category can be chosen from which to draw data (if no category is chosen, the system will try to pick the best data automatically).') ; ?>
	</p>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessmentSettingsProcess.php"?>">
		<?
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Year Group") ;
				print "</th>" ;
				print "<th>" ;
					print _("External Assessment") ;
				print "</th>" ;
				print "<th>" ;
					print _("Field Set") ;
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
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["name"] ;
						print "<input type='hidden' name='gibbonYearGroupID[]' value='" . $row["gibbonYearGroupID"] . "'>" ;
					print "</td>" ;
					print "<td>" ;
						print "<select style='float: none; width: 270px' name='gibbonExternalAssessmentID[]' id='gibbonExternalAssessmentID$count'>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							print "<option value=''></option>" ;
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonExternalAssessmentID"]==substr($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],0,strpos($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],"-"))) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowSelect["gibbonExternalAssessmentID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}			
						print "</select>" ;
					print "</td>" ;
					print "<td>" ;
						print "<select style='float: none; width: 270px' name='category[]' id='category$count'>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT DISTINCT gibbonExternalAssessment.gibbonExternalAssessmentID, category FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE active='Y' ORDER BY gibbonExternalAssessmentID, category" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							print "<option value=''></option>" ;
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonExternalAssessmentID"]==substr($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],0,strpos($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],"-")) AND $rowSelect["category"]==substr($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],(strpos($primaryExternalAssessmentByYearGroup[$row["gibbonYearGroupID"]],"-")+1))) {
									$selected="selected" ;
								}
								print "<option $selected class='" . $rowSelect["gibbonExternalAssessmentID"] . "' value='" . $rowSelect["category"] . "'>" . htmlPrep(substr($rowSelect["category"], (strpos($rowSelect["category"],"_")+1))) . "</option>" ;
							}			
						print "</select>" ;
						?>
						<script type="text/javascript">
							$("#category<? print $count ?>").chainedTo("#gibbonExternalAssessmentID<? print $count ?>");
						</script>
						<?
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
			?>
			<tr>
				<td class="right" colspan=3>
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<? print _("Submit") ; ?>">
				</td>
			</tr>
			<?
		print "</table>" ;
		?>
	</form>
	<?
}
?>