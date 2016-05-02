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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_contract_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a>  > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php'>".__($guid, 'Manage Staff')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID='.$_GET['gibbonStaffID']."'>".__($guid, 'Edit Staff')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Contract').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonStaffID = $_GET['gibbonStaffID'];
    $gibbonStaffContractID = $_GET['gibbonStaffContractID'];
    $search = $_GET['search'];
    if ($gibbonStaffID == '' or $gibbonStaffContractID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStaffID' => $gibbonStaffID, 'gibbonStaffContractID' => $gibbonStaffContractID);
            $sql = 'SELECT gibbonStaffContract.*, surname, preferredName FROM gibbonStaffContract JOIN gibbonStaff ON (gibbonStaffContract.gibbonStaffID=gibbonStaff.gibbonStaffID) JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffContract.gibbonStaffID=:gibbonStaffID AND gibbonStaffContractID=:gibbonStaffContractID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage_edit_contract.php&gibbonStaffID=$gibbonStaffID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_contract_editProcess.php?gibbonStaffContractID=$gibbonStaffContractID&gibbonStaffID=$gibbonStaffID&search=$search" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Person') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="person" id="person" maxlength=255 value="<?php echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', false, true) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Title') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'A name to identify this contract.') ?></span>
						</td>
						<td class="right">
							<input name="title" id="title" maxlength=100 value="<?php echo htmlPrep($row['title']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var title=new LiveValidation('title');
								title.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Status') ?></b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="status">
								<option value=""></option>
								<option <?php if ($row['status'] == 'Pending') {
    echo 'selected';
}
            ?> value="Pending"><?php echo __($guid, 'Pending') ?></option>
								<option <?php if ($row['status'] == 'Active') {
    echo 'selected';
}
            ?> value="Active"><?php echo __($guid, 'Active') ?></option>
								<option <?php if ($row['status'] == 'Expired') {
    echo 'selected';
}
            ?> value="Expired"><?php echo __($guid, 'Expired') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $row['dateStart']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add(Validate.Presence);
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
    echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
} else {
    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
}
            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
            ?>." } ); 
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
							<b><?php echo __($guid, 'End Date') ?></b><br/>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php if ($row['dateEnd'] != '') {
    echo dateConvertBack($guid, $row['dateEnd']);
}
            ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateEnd=new LiveValidation('dateEnd');
								dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
    echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
} else {
    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
}
            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
            ?>." } ); 
							</script>
							<script type="text/javascript">
								$(function() {
									$( "#dateEnd" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?php
                    $types = getSettingByScope($connection2, 'Staff', 'salaryScalePositions');
            if ($types != false) {
                $types = explode(',', $types);
                ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Salary Scale') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="salaryScale" id="salaryScale" class="standardWidth">
									<option value=""></option>
									<?php
                                    for ($i = 0; $i < count($types); ++$i) {
                                        $selected = '';
                                        if ($row['salaryScale'] == $types[$i]) {
                                            $selected = 'selected';
                                        }
                                        ?>
										<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
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
							<b><?php echo __($guid, 'Salary') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="salaryPeriod">
								<option value=""></option>
								<option <?php if ($row['salaryPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['salaryPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['salaryPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['salaryPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="salaryAmount" id="salaryAmount" maxlength=12 value="<?php echo htmlPrep($row['salaryAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var salaryAmount=new LiveValidation('salaryAmount');
								salaryAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<?php
                    $types = getSettingByScope($connection2, 'Staff', 'responsibilityPosts');
            if ($types != false) {
                $types = explode(',', $types);
                ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Responsibility Level') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="responsibility" id="responsibility" class="standardWidth">
									<option value=""></option>
									<?php
                                    for ($i = 0; $i < count($types); ++$i) {
                                        $selected = '';
                                        if ($row['responsibility'] == $types[$i]) {
                                            $selected = 'selected';
                                        }
                                        ?>
										<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
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
							<b><?php echo __($guid, 'Responsibility') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="responsibilityPeriod">
								<option value=""></option>
								<option <?php if ($row['responsibilityPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['responsibilityPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['responsibilityPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['responsibilityPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="responsibilityAmount" id="responsibilityAmount" maxlength=12 value="<?php echo htmlPrep($row['responsibilityAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var responsibilityAmount=new LiveValidation('responsibilityAmount');
								responsibilityAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Housing') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="housingPeriod">
								<option value=""></option>
								<option <?php if ($row['housingPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['housingPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['housingPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['housingPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="housingAmount" id="housingAmount" maxlength=12 value="<?php echo htmlPrep($row['housingAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var housingAmount=new LiveValidation('housingAmount');
								housingAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Travel') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="travelPeriod">
								<option value=""></option>
								<option <?php if ($row['travelPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['travelPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['travelPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['travelPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="travelAmount" id="travelAmount" maxlength=12 value="<?php echo htmlPrep($row['travelAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var travelAmount=new LiveValidation('travelAmount');
								travelAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Retirement') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="retirementPeriod">
								<option value=""></option>
								<option <?php if ($row['retirementPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['retirementPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['retirementPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['retirementPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="retirementAmount" id="retirementAmount" maxlength=12 value="<?php echo htmlPrep($row['retirementAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var retirementAmount=new LiveValidation('retirementAmount');
								retirementAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Bonus/Gratuity') ?></b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['currency'] ?><br/></span>
						</td>
						<td class="right">
							<select style="width: 150px" name="bonusPeriod">
								<option value=""></option>
								<option <?php if ($row['bonusPeriod'] == 'Week') {
    echo 'selected';
}
            ?> value="Week"><?php echo __($guid, 'Week') ?></option>
								<option <?php if ($row['bonusPeriod'] == 'Month') {
    echo 'selected';
}
            ?> value="Month"><?php echo __($guid, 'Month') ?></option>
								<option <?php if ($row['bonusPeriod'] == 'Year') {
    echo 'selected';
}
            ?> value="Year"><?php echo __($guid, 'Year') ?></option>
								<option <?php if ($row['bonusPeriod'] == 'Contract') {
    echo 'selected';
}
            ?> value="Contract"><?php echo __($guid, 'Contract') ?></option>
							</select>
							<input name="bonusAmount" id="bonusAmount" maxlength=12 value="<?php echo htmlPrep($row['bonusAmount']) ?>" type="text" style="width: 145px">
							<script type="text/javascript">
								var bonusAmount=new LiveValidation('bonusAmount');
								bonusAmount.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php echo __($guid, 'Education Benefits') ?></b><br/>
							<textarea name="education" id="education" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['education']) ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan=2 style='padding-top: 15px'> 
							<b><?php echo __($guid, 'Notes') ?></b><br/>
							<textarea name="notes" id="notes" rows=5 style="width:738px; margin: 5px 0px 0px 0px"><?php echo htmlPrep($row['notes']) ?></textarea>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Contract File') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Accepts PDF, ODT, DOC, DOCX, RTF.') ?><br/></span>
							<?php if ($row['contractUpload'] != '') {
    ?>
							<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
							<?php 
}
            ?>
						</td>
						<td class="right">
							<?php
                            if ($row['contractUpload'] != '') {
                                echo __($guid, 'Current attachment:')." <a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['contractUpload']."'>".$row['contractUpload'].'</a><br/><br/>';
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
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
							<input name="gibbonStudentEnrolmentID" id="gibbonStudentEnrolmentID" value="<?php echo $gibbonStudentEnrolmentID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>