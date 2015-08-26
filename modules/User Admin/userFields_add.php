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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/userFields_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/userFields.php'>" . _('Manage Custom Fields') . "</a> > </div><div class='trailEnd'>" . _('Add Custom Field') . "</div>" ;
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
			$addReturnMessage=_("Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage="Your request failed because your passwords did not match." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/userFields_addProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b><?php print _('Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name2" maxlength=50 value="" type="text" style="width: 300px">
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
						print "<option value='Y'>" . _('Yes') . "</option>" ;
						print "<option value='N'>" . _('No') . "</option>" ;
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Description') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="description" id="description" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var description=new LiveValidation('description');
						description.add(Validate.Presence);
					</script>
				</td>
			</tr>
			
			<script type="text/javascript">
				$(document).ready(function(){
					$("#optionsRow").css("display","none");
						
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
							print "<option value='varchar'>Short Text (max 255 characters)</option>" ;
							print "<option value='text'>Long Text</option>" ;
							print "<option value='date'>Date</option>" ;
							print "<option value='url'>Link</option>" ;
							print "<option value='select'>Dropdown</option>" ;
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
					<textarea name="options" id="options" style="width: 300px" rows='3'></textarea>
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
						print "<option value='Y'>" . _('Yes') . "</option>" ;
						print "<option value='N'>" . _('No') . "</option>" ;
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
						print _("Student") . " <input checked type='checkbox' name='activePersonStudent' value='1'/><br/>" ;
						print _("Staff") . " <input type='checkbox' name='activePersonStaff' value='1'/><br/>" ;
						print _("Parent") . " <input type='checkbox' name='activePersonParent' value='1'/><br/>" ;
						print _("Other") . " <input type='checkbox' name='activePersonOther' value='1'/><br/>" ;
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
						print "<option value='1'>" . _('Yes') . "</option>" ;
						print "<option value='0'>" . _('No') . "</option>" ;
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
						print "<option value='1'>" . _('Yes') . "</option>" ;
						print "<option selected value='0'>" . _('No') . "</option>" ;
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
?>