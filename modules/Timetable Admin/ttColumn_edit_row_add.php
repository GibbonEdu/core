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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit_row_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonTTColumnID = $_GET['gibbonTTColumnID'];

    if ($gibbonTTColumnID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonTTColumnID' => $gibbonTTColumnID);
            $sql = 'SELECT name AS columnName FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID';
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/ttColumn.php'>".__($guid, 'Manage Columns')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/ttColumn_edit.php&gibbonTTColumnID=$gibbonTTColumnID'>".__($guid, 'Edit Column')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Column Row').'</div>';
            echo '</div>';

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable Admin/ttColumn_edit_row_edit.php&gibbonTTColumnRowID='.$_GET['editID'].'&gibbonTTColumnID='.$_GET['gibbonTTColumnID'];
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/ttColumn_edit_row_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Column') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="columnName" id="columnName" maxlength=20 value="<?php echo $row['columnName'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var courseName=new LiveValidation('courseName');
								coursename2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this column.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=12 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this column.') ?></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Start Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							</script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT timeStart FROM gibbonTTColumnRow ORDER BY timeStart';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
            while ($rowAuto = $resultAuto->fetch()) {
                echo '"'.substr($rowAuto['timeStart'], 0, 5).'", ';
            }
            ?>
									];
									$( "#timeStart" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'End Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var timeEnd=new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							</script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT timeEnd FROM gibbonTTColumnRow ORDER BY timeEnd';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
            while ($rowAuto = $resultAuto->fetch()) {
                echo '"'.substr($rowAuto['timeEnd'], 0, 5).'", ';
            }
            ?>
									];
									$( "#timeEnd" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Type</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="type">
								<?php
                                echo "<option value='Lesson'>".__($guid, 'Lesson').'</option>';
            echo "<option value='Pastoral'>".__($guid, 'Pastoral').'</option>';
            echo "<option value='Sport'>".__($guid, 'Sport').'</option>';
            echo "<option value='Break'>".__($guid, 'Break').'</option>';
            echo "<option value='Service'>".__($guid, 'Service').'</option>';
            echo "<option value='Other'>".__($guid, 'Other').'</option>'; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonTTColumnID" id="gibbonTTColumnID" value="<?php echo $gibbonTTColumnID ?>" type="hidden">
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