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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_facility_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a>  > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php'>".__($guid, 'Manage Staff')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID='.$_GET['gibbonStaffID']."'>".__($guid, 'Edit Staff')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Facility').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonStaffID = $_GET['gibbonStaffID'];
    $search = $_GET['search'];
    if ($gibbonStaffID == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStaffID' => $gibbonStaffID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT gibbonStaff.*, preferredName, surname FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID AND gibbonPerson.gibbonPersonID=:gibbonPersonID';
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
            $row = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID=$gibbonStaffID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/staff_manage_edit_facility_addProcess.php?gibbonPersonID=$gibbonPersonID&gibbonStaffID=$gibbonStaffID&search=$search" ?>" enctype="multipart/form-data">
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
						<td>
							<b><?php echo __($guid, 'Facility') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
                            <?php
                            //Get array of spaces used by this user
                            try {
                                $dataUnique = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlUnique = 'SELECT * FROM gibbonSpacePerson WHERE gibbonPersonID=:gibbonPersonID';
                                $resultUnique = $connection2->prepare($sqlUnique);
                                $resultUnique->execute($dataUnique);
                            } catch (PDOException $e) {}
                            $rowUnique = $resultUnique->fetchAll();
                            ?>

                            <select name="gibbonSpaceID" id="gibbonSpaceID" class="standardWidth">
								<option value='Please select...'><?php echo __($guid, 'Please select...') ?></option>" ;
                                <?php
                                try {
									$dataSelect = array();
									$sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) { }
								while ($rowSelect = $resultSelect->fetch()) {
                                    $used = false;
                                    foreach ($rowUnique as $unique) {
                                        if ($unique['gibbonSpaceID'] == $rowSelect['gibbonSpaceID']) {
                                            $used = true;
                                        }
                                    }
									if ($used == false) {
										echo "<option value='".$rowSelect['gibbonSpaceID']."'>".htmlPrep($rowSelect['name']).'</option>';
									}
								}
								?>
							</select>
                            <script type="text/javascript">
                                var gibbonSpaceID=new LiveValidation('gibbonSpaceID');
                                gibbonSpaceID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                            </script>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Usage Type') ?></b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="usageType" id="usageType">
								<option value=''></option>" ;
								<option value="Teaching"><?php echo __($guid, 'Teaching') ?></option>
								<option value="Office"><?php echo __($guid, 'Office') ?></option>
								<option value="Other"><?php echo __($guid, 'Other') ?></option>
							</select>
						</td>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>
