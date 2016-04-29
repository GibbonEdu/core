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

if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$allStaff="" ;
	if (isset($_GET["allStaff"])) {
		$allStaff=$_GET["allStaff"] ;
	}
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>" . __($guid, 'Manage Staff') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Staff') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	if ($search!="" OR $allStaff!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>" . __($guid, 'Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/staff_manage_addProcess.php?search=$search&allStaff=$allStaff" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Basic Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Person') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>		
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonPersonID" id="gibbonPersonID">
						<?php
						print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
						try {
							$data=array(); 
							$sql="SELECT DISTINCT gibbonPerson.* FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE concat('%', gibbonRoleID, '%')) WHERE status='Full' AND gibbonRole.category='Staff' ORDER BY surname, preferredName" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($row=$result->fetch()) {
							print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], "Staff", true, true) . "</option>" ;
						}
						?>				
					</select>
					<script type="text/javascript">
						var gibbonPersonID=new LiveValidation('gibbonPersonID');
						gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Initials') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique if set.') ?></span>
				</td>
				<td class="right">
					<input name="initials" id="initials" maxlength=4 value="" type="text" class="standardWidth">
					<?php
					$idList="" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT initials FROM gibbonStaff ORDER BY initials" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$idList.="'" . $rowSelect["initials"]  . "'," ;
					}
					?>
					<script type="text/javascript">
						var initials=new LiveValidation('initials');
						initials.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "Initials already in use!", partialMatch: false, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class="standardWidth">
						<?php
						print "<option value=\"Please select...\">" . __($guid, 'Please select...') . "</option>" ;
						print "<optgroup label='--" . __($guid, 'Basic') . "--'>" ;
							print "<option value=\"Teaching\">" . __($guid, 'Teaching') . "</option>" ;
							print "<option value=\"Support\">" . __($guid, 'Support') . "</option>" ;
						print "</optgroup>" ;
						print "<optgroup label='--" . __($guid, 'System Roles') . "--'>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value=\"" . $rowSelect["name"] . "\">" . __($guid, $rowSelect["name"]) . "</option>" ;
							}
						print "</optgroup>" ;
						?>
					</select>
					<script type="text/javascript">
						var type=new LiveValidation('type');
						type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Job Title') ?></b><br/>
				</td>
				<td class="right">
					<input name="jobTitle" id="jobTitle" maxlength=100 value="" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'First Aid') ?></h3>
				</td>
			</tr>
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#firstAidQualified").change(function(){
						if ($('select.firstAidQualified option:selected').val()=="Y" ) {
							$("#firstAidExpiryRow").slideDown("fast", $("#firstAidExpiryRow").css("display","table-row")); 
						} else {
							$("#firstAidExpiryRow").css("display","none");
						} 
					 });
				});
			</script>
			<tr>
				<td> 
					<b><?php print __($guid, 'First Aid Qualified?') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="firstAidQualified" id="firstAidQualified" class="firstAidQualified">
						<option value=""></option>
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
						<option value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<tr id='firstAidExpiryRow' style='display: none'>
				<td> 
					<b><?php print __($guid, 'First Aid Expiry') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></span>
				</td>
				<td class="right">
					<input name="firstAidExpiry" id="firstAidExpiry" maxlength=10 value="<?php print dateConvertBack($guid, $row["firstAidExpiry"]) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						$(function() {
							$( "#firstAidExpiry" ).datepicker();
						});
					</script>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Biography') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Country Of Origin') ?></b><br/>
				</td>
				<td class="right">
					<select name="countryOfOrigin" id="countryOfOrigin" class="standardWidth">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(__($guid, $rowSelect["printable_name"])) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Qualifications') ?></b><br/>
				</td>
				<td class="right">
					<input name="qualifications" id="qualifications" maxlength=80 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Grouping') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, 'Used to group staff when creating a staff directory.') ?></span>
				</td>
				<td class="right">
					<input name="biographicalGrouping" id="biographicalGrouping" maxlength=100 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Grouping Priority') ?></b><br/>
					<span class="emphasis small"><?php print __($guid, 'Higher numbers move teachers up the order within their grouping.') ?></span>
				</td>
				<td class="right">
					<input name="biographicalGroupingPriority" id="biographicalGroupingPriority" maxlength=4 value="0" type="text" class="standardWidth">
					<script type="text/javascript">
						var biographicalGroupingPriority=new LiveValidation('biographicalGroupingPriority');
						biographicalGroupingPriority.add(Validate.Numericality);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Biography') ?></b><br/>
				</td>
				<td class="right">
					<textarea name='biography' id='biography' rows=10 style='width: 300px'></textarea>
				</td>
			</tr>
			
			<tr>
				<td>
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>