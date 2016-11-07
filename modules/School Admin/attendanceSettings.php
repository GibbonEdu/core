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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __($guid, 'Attendance Codes');
    echo '</h3>';
    echo '<p>';
    echo __($guid, 'These codes should not be changed during an active school year. Removing an attendace code after attendance has been recorded can result in lost information.');
    echo '</p>';


    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonAttendanceCode ORDER BY sequenceNumber ASC, name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/attendanceSettings_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th style="width:30px;">';
        echo __($guid, 'Code');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Direction');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Scope');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Active');
        echo '</th>';
        echo '<th style="width:80px;">';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;

        while ($row = $result->fetch()) {
            echo "<tr>";
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo ($row['direction'] == 'In')? __($guid, 'In Class') : __($guid, 'Out of Class');
            echo '</td>';
            echo '<td>';
            echo $row['scope'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $row['active']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_edit.php&gibbonAttendanceCodeID='.$row['gibbonAttendanceCodeID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            if ($row['type'] != 'Core') {
            	echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_delete.php&gibbonAttendanceCodeID='.$row['gibbonAttendanceCodeID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
        	}
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    echo '<h3>';
    echo __($guid, 'Miscellaneous');
    echo '</h3>';
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Reasons'); ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceReasons'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>

            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Attendance CLI'); ?></h3>
                </td>
            </tr>
            <tr>
                <?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceCLINotifyByRollGroup'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
                    <span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
                </td>
                <td class="right">
                    <select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
                        <option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceCLINotifyByClass'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
                    <span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
                </td>
                <td class="right">
                    <select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
                        <option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceCLIAdditionalUsers'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
                    <span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
                </td>
                <td class="right">
                    <select multiple name="<?php echo $row['name'] ?>[]" id="<?php echo $row['name'] ?>[]" style="width: 302px; height: 130px">
                        <?php
                        try {
                            $data=array( 'action1' => '%report_rollGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
                            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonRole.name as roleName 
                                    FROM gibbonPerson 
                                    JOIN gibbonPermission ON (gibbonPerson.gibbonRoleIDPrimary=gibbonPermission.gibbonRoleID) 
                                    JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) 
                                    JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID) 
                                    WHERE status='Full' 
                                    AND (gibbonAction.URLList LIKE :action1 OR gibbonAction.URLList LIKE :action2)
                                    GROUP BY gibbonPerson.gibbonPersonID 
                                    ORDER BY gibbonRole.gibbonRoleID, surname, preferredName" ;
                            $resultSelect=$connection2->prepare($sql);
                            $resultSelect->execute($data);
                        }
                        catch(PDOException $e) { }

                        $roleGroup = '';
                        $users = explode(',', $row['value'] );

                        while ($rowSelect=$resultSelect->fetch()) {

                            $selected = ( in_array($rowSelect['gibbonPersonID'], $users) !== false)? 'selected' : '';

                            if ($roleGroup != $rowSelect["roleName"]) {
                                if ($roleGroup != '') echo '</optgroup>';

                                $roleGroup = $rowSelect["roleName"];
                                echo '<optgroup label="-- '.__($guid, $roleGroup).' --">';
                            }

                            echo '<option '.$selected.' value="' . $rowSelect["gibbonPersonID"] . '">';
                                echo  formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Staff", true, true);
                            echo '</option>' ;
                        }
                        echo '</optgroup>';
                        ?>          
                    </select>
                </td>
            </tr>

            <?php /*
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Medical'); ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceMedicalReasons'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Attendance' AND name='attendanceEnableMedicalTracking'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                $enableSymptoms = $row['value'];
                ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					 $("#attendanceEnableMedicalTracking").click(function(){
						if ($('#attendanceEnableMedicalTracking option:selected').val()=="Y" ) {
							$("#symptomsRow").slideDown("fast", $("#symptomsRow").css("display","table-row"));  

						} else {
							$("#symptomsRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='symptomsRow' <?php if ($enableSymptoms == 'N') { echo " style='display: none'"; } ?>>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Students' AND name='medicalIllnessSymptoms'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php if (isset($row['value'])) { echo $row['value']; } ?></textarea>
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script> 
				</td>
			</tr>
            */ ?>
			
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
?>
