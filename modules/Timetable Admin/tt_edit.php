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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Manage Timetables') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Timetable') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonTTID=$_GET["gibbonTTID"] ;
	if ($gibbonTTID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTID"=>$gibbonTTID); 
			$sql="SELECT gibbonTT.*, gibbonSchoolYear.name as yearName FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonTTID=:gibbonTTID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/tt_editProcess.php?gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="gibbonSchoolYearID" id="gibbonSchoolYearID" maxlength=20 value="<?php print $row["yearName"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var schoolYearName=new LiveValidation('schoolYearName');
								schoolYearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this school year.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="<?php print htmlPrep($row["name"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=12 value="<?php print htmlPrep($row["nameShort"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Active') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="active">
								<?php
								print "<option " ; if ($row["active"]=="Y") { print "selected "; } ; print "value='Y'>" . __($guid, 'Yes') . "</option>" ;
								print "<option " ; if ($row["active"]=="N") { print "selected "; } ; print " value='N'>" . __($guid, 'No') . "</option>" ;
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Year Groups') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Groups not in an active TT this year.') ?></span>
						</td>
						<td class="right">
							<?php 
							$yearGroups=getNonTTYearGroups($connection2, $_GET["gibbonSchoolYearID"], $gibbonTTID) ;
							if ($yearGroups=="") {
								print "<i>" . __($guid, 'No year groups available.') . "</i>" ;
							}
							else {
								for ($i=0; $i<count($yearGroups); $i=$i+2) {
									$checked="" ;
									if (is_numeric(strpos($row["gibbonYearGroupIDList"], $yearGroups[$i]))) {
										$checked="checked " ;
									}
									print __($guid, $yearGroups[($i+1)]) . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
									print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
								}
							}
							?>
							<input type="hidden" name="count" value="<?php print (count($yearGroups))/2 ?>">
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonTTID" id="gibbonTTID" value="<?php print $_GET["gibbonTTID"] ?>" type="hidden">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $_GET["gibbonSchoolYearID"] ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
			
			print "<h2>" ;
			print __($guid, "Edit Timetable Days") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonTTID"=>$gibbonTTID); 
				$sql="SELECT gibbonTTDay.*, gibbonTTColumn.name AS columnName FROM gibbonTTDay JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonTTDay.name" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_add.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonTTID=$gibbonTTID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Name") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Short Name") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Column") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["nameShort"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["columnName"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_edit.php&gibbonTTDayID=" . $row["gibbonTTDayID"] . "&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_delete.php&gibbonTTDayID=" . $row["gibbonTTDayID"] . "&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
						
						$count++ ;
					}
				print "</table>" ;
				
			}
		}
	}
}
?>