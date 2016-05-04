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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/rollGroup_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Roll Groups')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Roll Group').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/rollGroup_manage_edit.php&gibbonRollGroupID='.$_GET['editID'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
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
            $row = $result->fetch();
            ?>
	
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage_addProcess.php' ?>">
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
							<span class="emphasis small">Needs to be unique in school year.</span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small">Needs to be unique in school year.</span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td rowspan=3> 
							<b><?php echo __($guid, 'Tutors') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Up to 3 per roll group. The first-listed will be marked as "Main Tutor".') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="gibbonPersonIDTutor">
								<?php
                                echo "<option value=''></option>";
            try {
                $data = array();
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
            while ($row = $result->fetch()) {
                echo "<option value='".$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true).'</option>';
            }
            ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td class="right">
							<select class="standardWidth" name="gibbonPersonIDTutor2">
								<?php
                                echo "<option value=''></option>";
            try {
                $data = array();
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
            while ($row = $result->fetch()) {
                echo "<option value='".$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true).'</option>';
            }
            ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td class="right">
							<select class="standardWidth" name="gibbonPersonIDTutor3">
								<?php
                                echo "<option value=''></option>";
            try {
                $data = array();
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
            while ($row = $result->fetch()) {
                echo "<option value='".$row['gibbonPersonID']."'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true).'</option>';
            }
            ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Location') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
            try {
                $dataSelect = array();
                $sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($row['gibbonSpaceID'] == $rowSelect['gibbonSpaceID']) {
                    $selected = 'selected';
                }
                echo "<option $selected value='".$rowSelect['gibbonSpaceID']."'>".htmlPrep($rowSelect['name']).'</option>';
            }
            ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Next Roll Group') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Sets student progression on rollover.') ?></span>
						</td>
						<td class="right">
							<?php
                             $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);

            if ($nextYear == '') {
                echo "<div class='warning'>";
                echo 'The next school year cannot be determined, so this value cannot be set.';
                echo '</div>';
            } else {
                echo "<select style='width: 302px' name='gibbonRollGroupIDNext'>";
                echo "<option value=''></option>";
                try {
                    $dataSelect = array('gibbonSchoolYearID' => $nextYear);
                    $sqlSelect = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    echo "<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }
                echo '</select>';
            }
            ?>		
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Website') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Include http://') ?></span>
						</td>
						<td class="right">
							<input name="website" id="website" maxlength=255 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var website=new LiveValidation('website');
								website.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
							</script>	
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