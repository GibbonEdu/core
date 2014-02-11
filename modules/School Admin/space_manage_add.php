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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/space_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/space_manage.php'>Manage Spaces</a> > </div><div class='trailEnd'>Add Space</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Your request failed because your passwords did not match." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Your request was successful. You can now add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/space_manage_addProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Name *</b><br/>
					<span style="font-size: 90%"><i>Must be unique.</i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name=new LiveValidation('name');
						name.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Type *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" style="width: 302px">
						<option value="Classroom">Classroom</option>
						<option value="Performance">Performance</option>
						<option value="Hall">Hall</option>
						<option value="Outdoor">Outdoor</option>
						<option value="Undercover">Undercover</option>
						<option value="Storage">Storage</option>
						<option value="Office">Office</option>
						<option value="Staffroom">Staffroom</option>
						<option value="Study">Study</option>
						<option value="Library">Library</option>
						<option value="Other">Other</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>User 1</b>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonPersonID1">
						<?
						print "<option value=''></option>" ;
						try {
							$data=array(); 
							$sql="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($row=$result->fetch()) {
							print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", true, true) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>User 2</b>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonPersonID2">
						<?
						print "<option value=''></option>" ;
						try {
							$data=array(); 
							$sql="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($row=$result->fetch()) {
							print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", true, true) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Capacity</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="capacity" id="capacity" maxlength=5 value="0" type="text" style="width: 300px">
					<script type="text/javascript">
						var capacity=new LiveValidation('capacity');
						capacity.add(Validate.Numericality);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Teacher's Computer *</b><br/>
				</td>
				<td class="right">
					<select name="computer" id="computer" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Student Computers *</b><br/>
					<span style="font-size: 90%"><i>How many are there?</i></span>
				</td>
				<td class="right">
					<input name="computerStudent" id="computerStudent" maxlength=5 value="0" type="text" style="width: 300px">
					<script type="text/javascript">
						var computerStudent=new LiveValidation('computerStudent');
						computerStudent.add(Validate.Numericality);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Projector *</b><br/>
				</td>
				<td class="right">
					<select name="projector" id="projector" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b>TV *</b><br/>
				</td>
				<td class="right">
					<select name="tv" id="tv" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b>DVD Player *</b><br/>
				</td>
				<td class="right">
					<select name="dvd" id="dvd" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b>HiFi *</b><br/>
				</td>
				<td class="right">
					<select name="hifi" id="hifi" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b>Speakers *</b><br/>
				</td>
				<td class="right">
					<select name="speakers" id="speakers" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b>Interactive White Board *</b><br/>
				</td>
				<td class="right">
					<select name="iwb" id="iwb" style="width: 302px">
						<option value="N">N</option>
						<option value="Y">Y</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Extension</b><br/>
					<span style="font-size: 90%"><i>Room's internal phone number.</i></span>
				</td>
				<td class="right">
					<input name="phoneInternal" id="phoneInternal" maxlength=5 value="<? print $row["phoneInternal"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Phone Number</b><br/>
					<span style="font-size: 90%"><i>Room's external phone number.</i></span>
				</td>
				<td class="right">
					<input name="phoneExternal" id="phoneExternal" maxlength=20 value="<? print $row["phoneExternal"] ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>Comment</b><br/>
					<span style="font-size: 90%"><i>1000 character limit</i></span>
				</td>
				<td class="right">
					<textarea name="comment" id="comment" rows=8 style="width: 300px"><? print $row["comment"] ?></textarea>
					<script type="text/javascript">
						var comment=new LiveValidation('comment');
						comment.add( Validate.Length, { maximum: 1000 } );
					 </script>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
}
?>