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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit_editAdult.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$_GET['gibbonFamilyID']."'>".__($guid, 'Edit Family')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Adult').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    if ($gibbonPersonID == '' or $gibbonFamilyID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT * FROM gibbonPerson, gibbonFamily, gibbonFamilyAdult WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=$gibbonPersonID&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Adult\'s Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input readonly name="child" id="child" maxlength=200 value="<?php echo formatName(htmlPrep($row['title']), htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Parent') ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Comment') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Data displayed in full Student Profile') ?><br/></span>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 class="standardWidth"><?php echo $row['comment'] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Data Access?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Access data on family\'s children?') ?></span>
						</td>
						<td class="right">
							<select name="childDataAccess" id="childDataAccess" class="standardWidth">
								<option <?php if ($row['childDataAccess'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['childDataAccess'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Contact Priority') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'The order in which school should contact family members.') ?></span>
						</td>
						<td class="right">
							<select name="contactPriority" id="contactPriority" class="standardWidth">
								<option <?php if ($row['contactPriority'] == '1') {
    echo 'selected ';
}
            ?>value="1"><?php echo __($guid, '1') ?></option>
								<option <?php if ($row['contactPriority'] == '2') {
    echo 'selected ';
}
            ?>value="2"><?php echo __($guid, '2') ?></option>
								<option <?php if ($row['contactPriority'] == '3') {
    echo 'selected ';
}
            ?>value="3"><?php echo __($guid, '3') ?></option>
							</select>
							<script type="text/javascript">
								/* Advanced Options Control */
								$(document).ready(function(){
									<?php 
                                    if ($row['contactPriority'] == '1') {
                                        echo '$("#contactCall").attr("disabled", "disabled");';
                                        echo '$("#contactSMS").attr("disabled", "disabled");';
                                        echo '$("#contactEmail").attr("disabled", "disabled");';
                                        echo '$("#contactMail").attr("disabled", "disabled");';
                                    }
            ?>	
									$("#contactPriority").change(function(){
										if ($('#contactPriority').val()=="1" ) {
											$("#contactCall").attr("disabled", "disabled");
											$("#contactCall").val("Y");
											$("#contactSMS").attr("disabled", "disabled");
											$("#contactSMS").val("Y");
											$("#contactEmail").attr("disabled", "disabled");
											$("#contactEmail").val("Y");
											$("#contactMail").attr("disabled", "disabled");
											$("#contactMail").val("Y");
										} 
										else {
											$("#contactCall").removeAttr("disabled");
											$("#contactSMS").removeAttr("disabled");
											$("#contactEmail").removeAttr("disabled");
											$("#contactMail").removeAttr("disabled");
										}
									 });
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Call?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency phone calls from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactCall" id="contactCall" class="standardWidth">
								<option <?php if ($row['contactCall'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['contactCall'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'SMS?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency SMS messages from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactSMS" id="contactSMS" class="standardWidth">
								<option <?php if ($row['contactSMS'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['contactSMS'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Email?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency emails from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactEmail" id="contactEmail" class="standardWidth">
								<option <?php if ($row['contactEmail'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['contactEmail'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Mail?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive postage mail from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactMail" id="contactMail" class="standardWidth">
								<option <?php if ($row['contactMail'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['contactMail'] == 'N') {
    echo 'selected ';
}
            ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
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