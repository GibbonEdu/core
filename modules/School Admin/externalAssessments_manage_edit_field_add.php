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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'];

    if ($gibbonExternalAssessmentID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
            $sql = 'SELECT name FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessments_manage.php'>".__($guid, 'Manage External Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'>".__($guid, 'Edit External Assessment')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Field').'</div>';
            echo '</div>';

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentFieldID='.$_GET['editID'].'&gibbonExternalAssessmentID='.$_GET['gibbonExternalAssessmentID'];
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/externalAssessments_manage_edit_field_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'External Assessment') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name')  ?> *</b><br/>
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
							<b><?php echo __($guid, 'Category') ?> *</b><br/>
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
							<b><?php echo __($guid, 'Order') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Order in which fields appear within category<br/>Should be unique for this category.') ?><br/></span>
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
							<b><?php echo __($guid, 'Grade Scale') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Grade scale used to control values that can be assigned.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonScaleID" id="gibbonScaleID" class="standardWidth">
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
            }
            ?>				
							</select>
							<script type="text/javascript">
								var gibbonScaleID=new LiveValidation('gibbonScaleID');
								gibbonScaleID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Year Groups') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Year groups to which this field is relevant.') ?></span>
						</td>
						<td class="right">
							<?php 
                            echo "<fieldset style='border: none'>";
            ?>
							<script type="text/javascript">
								$(function () {
									$('.checkall').click(function () {
										$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
									});
								});
							</script>
							<?php
                            echo __($guid, 'All').' / '.__($guid, 'None')." <input type='checkbox' class='checkall'><br/>";
            $yearGroups = getYearGroups($connection2);
            if ($yearGroups == '') {
                echo '<i>'.__($guid, 'No year groups available.').'</i>';
            } else {
                for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
                    echo __($guid, $yearGroups[($i + 1)])." <input type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
                    echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
                }
            }
            echo '</fieldset>';
            ?>
							<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
						</td>
					</tr>
					
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
							<input name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID" value="<?php echo $gibbonExternalAssessmentID ?>" type="hidden">
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