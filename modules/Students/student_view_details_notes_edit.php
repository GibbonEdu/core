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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$subpage=$_GET["subpage"] ;
	if ($gibbonPersonID=="" OR $subpage=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
			print "The specified student does not seem to exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php'>View Student Profiles</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view_details.php&gibbonPersonID=$gibbonPersonID&subpage=$subpage'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</a> > </div><div class='trailEnd'>Edit Student Note</div>" ;
			print "</div>" ;
	
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail4") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
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
					print _("You have not specified one or more required parameters.") ;
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
						print _("The specified record cannot be found.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					
					if ($_GET["search"]!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage'>" . _('Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					?>
					<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_editProcess.php?gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage&gibbonStudentNoteID=$gibbonStudentNoteID" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<?
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
									<td> 
										<b>Category *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="gibbonStudentNoteCategoryID" id="gibbonStudentNoteCategoryID" style="width: 302px">
											<option value="Please select..."><? print _('Please select...') ?></option>
											<?
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
											gibbonStudentNoteCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
										 </script>
									</td>
								</tr>
								<?
							}
							?>
							<tr>
								<td colspan=2 style='padding-top: 15px;'> 
									<b>Note *</b><br/>
									<? print getEditor($guid,  TRUE, "note", $row["note"], 25, true, true, false) ?>
								</td>
							</tr>
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<? print _("Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?
				}
			}
		}
	}
}
?>