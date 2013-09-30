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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttColumn_edit_row_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonTTColumnRowID=$_GET["gibbonTTColumnRowID"] ;
	$gibbonTTColumnID=$_GET["gibbonTTColumnID"] ;
	if ($gibbonTTColumnRowID=="" OR $gibbonTTColumnID=="") {
		print "<div class='error'>" ;
			print "You have not specified a timetable, timetable day or school year." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTColumnID"=>$gibbonTTColumnID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
			$sql="SELECT gibbonTTColumnRow.*, gibbonTTColumn.name AS columnName FROM gibbonTTColumn JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTColumnRow.gibbonTTColumnID=:gibbonTTColumnID AND gibbonTTColumnRow.gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified class cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/ttColumn.php'>Manage Columns</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/ttColumn_edit.php&gibbonTTColumnID=$gibbonTTColumnID'>Edit Column</a> > </div><div class='trailEnd'>Edit Column Row</div>" ;
			print "</div>" ;
			
			$updateReturn = $_GET["updateReturn"] ;
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
					$updateReturnMessage ="Update failed some values need to be unique but were not." ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage ="Update was successful." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_edit_row_editProcess.php?gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTColumnID=$gibbonTTColumnID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Column *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="columnName" id="columnName" maxlength=20 value="<? print $row["columnName"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var courseName = new LiveValidation('courseName');
								courseName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name *</b><br/>
							<span style="font-size: 90%"><i>Must be unique for this timetable.</i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=12 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name = new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Short Name *</b><br/>
							<span style="font-size: 90%"><i>Must be unique for this timetable.</i></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="<? print $row["nameShort"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort = new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Start Time *</b><br/>
							<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="<? print substr($row["timeStart"],0,5) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeStart = new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags = [
										<?
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeStart FROM gibbonTTColumnRow ORDER BY timeStart" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . substr($rowAuto["timeStart"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeStart" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>End Time *</b><br/>
							<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="<? print substr($row["timeEnd"],0,5) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeEnd = new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags = [
										<?
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeEnd FROM gibbonTTColumnRow ORDER BY timeEnd" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . substr($rowAuto["timeEnd"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeEnd" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Type</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="type">
								<?
								print "<option " ; if ($row["type"]=="Lesson") { print "selected "; } ; print " value='Lesson'>Lesson</option>" ;
								print "<option " ; if ($row["type"]=="Pastoral") { print "selected "; } ; print " value='Pastoral'>Pastoral</option>" ;
								print "<option " ; if ($row["type"]=="Sport") { print "selected "; } ; print " value='Sport'>Sport</option>" ;
								print "<option " ; if ($row["type"]=="Break") { print "selected "; } ; print " value='Break'>Break</option>" ;
								print "<option " ; if ($row["type"]=="Service") { print "selected "; } ; print " value='Service'>Service</option>" ;
								print "<option " ; if ($row["type"]=="Other") { print "selected "; } ; print " value='Other'>Other</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input name="gibbonTTColumnID" id="gibbonTTColumnID" value="<? print $gibbonTTColumnID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="reset" value="Reset"> <input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>