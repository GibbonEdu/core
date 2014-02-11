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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/role_manage.php'>Manage Role</a> > </div><div class='trailEnd'>Add Role</div>" ;
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
		else if ($addReturn=="success0") {
			$addReturnMessage ="Your request was successful. You can now add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/role_manage_addProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Category *</b><br/>
				</td>
				<td class="right">
					<select name="category" id="category" style="width: 302px">
						<option value="Please select...">Please select...</option>
						<option value="Staff">Staff</option>
						<option value="Student">Student</option>
						<option value="Parent">Parent</option>
						<option value="Other">Other</option>
					</select>
					<script type="text/javascript">
						var category=new LiveValidation('category');
						category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Role Name *</b><br/>
					<span style="font-size: 90%"><i>Needs to be unique.</i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=20 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name=new LiveValidation('name');
						name.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<td> 
					<b>Short Name *</b><br/>
					<span style="font-size: 90%"><i>Needs to be unique.</i></span>
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
					<b>Description *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="description" id="description" maxlength=60 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var description=new LiveValidation('description');
						description.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<td> 
					<b>Type *</b><br/>
					<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
				</td>
				<td class="right">
					<input name="type" id="type" readonly="readonly" maxlength=20 value="Additional" type="text" style="width: 300px">
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