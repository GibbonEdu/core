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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$allStudents="" ;
	if (isset($_GET["allStudents"])) {
		$allStudents=$_GET["allStudents"] ;
	}
	
	$enableStudentNotes=getSettingByScope($connection2, "Students", "enableStudentNotes") ;
	if ($enableStudentNotes!="Y") {
		print "<div class='error'>" ;
			print __($guid, "You do not have access to this action.") ;
		print "</div>" ;
	}
	else {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
		$subpage=$_GET["subpage"] ;
		if ($gibbonPersonID=="" OR $subpage=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
			
				//Proceed!
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php'>" . __($guid, 'View Student Profiles') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view_details.php&gibbonPersonID=$gibbonPersonID&subpage=$subpage&allStudents=$allStudents'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Student Note') . "</div>" ;
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
						$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
					}
					else if ($updateReturn=="success0") {
						$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				} 
			
				//Check if school year specified
				$gibbonStudentNoteID=$_GET["gibbonStudentNoteID"] ;
				if ($gibbonStudentNoteID=="") {
					print "<div class='error'>" ;
						print __($guid, "You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					try {
						$data=array("gibbonStudentNoteID"=>$gibbonStudentNoteID); 
						$sql="SELECT * FROM gibbonStudentNote WHERE gibbonStudentNoteID=:gibbonStudentNoteID" ;
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
					
						if ($_GET["search"]!="") {
							print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage&category=" . $_GET["category"] . "&allStudents=$allStudents'>" . __($guid, 'Back to Search Results') . "</a>" ;
							print "</div>" ;
						}
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_editProcess.php?gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage&gibbonStudentNoteID=$gibbonStudentNoteID&category=" . $_GET["category"] . "&allStudents=$allStudents" ?>">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr>
									<td style='width: 275px'> 
										<b><?php print __($guid, 'Title') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<input name="title" id="title" maxlength=100 value="<?php print htmlprep($row["title"]) ?>" type="text" class="standardWidth">
										<script type="text/javascript">
											var title=new LiveValidation('title');
											title.add(Validate.Presence);
										</script>
									</td>
								</tr>
								<?php
								try {
									$dataCategories=array(); 
									$sqlCategories="SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name" ;
									$resultCategories=$connection2->prepare($sqlCategories);
									$resultCategories->execute($dataCategories);
								}
								catch(PDOException $e) { }
								if ($resultCategories->rowCount()>0) {
									?>
									<tr>
										<td style='width: 275px'> 
											<b><?php print __($guid, 'Category') ?> *</b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<select name="gibbonStudentNoteCategoryID" id="gibbonStudentNoteCategoryID" class="standardWidth">
												<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
												<?php
												while ($rowCategories=$resultCategories->fetch()) {
													$selected="" ;
													if ($rowCategories["gibbonStudentNoteCategoryID"]==$row["gibbonStudentNoteCategoryID"]) {
														$selected="selected" ;
													}
													print "<option $selected value='" . $rowCategories["gibbonStudentNoteCategoryID"] . "'>" . $rowCategories["name"] . "</option>" ;
												}
												?>
											</select>
											<script type="text/javascript">
												var gibbonStudentNoteCategoryID=new LiveValidation('gibbonStudentNoteCategoryID');
												gibbonStudentNoteCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
											</script>
										</td>
									</tr>
									<?php
								}
								?>
								<tr>
									<td colspan=2 style='padding-top: 15px;'> 
										<b><?php print __($guid, 'Note') ?> *</b><br/>
										<?php print getEditor($guid,  TRUE, "note", $row["note"], 25, true, true, false) ?>
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
	}
}
?>