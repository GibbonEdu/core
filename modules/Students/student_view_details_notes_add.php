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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view_details_notes_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$subpage=$_GET["subpage"] ;
	if ($gibbonPersonID=="" OR $subpage=="") {
		print "<div class='error'>" ;
			print "You have not specified a student or subpage." ;
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view.php'>View Student Profiles</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/student_view_details.php&gibbonPersonID=$gibbonPersonID&subpage=$subpage'>" . formatName("", $row["preferredName"], $row["surname"], "Student") . "</a> > </div><div class='trailEnd'>Add Student Note</div>" ;
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
			

			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage'>Back to Search Results</a>" ;
				print "</div>" ;
			}

			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/student_view_details_notes_addProcess.php?gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] . "&subpage=$subpage" ?>">
				<table style="width: 100%">	
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
									<option value="Please select...">Please select...</option>
									<?
									while ($rowCategories=$resultCategories->fetch()) {
										print "<option value='" . $rowCategories["gibbonStudentNoteCategoryID"] . "'>" . $rowCategories["name"] . "</option>" ;
									}
									?>
								</select>
								<script type="text/javascript">
									var gibbonStudentNoteCategoryID = new LiveValidation('gibbonStudentNoteCategoryID');
									gibbonStudentNoteCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
								 </script>
								 <script type="text/javascript">
								 	$("#gibbonStudentNoteCategoryID").change(function() {
										if ($("#gibbonStudentNoteCategoryID").val()!="Please select...") {
											$.get('<? print $_SESSION[$guid]["absoluteURL"] . "/modules/Students/student_view_details_notes_addAjax.php?gibbonStudentNoteCategoryID=" ?>' + $("#gibbonStudentNoteCategoryID").val(), function(data){
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
	}
}
?>