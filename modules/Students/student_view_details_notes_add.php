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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_add.php")==FALSE) {
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php'>View Student Profiles</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view_details.php&gibbonPersonID=$gibbonPersonID&subpage=$subpage'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</a> > </div><div class='trailEnd'>Add Student Note</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			

			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}

			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_addProcess.php?gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
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
							<td> 
								<b>Category *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="gibbonStudentNoteCategoryID" id="gibbonStudentNoteCategoryID" style="width: 302px">
									<option value="Please select..."><?php print _('Please select...') ?></option>
									<?php
									while ($rowCategories=$resultCategories->fetch()) {
										print "<option value='" . $rowCategories["gibbonStudentNoteCategoryID"] . "'>" . $rowCategories["name"] . "</option>" ;
									}
									?>
								</select>
								<script type="text/javascript">
									var gibbonStudentNoteCategoryID=new LiveValidation('gibbonStudentNoteCategoryID');
									gibbonStudentNoteCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								 </script>
								 <script type="text/javascript">
								 	$("#gibbonStudentNoteCategoryID").change(function() {
										if ($("#gibbonStudentNoteCategoryID").val()!="Please select...") {
											$.get('<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/Students/student_view_details_notes_addAjax.php?gibbonStudentNoteCategoryID=" ?>' + $("#gibbonStudentNoteCategoryID").val(), function(data){
												if (tinyMCE.activeEditor==null) {
													if ($("textarea#note").val()=="") {
														$("textarea#note").val(data) ;
													}
												}
												else {
													if (tinyMCE.get('note').getContent()=="") {
														tinyMCE.get('note').setContent(data) ;
													}
												}
											});
											
										}
									});
								 </script>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td colspan=2 style='padding-top: 15px;'> 
							<b>Note *</b><br/>
							<?php print getEditor($guid,  TRUE, "note", "", 25, true, true, false) ?>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>