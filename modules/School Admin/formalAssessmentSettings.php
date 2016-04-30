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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/formalAssessmentSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Formal Assessment Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/formalAssessmentSettingsProcess.php"?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=3> 
					<h3><?php print __($guid, 'Internal Assessment Settings') ; ?></h3>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Formal Assessment' AND name='internalAssessmentTypes'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></span>
				</td>
				<td class="right" colspan=2>
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" type="text" class="standardWidth" rows=4><?php if (isset($row["value"])) { print $row["value"] ; } ?></textarea>
					<script type="text/javascript">
						var <?php print $row["name"] ?>=new LiveValidation('<?php print $row["name"] ?>');
						<?php print $row["name"] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=3> 
					<h3><?php print __($guid, 'Primary External Assessement') ; ?></h3>
					<?php print __($guid, 'These settings allow a particular type of external assessment to be associated with each year group. The selected assessment will be used as the primary assessment to be used as a baseline for comparison (for example, within the Markbook). In addition, a particular field category can be chosen from which to draw data (if no category is chosen, the system will try to pick the best data automatically).') ; ?>
				</td>
			</tr>
			
	
			<?php
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

			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Year Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "External Assessment") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Field Set") ;
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
						print __($guid, $row["name"]) ;
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
								print "<option $selected value='" . $rowSelect["gibbonExternalAssessmentID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
							}			
						print "</select>" ;
					print "</td>" ;
					print "<td>" ;
						print "<select style='float: none; width: 270px' name='category$count' id='category$count'>" ;
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
								print "<option $selected class='" . $rowSelect["gibbonExternalAssessmentID"] . "' value='" . $rowSelect["category"] . "'>" . htmlPrep(__($guid, substr($rowSelect["category"], (strpos($rowSelect["category"],"_")+1)))) . "</option>" ;
							}			
						print "</select>" ;
						?>
						<script type="text/javascript">
							$("#category<?php print $count ?>").chainedTo("#gibbonExternalAssessmentID<?php print $count ?>");
						</script>
						<?php
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
			?>
			<tr>
				<td>
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right" colspan=2>
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
			<?php
		print "</table>" ;
		?>
	</form>
	<?php
}
?>