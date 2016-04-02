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

if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_manage_edit_contract_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a>  > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_manage.php'>" . __($guid, 'Manage Staff') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID=" . $_GET["gibbonStaffID"] . "'>" . __($guid, 'Edit Staff') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Contract') . "</div>" ;
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
	
	//Check if school year specified
	$gibbonStaffID=$_GET["gibbonStaffID"] ;
	$gibbonStaffContractID=$_GET["gibbonStaffContractID"] ;
	$search=$_GET["search"] ;
	if ($gibbonStaffID=="" OR $gibbonStaffContractID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonStaffID"=>$gibbonStaffID, "gibbonStaffContractID"=>$gibbonStaffContractID); 
			$sql="SELECT gibbonStaffContract.*, surname, preferredName FROM gibbonStaffContract JOIN gibbonStaff ON (gibbonStaffContract.gibbonStaffID=gibbonStaff.gibbonStaffID) JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffContract.gibbonStaffID=:gibbonStaffID AND gibbonStaffContractID=:gibbonStaffContractID" ; 
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
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_manage_edit_contract.php&gibbonStaffID=$gibbonStaffID&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/staff_manage_edit_contract_editProcess.php?gibbonStaffContractID=$gibbonStaffContractID&gibbonStaffID=$gibbonStaffID&search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Person') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="person" id="person" maxlength=255 value="<?php print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", false, true) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Title') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'A name to identify this contract.') ?></i></span>
						</td>
						<td class="right">
							<input name="title" id="title" maxlength=100 value="<?php print htmlPrep($row["title"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var title=new LiveValidation('title');
								title.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Status') ?></b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="status">
								<option value=""></option>
								<option <?php if ($row["status"]=="Pending") { print "selected" ; } ?> value="Pending"><?php print __($guid, 'Pending') ?></option>
								<option <?php if ($row["status"]=="Active") { print "selected" ; } ?> value="Active"><?php print __($guid, 'Active') ?></option>
								<option <?php if ($row["status"]=="Expired") { print "selected" ; } ?> value="Expired"><?php print __($guid, 'Expired') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Start Date') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateStart"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add(Validate.Presence);
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							<script type="text/javascript">
								$(function() {
									$( "#dateStart" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'End Date') ?></b><br/>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php if ($row["dateEnd"]!="") { print dateConvertBack($guid, $row["dateEnd"]) ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dateEnd=new LiveValidation('dateEnd');
								dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							<script type="text/javascript">
								$(function() {
									$( "#dateEnd" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?php
					$types=getSettingByScope($connection2, "Staff", "salaryScalePositions") ;
					if ($types!=FALSE) {
						$types=explode(",", $types) ;
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Salary Scale') ?></b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="salaryScale" id="salaryScale" style="width: 302px">
									<option value=""></option>
									<?php
									for ($i=0; $i<count($types); $i++) {
										$selected="" ;
										if ($row["salaryScale"]==$types[$i]) {
											$selected="selected" ;
										}
										?>
										<option <?php print $selected ?> value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
									<?php
									}
									?>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Salary') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="salaryPeriod">
								<option value=""></option>
								<option <?php if ($row["salaryPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["salaryPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["salaryPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["salaryPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="salaryAmount" id="salaryAmount" maxlength=12 value="<?php print htmlPrep($row["salaryAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var salaryAmount=new LiveValidation('salaryAmount');
								salaryAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<?php
					$types=getSettingByScope($connection2, "Staff", "responsibilityPosts") ;
					if ($types!=FALSE) {
						$types=explode(",", $types) ;
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Responsibility Level') ?></b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="responsibility" id="responsibility" style="width: 302px">
									<option value=""></option>
									<?php
									for ($i=0; $i<count($types); $i++) {
										$selected="" ;
										if ($row["responsibility"]==$types[$i]) {
											$selected="selected" ;
										}
										?>
										<option <?php print $selected ?> value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
									<?php
									}
									?>
								</select>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Responsibility') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="responsibilityPeriod">
								<option value=""></option>
								<option <?php if ($row["responsibilityPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["responsibilityPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["responsibilityPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["responsibilityPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="responsibilityAmount" id="responsibilityAmount" maxlength=12 value="<?php print htmlPrep($row["responsibilityAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var responsibilityAmount=new LiveValidation('responsibilityAmount');
								responsibilityAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Housing') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="housingPeriod">
								<option value=""></option>
								<option <?php if ($row["housingPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["housingPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["housingPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["housingPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="housingAmount" id="housingAmount" maxlength=12 value="<?php print htmlPrep($row["housingAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var housingAmount=new LiveValidation('housingAmount');
								housingAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Travel') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="travelPeriod">
								<option value=""></option>
								<option <?php if ($row["travelPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["travelPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["travelPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["travelPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="travelAmount" id="travelAmount" maxlength=12 value="<?php print htmlPrep($row["travelAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var travelAmount=new LiveValidation('travelAmount');
								travelAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Retirement') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="retirementPeriod">
								<option value=""></option>
								<option <?php if ($row["retirementPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["retirementPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["retirementPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["retirementPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="retirementAmount" id="retirementAmount" maxlength=12 value="<?php print htmlPrep($row["retirementAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var retirementAmount=new LiveValidation('retirementAmount');
								retirementAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Bonus/Gratuity') ?></b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["currency"] ?><br/></i></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="bonusPeriod">
								<option value=""></option>
								<option <?php if ($row["bonusPeriod"]=="Week") { print "selected" ; } ?> value="Week"><?php print __($guid, 'Week') ?></option>
								<option <?php if ($row["bonusPeriod"]=="Month") { print "selected" ; } ?> value="Month"><?php print __($guid, 'Month') ?></option>
								<option <?php if ($row["bonusPeriod"]=="Year") { print "selected" ; } ?> value="Year"><?php print __($guid, 'Year') ?></option>
								<option <?php if ($row["bonusPeriod"]=="Contract") { print "selected" ; } ?> value="Contract"><?php print __($guid, 'Contract') ?></option>
							</select>
							<input name="bonusAmount" id="bonusAmount" maxlength=12 value="<?php print htmlPrep($row["bonusAmount"]) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var bonusAmount=new LiveValidation('bonusAmount');
								bonusAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php print __($guid, 'Education Benefits') ?></b><br/>
							<textarea name="education" id="education" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["education"]) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php print __($guid, 'Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Contract File') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Accepts PDF, ODT, DOC, DOCX, RTF.') ?><br/></i></span>
							<?php if ($row["contractUpload"]!="") { ?>
							<span style="font-size: 90%"><i><?php print __($guid, 'Will overwrite existing attachment.') ?></i></span>
							<?php } ?>
						</td>
						<td class="right">
							<?php
							if ($row["contractUpload"]!="") {
								print __($guid, "Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["contractUpload"] . "'>" . $row["contractUpload"] . "</a><br/><br/>" ;
							}
							?>
							<input type="file" name="file1" id="file1"><br/><br/>
							<script type="text/javascript">
								var file1=new LiveValidation('file1');
								file1.add( Validate.Inclusion, { within: ['pdf','odt','doc','docx','rtf'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonStudentEnrolmentID" id="gibbonStudentEnrolmentID" value="<?php print $gibbonStudentEnrolmentID ?>" type="hidden">
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