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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonScaleID = $_GET['gibbonScaleID'];

    if ($gibbonScaleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
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
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage.php'>".__($guid, 'Manage Grade Scales')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage_edit.php&gibbonScaleID=$gibbonScaleID'>".__($guid, 'Edit Grade Scale')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Grade').'</div>';
            echo '</div>';

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/gradeScales_manage_edit_grade_edit.php&gibbonScaleGradeID='.$_GET['editID']."&gibbonScaleID=$gibbonScaleID";
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Grade Scale') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<?php echo __($guid, $row['name']) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Value') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this grade scale.') ?></span>
						</td>
						<td class="right">
							<input name="value" id="value" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var value=new LiveValidation('value');
								value.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Descriptor') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="descriptor" id="descriptor" maxlength=50 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var descriptor=new LiveValidation('descriptor');
								descriptor.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this grade scale.') ?><br/></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Is Default?') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Preselects this option when using this grade scale in appropriate contexts.') ?><br/></span>
						</td>
						<td class="right">
							<select name="isDefault" id="isDefault" class="standardWidth">
								<option value="N"><?php echo ynExpander($guid, 'N') ?></option>
								<option value="Y"><?php echo ynExpander($guid, 'Y') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonScaleID" id="gibbonScaleID" value="<?php echo $gibbonScaleID ?>" type="hidden">
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