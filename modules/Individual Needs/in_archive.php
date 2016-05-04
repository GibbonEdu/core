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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_archive.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Archive Records').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/in_archiveProcess.php'?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td> 
					<b><?php echo __($guid, 'Delete Current Plans?') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Deletes Individual Education Plan fields only, not Individual Needs Status fields.') ?></span>
				</td>
				<td class="right">
					<select name="deleteCurrentPlans" id="deleteCurrentPlans" class="standardWidth">
						<option value="N"><?php echo ynExpander($guid, 'N') ?></option>
						<option value="Y"><?php echo ynExpander($guid, 'Y') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Archive Title') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input type='text' maxlength=50 name="title" id="title" class="standardWidth" value=''/>
					<script type="text/javascript">
						var title=new LiveValidation('title');
						title.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td style='width: 275px; vertical-align: top'> 
					<b><?php echo __($guid, 'Students') ?> *</b><br/>
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
                    try {
                        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlSelect = "SELECT surname, preferredName, gibbonIN.* FROM gibbonPerson JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                        $resultSelect = $connection2->prepare($sqlSelect);
                        $resultSelect->execute($dataSelect);
                    } catch (PDOException $e) {
                        echo "<div class='error'>";
                        echo $e->getMessage();
                        echo '</div>';
                    }
    echo __($guid, 'All/None')." <input type='checkbox' class='checkall'><br/>";
    if ($resultSelect->rowCount() < 1) {
        echo '<i>'.__($guid, 'No year groups available.').'</i>';
    } else {
        while ($rowSelect = $resultSelect->fetch()) {
            echo formatName('', $rowSelect['preferredName'], $rowSelect['surname'], 'Student', true)." <input type='checkbox' value='".$rowSelect['gibbonPersonID']."' name='gibbonPersonID[]'><br/>";
        }
    }
    echo '</fieldset>';?>
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
?>