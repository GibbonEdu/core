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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_edit_field_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	$gibbonExternalAssessmentID=$_GET["gibbonExternalAssessmentID"] ;
	
	if ($gibbonExternalAssessmentID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
			$sql="SELECT name FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessments_manage.php'>" . __($guid, 'Manage External Assessments') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'>" . __($guid, 'Edit External Assessment') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Field') . "</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage=__($guid, "Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_addProcess.php" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'External Assessment') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php print $row["name"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name')  ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=50 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Category') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="category" id="category" maxlength=50 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var category=new LiveValidation('category');
								category.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Order') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Order in which fields appear within category<br/>Should be unique for this category.') ?><br/></span>
						</td>
						<td class="right">
							<input name="order" id="order" maxlength=4 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var order=new LiveValidation('order');
								order.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Grade Scale') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Grade scale used to control values that can be assigned.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonScaleID" id="gibbonScaleID" class="standardWidth">
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonScaleID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleID=new LiveValidation('gibbonScaleID');
								gibbonScaleID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Year Groups') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Year groups to which this field is relevant.') ?></span>
						</td>
						<td class="right">
							<?php 
							print "<fieldset style='border: none'>" ;
							?>
							<script type="text/javascript">
								$(function () {
									$('.checkall').click(function () {
										$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
									});
								});
							</script>
							<?php
							print __($guid, "All") .  " / " . __($guid, "None") . " <input type='checkbox' class='checkall'><br/>" ;
							$yearGroups=getYearGroups($connection2) ;
							if ($yearGroups=="") {
								print "<i>" . __($guid, 'No year groups available.') . "</i>" ;
							}
							else {
								for ($i=0; $i<count($yearGroups); $i=$i+2) {
									print __($guid, $yearGroups[($i+1)]) . " <input type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
									print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
								}
							}
							print "</fieldset>" ;
							?>
							<input type="hidden" name="count" value="<?php print (count($yearGroups))/2 ?>">
						</td>
					</tr>
					
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID" value="<?php print $gibbonExternalAssessmentID ?>" type="hidden">
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
?>