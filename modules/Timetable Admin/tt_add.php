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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/tt.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Timetables')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Timetable').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable Admin/tt_edit.php&gibbonTTID='.$_GET['editID'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];

    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/tt_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var schoolYearName=new LiveValidation('schoolYearName');
								schoolYearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this school year.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=12 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Active</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="active">
								<?php
                                echo "<option value='Y'>".__($guid, 'Yes').'</option>';
            					echo "<option value='N'>".__($guid, 'No').'</option>'; ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Year Groups') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Groups not in an active TT this year.') ?></span>
						</td>
						<td class="right">
							<?php 
                            $yearGroups = getNonTTYearGroups($connection2, $gibbonSchoolYearID);
							if ($yearGroups == '') {
								echo '<i>'.__($guid, 'No year groups available.').'</i>';
							} else {
								for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
									echo __($guid, $yearGroups[($i + 1)])." <input type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
									echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
								}
							}
							?>
							<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
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