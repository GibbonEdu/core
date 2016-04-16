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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_edit_day_edit_class_edit.php")==FALSE) {
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
	$gibbonTTColumnRowID=$_GET["gibbonTTColumnRowID"] ;
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	
	if ($gibbonTTDayID=="" OR $gibbonTTID=="" OR $gibbonSchoolYearID=="" OR $gibbonTTColumnRowID=="" OR $gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonTTDayID"=>$gibbonTTDayID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID, gibbonSpaceID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			$course=$row["course"] ;
			$class=$row["class"] ;
			$gibbonSpaceID=$row["gibbonSpaceID"] ;
			$gibbonTTDayRowClassID=$row["gibbonTTDayRowClassID"] ;
			
			try {
				$data=array("gibbonTTDayID"=>$gibbonTTDayID, "gibbonTTID"=>$gibbonTTID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID); 
				$sql="SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName, gibbonYearGroupIDList FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID" ;
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
				$row=$result->fetch() ;
				
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > ... > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Manage Timetables') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Edit Timetable') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>" . __($guid, 'Edit Timetable Day') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/tt_edit_day_edit_class.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID'>" . __($guid, 'Classes in Period') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Class in Period') . "</div>" ; 
				print "</div>" ;
				
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage="" ;
				$classOut="error" ;
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
					else if ($updateReturn=="success0") {
						$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
						$classOut="success" ;
					}
					print "<div class='$classOut'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				} 
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/tt_edit_day_edit_class_editProcess.php?&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
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
								<b><?php print __($guid, 'Day') ?> *</b><br/>
								<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="dayName" id="dayName" maxlength=20 value="<?php print $row["dayName"] ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var courseName=new LiveValidation('courseName');
									coursename2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Period') ?> *</b><br/>
								<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="rowName" id="rowName" maxlength=20 value="<?php print $row["rowName"] ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var courseName=new LiveValidation('courseName');
									coursename2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Class') ?> *</b><br/>
								<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="class" id="class" maxlength=20 value="<?php print $course . "." . $class ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Location') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="gibbonSpaceID" id="gibbonSpaceID" class="standardWidth">
									<?php
									print "<option value=''></option>" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										try {
											$dataUnique=array("gibbonTTDayID"=>$gibbonTTDayID, "gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonSpaceID"=>$rowSelect["gibbonSpaceID"]); 
											$sqlUnique="SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonSpaceID=:gibbonSpaceID" ;
											$resultUnique=$connection2->prepare($sqlUnique);
											$resultUnique->execute($dataUnique);
										}
										catch(PDOException $e) { }
										if ($resultUnique->rowCount()<1) {
											print "<option value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
										else if ($rowSelect["gibbonSpaceID"]==$gibbonSpaceID) {
											print "<option selected value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
									}
									?>				
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
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
}
?>