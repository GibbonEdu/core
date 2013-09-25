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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/studentsSettings_noteCategory_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/studentsSettings.php'>Manage Students Settings</a> > </div><div class='trailEnd'>Add Note Category</div>" ;
	print "</div>" ;
	
	$addReturn = $_GET["addReturn"] ;
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
			$addReturnMessage ="Add failed because some values need to be unique but were not." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Add failed because the passwords did not match." ;	
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
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentsSettings_noteCategory_addProcess.php" ?>">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b>Name *</b><br/>
					<span style="font-size: 90%"><i>Must be unique.</i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=30 value="<? print $row["name"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var name = new LiveValidation('name');
						name.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Active *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="active" id="active" style="width: 302px">
						<option <? if ($row["active"]=="Y") { print "selected" ; } ?> value="Y">Y</option>
						<option <? if ($row["active"]=="N") { print "selected" ; } ?> value="N">N</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Template</b><br/>
					<span style="font-size: 90%"><i>HTML code to be inserted into blank note.</i></span>
				</td>
				<td class="right">
					<textarea name="template" id="template" rows=8 style="width: 300px"><? print $row["template"] ?></textarea>
				</td>
			</tr>
			<tr>
				<td class="right" colspan=2>
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="reset" value="Reset"> <input type="submit" value="Submit">
				</td>
			</tr>
			<tr>
				<td class="right" colspan=2>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
			</tr>
		</table>
	</form>
	<?
}
?>