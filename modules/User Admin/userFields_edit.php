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
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/userFields.php'>" . __($guid, 'Manage Custom Fields') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Custom Field') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonPersonFieldID=$_GET["gibbonPersonFieldID"] ;
	if ($gibbonPersonFieldID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/userFields_editProcess.php?gibbonPersonFieldID=" . $row["gibbonPersonFieldID"] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name2" maxlength=50 value="<?php print $row["name"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name2');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Active') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="active">
								<?php
								print "<option " ; if ($row["active"]=="Y") { print "selected" ; } print " value='Y'>" . __($guid, 'Yes') . "</option>" ;
								print "<option " ; if ($row["active"]=="N") { print "selected" ; } print " value='N'>" . __($guid, 'No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Description') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=255 value="<?php print $row["description"] ?>" type="text" class="standardWidth">
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
							<b><?php print __($guid, 'Type') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="type" id="type" class="type">
								<?php
									print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
									print "<option " ; if ($row["type"]=="varchar") { print "selected" ; } print " value='varchar'>Short Text (max 255 characters)</option>" ;
									print "<option " ; if ($row["type"]=="text") { print "selected" ; } print " value='text'>Long Text</option>" ;
									print "<option " ; if ($row["type"]=="date") { print "selected" ; } print " value='date'>Date</option>" ;
									print "<option " ; if ($row["type"]=="url") { print "selected" ; } print " value='url'>Link</option>" ;
									print "<option " ; if ($row["type"]=="select") { print "selected" ; } print " value='select'>Dropdown</option>" ;
								?>				
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr id="optionsRow">
						<td> 
							<b><?php print __($guid, 'Options') ?> *</b><br/>
							<span class="emphasis small">
								<?php 
									print __($guid, 'Short Text: number of characters, up to 255.') . "<br/>" ;
									print __($guid, 'Long Text: number of rows for field.') . "<br/>" ;
									print __($guid, 'Dropdown: comma separated list of options.') . "<br/>" ;	
								?>
								</span>
						</td>
						<td class="right">
							<textarea name="options" id="options" class="standardWidth" rows='3'><?php print $row["options"] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Required') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Is this field compulsory?') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="required">
								<?php
								print "<option " ; if ($row["required"]=="Y") { print "selected" ; } print " value='Y'>" . __($guid, 'Yes') . "</option>" ;
								print "<option " ; if ($row["required"]=="N") { print "selected" ; } print " value='N'>" . __($guid, 'No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Role Categories') ?></b><br/>
						</td>
						<td class="right">
							<?php
								print __($guid, "Student") . " <input " ; if ($row["activePersonStudent"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonStudent' value='1'/><br/>" ;
								print __($guid, "Staff") . " <input " ; if ($row["activePersonStaff"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonStaff' value='1'/><br/>" ;
								print __($guid, "Parent") . " <input " ; if ($row["activePersonParent"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonParent' value='1'/><br/>" ;
								print __($guid, "Other") . " <input " ; if ($row["activePersonOther"]=="1") { print "checked" ; } print " type='checkbox' name='activePersonOther' value='1'/><br/>" ;
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Include In Data Updater?') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="activeDataUpdater">
								<?php
								print "<option " ; if ($row["activeDataUpdater"]=="1") { print "selected" ; } print " value='1'>" . __($guid, 'Yes') . "</option>" ;
								print "<option " ; if ($row["activeDataUpdater"]=="0") { print "selected" ; } print " value='0'>" . __($guid, 'No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Include In Application Form?') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="activeApplicationForm">
								<?php
								print "<option " ; if ($row["activeApplicationForm"]=="1") { print "selected" ; } print " value='1'>" . __($guid, 'Yes') . "</option>" ;
								print "<option " ; if ($row["activeApplicationForm"]=="0") { print "selected" ; } print " value='0'>" . __($guid, 'No') . "</option>" ;
								?>				
							</select>
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
	}
}
?>