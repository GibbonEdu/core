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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/daysOfWeek_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Alert Levels</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Some aspects of the update failed." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	//Let's go!
	?>
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/alertLevelSettingsProcess.php"?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<?
			$count=0 ;
			while($row=$result->fetch()) {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3><? print $row["name"] ?></h3>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Name *</b>
					</td>
					<td class="right">
						<input type='hidden' name="<? print "gibbonAlertLevelID" .$count ?>" id="<? print "gibbonAlertLevelID" .$count ?>" value="<? print $row["gibbonAlertLevelID"] ?>">
						<input type='text' name="<? print "name" .$count ?>" id="<? print "name" .$count ?>" maxlength=50 value="<? print $row["name"] ?>" style="width: 300px">
						<script type="text/javascript">
							var <? print "name" .$count ?>=new LiveValidation('<? print "name" .$count ?>');
							<? print "name" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Short Name *</b>
					</td>
					<td class="right">
						<input type='text' name="<? print "nameShort" .$count ?>" id="<? print "nameShort" .$count ?>" maxlength=4 value="<? print $row["nameShort"] ?>" style="width: 300px">
						<script type="text/javascript">
							var <? print "nameShort" .$count ?>=new LiveValidation('<? print "nameShort" .$count ?>');
							<? print "nameShort" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Font/Border Color *</b><br/>
						<span style="font-size: 90%"><i>RGB Hex value, without leading #.</i></span>
					</td>
					<td class="right">
						<input type='text' name="<? print "color" .$count ?>" id="<? print "color" .$count ?>" maxlength=6 value="<? print $row["color"] ?>" style="width: 300px">
						<script type="text/javascript">
							var <? print "color" .$count ?>=new LiveValidation('<? print "color" .$count ?>');
							<? print "color" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Background Color *</b><br/>
						<span style="font-size: 90%"><i>RGB Hex value, without leading #.</i></span>
					</td>
					<td class="right">
						<input type='text' name="<? print "colorBG" .$count ?>" id="<? print "colorBG" .$count ?>" maxlength=6 value="<? print $row["colorBG"] ?>" style="width: 300px">
						<script type="text/javascript">
							var <? print "colorBG" .$count ?>=new LiveValidation('<? print "colorBG" .$count ?>');
							<? print "colorBG" .$count ?>.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Sequence Number *</b><br/>
						<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
					</td>
					<td class="right">
						<input readonly type='text' name="<? print "sequenceNumber" .$count ?>" id="<? print "sequenceNumber" .$count ?>" maxlength=4 value="<? print $row["sequenceNumber"] ?>" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b>Description </b> 
						<textarea name='<? print "description" .$count ?>' id='<? print "description" .$count ?>' rows=5 style='width: 300px'><? print $row["description"] ?></textarea>
					</td>
				</tr>
				<?
				$count++ ;
			}
			?>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
				<td class="right">
					<input type="hidden" name="count" value="<? print $count ?>">
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
}
?>