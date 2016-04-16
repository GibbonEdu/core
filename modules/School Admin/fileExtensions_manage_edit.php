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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/fileExtensions_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/fileExtensions_manage.php'>" . __($guid, 'Manage File Extensions') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit File Extensions') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonFileExtensionID=$_GET["gibbonFileExtensionID"] ;
	if ($gibbonFileExtensionID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFileExtensionID"=>$gibbonFileExtensionID); 
			$sql="SELECT * FROM gibbonFileExtension WHERE gibbonFileExtensionID=:gibbonFileExtensionID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/fileExtensions_manage_editProcess.php?gibbonFileExtensionID=$gibbonFileExtensionID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Extension') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Must be unique.') ?></i></span>
						</td>
						<td class="right">
							<input name="extension" id="extension" maxlength=7 value="<?php print $row["extension"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var extension=new LiveValidation('extension');
								extension.add(Validate.Presence);
							</script> 
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=50 value="<?php print __($guid, $row["name"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script> 
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
								<option <?php if ($row["type"]=="Document") { print "selected" ; } ?> value="Document"><?php print __($guid, 'Document') ?></option>
								<option <?php if ($row["type"]=="Spreadsheet") { print "selected" ; } ?> value="Spreadsheet"><?php print __($guid, 'Spreadsheet') ?></option>
								<option <?php if ($row["type"]=="Presentation") { print "selected" ; } ?> value="Presentation"><?php print __($guid, 'Presentation') ?></option>
								<option <?php if ($row["type"]=="Graphics/Design") { print "selected" ; } ?> value="Graphics/Design"><?php print __($guid, 'Graphics/Design') ?></option>
								<option <?php if ($row["type"]=="Video") { print "selected" ; } ?> value="Video"><?php print __($guid, 'Video') ?></option>
								<option <?php if ($row["type"]=="Audio") { print "selected" ; } ?> value="Audio"><?php print __($guid, 'Audio') ?></option>
								<option <?php if ($row["type"]=="Other") { print "selected" ; } ?> value="Other"><?php print __($guid, 'Other') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
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