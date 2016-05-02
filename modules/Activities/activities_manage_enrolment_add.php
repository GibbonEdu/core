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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_manage.php'>".__($guid, 'Manage Activities')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$_GET['gibbonActivityID'].'&search='.$_GET['search']."'>".__($guid, 'Activity Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Student').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonActivityID = $_GET['gibbonActivityID'];

    if ($gibbonActivityID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
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
            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
            if ($_GET['search'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage.php&search='.$_GET['search']."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/activities_manage_enrolment_addProcess.php?gibbonActivityID=$gibbonActivityID&search=".$_GET['search'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Name') ?></b><br/>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php
                    if ($dateType == 'Date') {
                        ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Listing Dates') ?></b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<?php echo dateConvertBack($guid, $row['listingStart']).'-'.dateConvertBack($guid, $row['listingEnd']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Program Dates') ?></b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<?php echo dateConvertBack($guid, $row['programStart']).'-'.dateConvertBack($guid, $row['programEnd']) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    } else {
                        ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Terms') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                        $termList = '';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                $termList .= $terms[($i + 1)].', ';
                            }
                        }
                        if ($termList == '') {
                            $termList = '-, ';
                        }
                        ?>
								<input readonly name="name" id="name" maxlength=20 value="<?php echo substr($termList, 0, -2) ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    }
            ?>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Students') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<optgroup label='--<?php echo __($guid, 'Enrolable Students') ?>--'>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelectWhere = '';
                                    if ($row['gibbonYearGroupIDList'] != '') {
                                        $years = explode(',', $row['gibbonYearGroupIDList']);
                                        for ($i = 0; $i < count($years); ++$i) {
                                            if ($i == 0) {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere .= 'AND (gibbonYearGroupID=:'.$years[$i];
                                            } else {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere .= ' OR gibbonYearGroupID=:'.$years[$i];
                                            }

                                            if ($i == (count($years) - 1)) {
                                                $sqlSelectWhere .= ')';
                                            }
                                        }
                                    } else {
                                        $sqlSelectWhere = ' FALSE';
                                    }
                                    $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY name, surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }

            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', $rowSelect['preferredName'], $rowSelect['surname'], 'Student', true).'</option>';
            }
            ?>
								</optgroup>
								<optgroup label='--<?php echo __($guid, 'All Users') ?>--'>
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
            while ($rowSelect = $resultSelect->fetch()) {
                $expected = '';
                if ($rowSelect['status'] == 'Expected') {
                    $expected = ' (Expected)';
                }
                echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')'.$expected.'</option>';
            }
            ?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Status') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="status" id="status" class="standardWidth">
								<option value="Accepted"><?php echo __($guid, 'Accepted') ?></option>
								<?php
                                $enrolment = getSettingByScope($connection2, 'Activities', 'enrolmentType');
            if ($enrolment == 'Competitive') {
                echo "<option value='Waiting List'>".__($guid, 'Waiting List').'</option>';
            } else {
                echo "<option value='Pending'>".__($guid, 'Pending').'</option>';
            }
            ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
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