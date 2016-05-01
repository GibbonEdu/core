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

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php'>".__($guid, 'Manage Medical Forms')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Medical Form').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if person medical specified
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'];
    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    if ($gibbonPersonMedicalID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
            $sql = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_editProcess.php?gibbonPersonMedicalID='.$gibbonPersonMedicalID."&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Person') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
                            try {
                                $dataSelect = array('gibbonPersonID' => $row['gibbonPersonID']);
                                $sqlSelect = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
            $rowSelect = $resultSelect->fetch();
            ?>	
							<input readonly name="name" id="name" maxlength=255 value="<?php echo formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student');
            ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Blood Type') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="bloodType">
								<option <?php if ($row['bloodType'] == '') {
    echo 'selected ';
}
            ?>value=""></option>
								<option <?php if ($row['bloodType'] == 'O+') {
    echo 'selected ';
}
            ?>value="O+">O+</option>
								<option <?php if ($row['bloodType'] == 'A+') {
    echo 'selected ';
}
            ?>value="A+">A+</option>
								<option <?php if ($row['bloodType'] == 'B+') {
    echo 'selected ';
}
            ?>value="B+">B+</option>
								<option <?php if ($row['bloodType'] == 'AB+') {
    echo 'selected ';
}
            ?>value="AB+">AB+</option>
								<option <?php if ($row['bloodType'] == 'O-') {
    echo 'selected ';
}
            ?>value="O-">O-</option>
								<option <?php if ($row['bloodType'] == 'A-') {
    echo 'selected ';
}
            ?>value="A-">A-</option>
								<option <?php if ($row['bloodType'] == 'B-') {
    echo 'selected ';
}
            ?>value="B-">B-</option>
								<option <?php if ($row['bloodType'] == 'AB-') {
    echo 'selected ';
}
            ?>value="AB-">AB-</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Long-Term Medication?') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="longTermMedication">
								<option <?php if ($row['longTermMedication'] == '') {
    echo 'selected ';
}
            ?>value=""></option>
								<option <?php if ($row['longTermMedication'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y">Y</option>
								<option <?php if ($row['longTermMedication'] == 'N') {
    echo 'selected ';
}
            ?>value="N">N</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Medication Details') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="longTermMedicationDetails" id="longTermMedicationDetails" rows=8 class="standardWidth"><?php echo $row['longTermMedicationDetails'] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Tetanus Within Last 10 Years?') ?></b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="tetanusWithin10Years">
								<option <?php if ($row['tetanusWithin10Years'] == '') {
    echo 'selected ';
}
            ?>value=""></option>
								<option <?php if ($row['tetanusWithin10Years'] == 'Y') {
    echo 'selected ';
}
            ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['tetanusWithin10Years'] == 'N') {
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
							<input type="hidden" name="gibbonPersonMedicalID" value="<?php echo $row['gibbonPersonMedicalID'] ?>">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

            echo '<h2>';
            echo __($guid, 'Medical Conditions');
            echo '</h2>';

            try {
                $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
                $sql = 'SELECT gibbonPersonMedicalCondition.*, gibbonAlertLevel.name AS risk FROM gibbonPersonMedicalCondition JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY gibbonPersonMedicalCondition.name';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_add.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID']."&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Risk');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Details');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Medication');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
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
                    ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo __($guid, $row['name']);
                    echo '</td>';
                    echo '<td>';
                    echo __($guid, $row['risk']);
                    echo '</td>';
                    echo '<td>';
                    if ($row['triggers'] != '') {
                        echo '<b>'.__($guid, 'Triggers').':</b> '.$row['triggers'].'<br/>';
                    }
                    if ($row['reaction'] != '') {
                        echo '<b>'.__($guid, 'Reaction').':</b> '.$row['reaction'].'<br/>';
                    }
                    if ($row['response'] != '') {
                        echo '<b>'.__($guid, 'Response').':</b> '.$row['response'].'<br/>';
                    }
                    if ($row['lastEpisode'] != '') {
                        echo '<b>'.__($guid, 'Last Episode').':</b> '.dateConvertBack($guid, $row['lastEpisode']).'<br/>';
                    }
                    if ($row['lastEpisodeTreatment'] != '') {
                        echo '<b>'.__($guid, 'Last Episode Treatment').':</b> '.$row['lastEpisodeTreatment'].'<br/>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['medication'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['comment'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_edit.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID'].'&gibbonPersonMedicalConditionID='.$row['gibbonPersonMedicalConditionID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_delete.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID'].'&gibbonPersonMedicalConditionID='.$row['gibbonPersonMedicalConditionID']."&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>