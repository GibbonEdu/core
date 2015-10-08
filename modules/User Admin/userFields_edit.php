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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/userFields_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/userFields.php'>" . _('Manage Custom Fields') . "</a> > </div><div class='trailEnd'>" . _('Edit Custom Field') . "</div>" ;
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
	$gibbonPersonFieldID=$_GET["gibbonPersonFieldID"] ;
	if ($gibbonPersonFieldID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonFieldID"=>$gibbonPersonFieldID); 
			$sql="SELECT * FROM gibbonPersonField WHERE gibbonPersonFieldID=:gibbonPersonFieldID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/userFields_editProcess.php?gibbonPersonFieldID=" . $row["gibbonPersonFieldID"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print _('Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name2" maxlength=50 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name2');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="active">
								<?php
								print "<option " ; if ($row["active"]=="Y") { print "selected" ; } print " value='Y'>" . _('Yes') . "</option>" ;
								print "<option " ; if ($row["active"]=="N") { print "selected" ; } print " value='N'>" . _('No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Description') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=255 value="<?php print $row["description"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var description=new LiveValidation('description');
								description.add(Validate.Presence);
							</script>
						</td>
					</tr>
			
					<script type="text/javascript">
						$(document).ready(function(){
							<?php
								if ($row["type"]!="varchar" AND $row["type"]!="text" AND $row["type"]!="select") {
									print "$(\"#optionsRow\").css(\"display\",\"none\");" ;
								}
							?>
							
							$("#type").change(function(){
								//varchar = chars
								//text = rows
								//select = csl of options
								if ($('select.type option:selected').val()=="varchar" || $('select.type option:selected').val()=="text" || $('select.type option:selected').val()=="select") {
									$("#optionsRow").slideDown("fast", $("#optionsRow").css("display","table-row")); 
								}
								else {
									$("#optionsRow").css("display","none");
								}
							 });
						});
					</script>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Type') ?> *</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="type" id="type" class="type">
								<?php
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
									print "<option " ; if ($row["type"]=="varchar") { print "selected" ; } print " value='varchar'>Short Text (max 255 characters)</option>" ;
									print "<option " ; if ($row["type"]=="text") { print "selected" ; } print " value='text'>Long Text</option>" ;
									print "<option " ; if ($row["type"]=="date") { print "selected" ; } print " value='date'>Date</option>" ;
									print "<option " ; if ($row["type"]=="url") { print "selected" ; } print " value='url'>Link</option>" ;
									print "<option " ; if ($row["type"]=="select") { print "selected" ; } print " value='select'>Dropdown</option>" ;
								?>				
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr id="optionsRow">
						<td> 
							<b><?php print _('Options') ?> *</b><br/>
							<span style="font-size: 90%"><i>
								<?php 
									print _('Short Text: number of characters, up to 255.') . "<br/>" ;
									print _('Long Text: number of rows for field.') . "<br/>" ;
									print _('Dropdown: comma separated list of options.') . "<br/>" ;	
								?>
								</i></span>
						</td>
						<td class="right">
							<textarea name="options" id="options" style="width: 300px" rows='3'><?php print $row["options"] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Required') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Is this field compulsory?') ?></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="required">
								<?php
								print "<option " ; if ($row["required"]=="Y") { print "selected" ; } print " value='Y'>" . _('Yes') . "</option>" ;
								print "<option " ; if ($row["required"]=="N") { print "selected" ; } print " value='N'>" . _('No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Role Categories') ?></b><br/>
						</td>
						<td class="right">
							<?php
								print _("Student") . " <input " ; if ($row["activePersonStudent"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonStudent' value='1'/><br/>" ;
								print _("Staff") . " <input " ; if ($row["activePersonStaff"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonStaff' value='1'/><br/>" ;
								print _("Parent") . " <input " ; if ($row["activePersonParent"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonParent' value='1'/><br/>" ;
								print _("Other") . " <input " ; if ($row["activePersonOther"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonOther' value='1'/><br/>" ;
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Include In Data Updater?') ?> *</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="activeDataUpdater">
								<?php
								print "<option " ; if ($row["activeDataUpdater"]=="1") { print "selected" ; } print " value='1'>" . _('Yes') . "</option>" ;
								print "<option " ; if ($row["activeDataUpdater"]=="0") { print "selected" ; } print " value='0'>" . _('No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Include In Application Form?') ?> *</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="activeApplicationForm">
								<?php
								print "<option " ; if ($row["activeApplicationForm"]=="1") { print "selected" ; } print " value='1'>" . _('Yes') . "</option>" ;
								print "<option " ; if ($row["activeApplicationForm"]=="0") { print "selected" ; } print " value='0'>" . _('No') . "</option>" ;
								?>				
							</select>
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