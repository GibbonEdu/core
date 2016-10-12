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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage.php'>".__($guid, 'Manage Grade Scales')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Grade Scale').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonScaleID = $_GET['gibbonScaleID'];
    if ($gibbonScaleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
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
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/gradeScales_manage_editProcess.php?gibbonScaleID=$gibbonScaleID" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'Name') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Must be unique for this school year.') ?></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=40 value="<?php if (isset($row['name'])) { echo htmlPrep(__($guid, $row['name'])); } ?>" type="text" class="standardWidth">
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
						<input name="nameShort" id="nameShort" maxlength=5 value="<?php if (isset($row['nameShort'])) { echo htmlPrep(__($guid, $row['nameShort'])); } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var nameShort=new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Usage') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Brief description of how scale is used.') ?></span>
					</td>
					<td class="right">
						<input name="usage" id="usage" maxlength=50 value="<?php if (isset($row['usage'])) { echo htmlPrep(__($guid, $row['usage'])); } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var usage=new LiveValidation('usage');
							usage.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Active') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="active" id="active" class="standardWidth">
							<option <?php if ($row['active'] == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
							<option <?php if ($row['active'] == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Numeric? *</b><br/>
						<span class="emphasis small">Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.</span>
					</td>
					<td class="right">
						<select name="numeric" id="numeric" class="standardWidth">
							<option <?php if ($row['numeric'] == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
							<option <?php if ($row['numeric'] == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Lowest Acceptable') ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, 'This is the lowest grade a student can get without being unsatisfactory.') ?></span>
					</td>
					<td class="right">
						<select name="lowestAcceptable" id="lowestAcceptable" class="standardWidth">
							<?php
                            echo "<option value=''></option>";
							try {
								$dataSelect = array('gibbonScaleID' => $gibbonScaleID);
								$sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								$selected = '';
								if ($rowSelect['sequenceNumber'] == $row['lowestAcceptable']) {
									$selected = 'selected';
								}
								echo "<option $selected value='".$rowSelect['sequenceNumber']."'>".$rowSelect['value'].'</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input name="gibbonScaleID" id="gibbonScaleID" value="<?php echo $_GET['gibbonScaleID'] ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
			</form>
			<?php

            echo '<h2>';
            echo __($guid, 'Edit Grades');
            echo '</h2>';

            try {
                $data = array('gibbonScaleID' => $gibbonScaleID);
                $sql = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/gradeScales_manage_edit_grade_add.php&gibbonScaleID=$gibbonScaleID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Value');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Descriptor');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Sequence Number');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Is Default?');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo __($guid, $row['value']);
                    echo '</td>';
                    echo '<td>';
                    echo __($guid, $row['descriptor']);
                    echo '</td>';
                    echo '<td>';
                    echo $row['sequenceNumber'];
                    echo '</td>';
                    echo '<td>';
                    echo ynExpander($guid, $row['isDefault']);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_edit.php&gibbonScaleGradeID='.$row['gibbonScaleGradeID']."&gibbonScaleID=$gibbonScaleID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_delete.php&gibbonScaleGradeID='.$row['gibbonScaleGradeID']."&gibbonScaleID=$gibbonScaleID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }
        }
    }
}
?>