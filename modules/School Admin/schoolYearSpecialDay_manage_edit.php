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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearSpecialDay_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . _('Manage Special Days') . "</a> > </div><div class='trailEnd'>" . _('Edit Special Day') . "</div>" ;
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
	$gibbonSchoolYearSpecialDayID=$_GET["gibbonSchoolYearSpecialDayID"] ;
	if ($gibbonSchoolYearSpecialDayID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearSpecialDayID"=>$gibbonSchoolYearSpecialDayID); 
			$sql="SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearSpecialDayID=:gibbonSchoolYearSpecialDayID" ;
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
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/schoolYearSpecialDay_manage_editProcess.php?gibbonSchoolYearSpecialDayID=$gibbonSchoolYearSpecialDayID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] ?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Must be unique.')?> <?php print _('This value cannot be changed.') ?></i></span>
					</td>
					<td class="right">
						<input readonly name="date" id="date" maxlength=10 value="<?php print dateConvertBack($guid, $row["date"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var date=new LiveValidation('date');
							date.add(Validate.Presence);
							date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Type') ?> *</b>
					</td>
					<td class="right">
						<select name="type" id="type" style="width: 302px">
							<option value="Please select..."><?php print _('Please select...') ?></option>
							<option <?php if ($row["type"]=="School Closure") { print "selected " ; } ?>value="School Closure"><?php print _('School Closure') ?></option>
							<option <?php if ($row["type"]=="Timing Change") { print "selected " ; } ?>value="Timing Change"><?php print _('Timing Change') ?></option>
						</select>
						<script type="text/javascript">
							var type=new LiveValidation('type');
							type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Name') ?> *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=20 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var name2=new LiveValidation('name');
							name2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Description') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input name="description" id="description" maxlength=255 value="<?php print htmlPrep($row["description"]) ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('School Opens') ?></b>
					</td>
					<td class="right">
						<select style="width:100px" name="schoolOpenM" id="schoolOpenM">
							<?php
							print "<option value='Minutes'>" . _('Minutes') . "</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolOpen"],3,2)==$i AND $row["schoolOpen"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="schoolOpenH" id="schoolOpenH">
							<?php
							print "<option value='Hours'>" . _('Hours') . "</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolOpen"],0,2)==$i AND $row["schoolOpen"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('School Starts') ?></b>
					</td>
					<td class="right">
						<select style="width:100px" name="schoolStartM" id="schoolStartM">
							<?php
							print "<option value='Minutes'>" . _('Minutes') . "</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolStart"],3,2)==$i AND $row["schoolStart"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="schoolStartH" id="schoolStartH">
							<?php
							print "<option value='Hours'>" . _('Hours') . "</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolStart"],0,2)==$i AND $row["schoolStart"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('School Ends') ?></b>
					</td>
					<td class="right">
						<select style="width:100px" name="schoolEndM" id="schoolEndM">
							<?php
							print "<option value='Minutes'>" . _('Minutes') . "</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolEnd"],3,2)==$i AND $row["schoolEnd"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="schoolEndH" id="schoolEndH">
							<?php
							print "<option value='Hours'>" . _('Hours') . "</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolEnd"],0,2)==$i AND $row["schoolEnd"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('School Closes') ?></b>
					</td>
					<td class="right">
						<select style="width:100px" name="schoolCloseM" id="schoolCloseM">
							<?php
							print "<option value='Minutes'>" . _('Minutes') . "</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolClose"],3,2)==$i AND $row["schoolClose"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="schoolCloseH" id="schoolCloseH">
							<?php
							print "<option value='Hours'>" . _('Hours') . "</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
								
								if (substr($row["schoolClose"],0,2)==$i AND $row["schoolClose"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $_GET["gibbonSchoolYearID"] ?>" type="hidden">
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