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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_edit_day_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonTTDayID=$_GET["gibbonTTDayID"] ;
	$gibbonTTID=$_GET["gibbonTTID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	if ($gibbonTTDayID=="" OR $gibbonTTID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTID"=>$gibbonTTID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonTTDayID"=>$gibbonTTDayID); 
			$sql="SELECT gibbonTT.gibbonTTID, gibbonSchoolYear.name AS yearName, gibbonTT.name AS ttName, gibbonTTDay.name, gibbonTTDay.nameShort, gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonTT.gibbonTTID=:gibbonTTID AND gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTDayID=:gibbonTTDayID" ;
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
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Manage Timetables') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Edit Timetable') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Timetable Day') . "</div>" ; 
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
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_editProcess.php?gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print htmlPrep($row["yearName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var yearName=new LiveValidation('yearName');
								yearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Timetable') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="ttName" id="ttName" maxlength=20 value="<?php print $row["ttName"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var courseName=new LiveValidation('courseName');
								coursename2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this timetable.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=12 value="<?php print htmlPrep($row["name"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this timetable.') ?></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="<?php print htmlPrep($row["nameShort"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Timetable Column') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
							try {
								$dataSelect=array("gibbonTTColumnID"=>$row["gibbonTTColumnID"]); 
								$sqlSelect="SELECT * FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							
							if ($resultSelect->rowCount()==1) {
								$rowSelect=$resultSelect->fetch() ;
								?>
								<input readonly name="column" id="column" maxlength=20 value="<?php print htmlPrep($rowSelect["name"]) ?>" type="text" class="standardWidth">
								<?php
							}
							?>				
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonTTID" id="gibbonTTID" value="<?php print $gibbonTTID ?>" type="hidden">
							<input name="gibbonTTColumnID" id="gibbonTTColumnID" value="<?php print $row["gibbonTTColumnID"] ?>" type="hidden">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
			
			print "<h2>" ;
			print __($guid, "Edit Classes by Period") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonTTColumnID"=>$row["gibbonTTColumnID"]); 
				$sql="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart, name" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
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
							print __($guid, "Time") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Type") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Classes") ;
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
								print $row["timeStart"] . " - " . $row["timeEnd"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["type"] ;
							print "</td>" ;
							print "<td>" ;
								try {
									$dataClasses=array("gibbonTTColumnRowID"=>$row["gibbonTTColumnRowID"], "gibbonTTDayID"=>$gibbonTTDayID); 
									$sqlClasses="SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID" ;
									$resultClasses=$connection2->prepare($sqlClasses);
									$resultClasses->execute($dataClasses);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								print $resultClasses->rowCount() ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_edit_class.php&gibbonTTColumnRowID=" . $row["gibbonTTColumnRowID"] . "&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
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