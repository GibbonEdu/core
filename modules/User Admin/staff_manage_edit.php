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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/staff_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/staff_manage.php'>" . _('Manage Staff') . "</a> > </div><div class='trailEnd'>" . _('Edit Staff') . "</div>" ;
	print "</div>" ;
	
	$allStaff="" ;
	if (isset($_GET["allStaff"])) {
		$allStaff=$_GET["allStaff"] ;
	}
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
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
			$updateReturnMessage=_("Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonStaffID=$_GET["gibbonStaffID"] ;
	if ($gibbonStaffID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonStaffID"=>$gibbonStaffID); 
			$sql="SELECT gibbonStaff.*, surname, preferredName, initials FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="" OR $allStaff!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/staff_manage.php&search=$search&allStaff=$allStaff'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/staff_manage_editProcess.php?gibbonStaffID=" . $row["gibbonStaffID"] . "&search=$search&allStaff=$allStaff" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Person') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="person" id="person" maxlength=255 value="<?php print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", false, true) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Initials') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Must be unique if set.') ?></i></span>
						</td>
						<td class="right">
							<input name="initials" id="initials" maxlength=4 value="<?php print $row["initials"] ?>" type="text" style="width: 300px">
							<?php
							$idList="" ;
							try {
								$dataSelect=array("initials"=>$row["initials"]); 
								$sqlSelect="SELECT initials FROM gibbonStaff WHERE NOT initials=:initials ORDER BY initials" ;
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
							<b><?php print _('Type') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<option <?php if ($row["type"]=="Teaching") { print "selected " ;} ?>value="Teaching">Teaching</option>
								<option <?php if ($row["type"]=="Support") { print "selected " ;}?>value="Support">Support</option>
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Job Title') ?></b><br/>
						</td>
						<td class="right">
							<input name="jobTitle" id="jobTitle" maxlength=100 value="<?php print htmlPrep($row["jobTitle"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('First Aid') ?></h3>
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
							<b><?php print _('First Aid Qualified?') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="firstAidQualified" id="firstAidQualified" class="firstAidQualified">
								<option <?php if ($row["firstAidQualified"]=="") { print "selected" ; } ?> value=""></option>
								<option <?php if ($row["firstAidQualified"]=="Y") { print "selected" ; } ?> value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["firstAidQualified"]=="N") { print "selected" ; } ?> value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr id='firstAidExpiryRow' <?php if ($row["firstAidQualified"]!="Y") { print "style='display: none'" ; } ?>>
						<td> 
							<b><?php print _('First Aid Expiry') ?></b><br/>
							<span style="font-size: 90%"><i>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
						</td>
						<td class="right">
							<input name="firstAidExpiry" id="firstAidExpiry" maxlength=10 value="<?php print dateConvertBack($guid, $row["firstAidExpiry"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								$(function() {
									$( "#firstAidExpiry" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Biography') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Country Of Origin') ?></b><br/>
						</td>
						<td class="right">
							<select name="countryOfOrigin" id="countryOfOrigin" style="width: 302px">
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
									$selected="" ;
									if ($rowSelect["printable_name"]==$row["countryOfOrigin"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Qualifications') ?></b><br/>
						</td>
						<td class="right">
							<input name="qualifications" id="qualifications" maxlength=100 value="<?php print htmlPrep($row["qualifications"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Grouping') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Used to group staff when creating a staff directory.') ?></i></span>
						</td>
						<td class="right">
							<input name="biographicalGrouping" id="biographicalGrouping" maxlength=100 value="<?php print htmlPrep($row["biographicalGrouping"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Grouping Priority') ?></b><br/>
							<span style="font-size: 90%"><?php print _('<i>Higher numbers move teachers up the order within their grouping.') ?></i></span>
						</td>
						<td class="right">
							<input name="biographicalGroupingPriority" id="biographicalGroupingPriority" maxlength=4 value="<?php print htmlPrep($row["biographicalGroupingPriority"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var biographicalGroupingPriority=new LiveValidation('biographicalGroupingPriority');
								biographicalGroupingPriority.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Biography') ?></b><br/>
						</td>
						<td class="right">
							<textarea name='biography' id='biography' rows=10 style='width: 300px'><?php print htmlPrep($row["biography"]) ?></textarea>
						</td>
					</tr>
			
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>