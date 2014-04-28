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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttColumn_edit_row_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonTTColumnID=$_GET["gibbonTTColumnID"] ;
	
	if ($gibbonTTColumnID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTColumnID"=>$gibbonTTColumnID); 
			$sql="SELECT name AS columnName FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/ttColumn.php'>" . _('Manage Columns') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/ttColumn_edit.php&gibbonTTColumnID=$gibbonTTColumnID'>" . _('Edit Column') . "</a> > </div><div class='trailEnd'>" . _('Add Column Row') . "</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_edit_row_addProcess.php" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print _('Column') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="columnName" id="columnName" maxlength=20 value="<?php print $row["columnName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var courseName=new LiveValidation('courseName');
								courseName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<?php print "<b>" . _('Name') . " *</b><br/>" ; ?>
							<span style="font-size: 90%"><i><?php print _('Must be unique for this column.') ?></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=12 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<?php print "<b>" . _('Short Name') . " *</b><br/>" ; ?>
							<span style="font-size: 90%"><i><?php print _('Must be unique for this column.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Start Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeStart FROM gibbonTTColumnRow ORDER BY timeStart" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . substr($rowAuto["timeStart"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeStart" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('End Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeEnd=new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeEnd FROM gibbonTTColumnRow ORDER BY timeEnd" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {											
											print "\"" . substr($rowAuto["timeEnd"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeEnd" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Type</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="type">
								<?php
								print "<option value='Lesson'>" . _('Lesson') . "</option>" ;
								print "<option value='Pastoral'>" . _('Pastoral') . "</option>" ;
								print "<option value='Sport'>" . _('Sport') . "</option>" ;
								print "<option value='Break'>" . _('Break') . "</option>" ;
								print "<option value='Service'>" . _('Service') . "</option>" ;
								print "<option value='Other'>" . _('Other') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonTTColumnID" id="gibbonTTColumnID" value="<?php print $gibbonTTColumnID ?>" type="hidden">
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