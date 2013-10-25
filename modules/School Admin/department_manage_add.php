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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_manage.php'>Manage Departments</a> > </div><div class='trailEnd'>Add Learning Area</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Add failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Add failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Add failed some values need to be unique but were not." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Add failed due to problems uploading one or more attachments." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Add was successful. You can add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_addProcess.php?address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($('select.type option:selected').val() == "Learning Area" ) {
							$("#roleAdminRow").css("display","none");
							$("#roleLARow").slideDown("fast", $("#roleLARow").css("display","table-row")); 
						} else if ($('select.type option:selected').val() == "Administration" ) {
							$("#roleLARow").css("display","none");
							$("#roleAdminRow").slideDown("fast", $("#roleAdminRow").css("display","table-row")); 
						} 
					 });
				});
			</script>
			<tr>
				<td> 
					<b>Type *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class='type' style="width: 300px">
						<option value='Learning Area'>Learning Area</option>
						<option value='Administration'>Administration</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Name *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=40 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name=new LiveValidation('name');
						name.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Short Name *</b><br/>
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
					<b>Subject Listing</b><br/>
				</td>
				<td class="right">
					<input name="subjectListing" id="subjectListing" maxlength=255 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b>Blurb</b> 
					<? print getEditor($guid,  TRUE, "blurb", "", 20 ) ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Logo</b><br/>
					<span style="font-size: 90%"><i>125x125px jpg/png/gif</i></span>
				</td>
				<td class="right">
					<input type="file" name="file" id="file"><br/><br/>
					<?
					print getMaxUpload() ;
					$ext="'.png','.jpeg','.jpg','.gif'" ;
					?>
					
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Staff</b><br/>
					<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
				</td>
				<td class="right">
					<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
						<?
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Staff", true, true) . "</option>" ;
						}
						?>
					</select>
				</td>
			<tr id='roleLARow'>
				<td> 
					<b>Role</b><br/>
				</td>
				<td class="right">
					<select name="roleLA" id="roleLA" style="width: 302px">
						<option value="Coordinator">Coordinator</option>
						<option value="Assistant Coordinator">Assistant Coordinator</option>
						<option value="Teacher (Curriculum)">Teacher (Curriculum)</option>
						<option value="Teacher">Teacher</option>
						<option value="Other">Other</option>
					</select>
				</td>
			</tr>
			<tr id='roleAdminRow' style='display: none'>
				<td> 
					<b>Role</b><br/>
				</td>
				<td class="right">
					<select name="roleAdmin" id="roleAdmin" style="width: 302px">
						<option value="Director">Director</option>
						<option value="Manager">Manager</option>
						<option value="Administrator">Administrator</option>
						<option value="Other">Other</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
				<td class="right">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
}
?>