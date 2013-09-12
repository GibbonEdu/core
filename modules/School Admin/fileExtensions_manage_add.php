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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/fileExtensions_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/fileExtensions_manage.php'>Manage File Extensions</a> > </div><div class='trailEnd'>Add File Extension</div>" ;
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
			$addReturnMessage ="Update failed some values need to be unique but were not." ;	
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
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/fileExtensions_manage_addProcess.php" ?>">
		<table style="width: 100%">	
			<tr>
				<td> 
					<b>Extension *</b><br/>
					<span style="font-size: 90%"><i>Needs to be unique.</i></span>
				</td>
				<td class="right">
					<input name="extension" id="extension" maxlength=7 value="<? print $row["extension"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var extension = new LiveValidation('extension');
						extension.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<td> 
					<b>Name *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=50 value="<? print $row["name"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var name = new LiveValidation('name');
						name.add(Validate.Presence);
					 </script> 
				</td>
			</tr>
			<tr>
				<td> 
					<b>Type *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="type" id="type" style="width: 302px">
						<option value="Please select...">Please select...</option>
						<option value="Document">Document</option>
						<option value="Spreadsheet">Spreadsheet</option>
						<option value="Presentation">Presentation</option>
						<option value="Graphics/Design">Graphics/Design</option>
						<option value="Video">Video</option>
						<option value="Audio">Audio</option>
						<option value="Other">Other</option>
					</select>
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