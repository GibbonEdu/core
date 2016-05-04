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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $dateStamp = $_GET['dateStamp'];

    if ($gibbonSchoolYearID == '' or $dateStamp == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if (isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2, true) != true) {
            echo "<div class='error'>";
            echo __($guid, 'School is not open on the specified day.');
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

                //Proceed!
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/ttDates.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Tie Days to Dates')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=$dateStamp'>".__($guid, 'Edit Days in Date')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Day to Date').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/ttDates_edit_addProcess.php' ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Year') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var courseName=new LiveValidation('courseName');
									coursename2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Date') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input hidden name="dateStamp" id="dateStamp" maxlength=20 value="<?php echo $dateStamp ?>" type="text" class="standardWidth">
								<input readonly name="date" id="date" maxlength=20 value="<?php echo date('d/m/Y l', $dateStamp) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var courseName=new LiveValidation('courseName');
									coursename2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Day') ?></b><br/>
							</td>
							<td class="right">
								<select class="standardWidth" name="gibbonTTDayID">
									<?php
                                    //Check which timetables are not already linked to this date
                                    try {
                                        $dataCheck = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                                        $sqlCheck = 'SELECT * FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                        $resultCheck = $connection2->prepare($sqlCheck);
                                        $resultCheck->execute($dataCheck);
                                    } catch (PDOException $e) {
                                    }

                $tt = array();
                $count = 0;
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheckInner = array('gibbonTTID' => $rowCheck['gibbonTTID'], 'date' => date('Y-m-d', $dateStamp));
                        $sqlCheckInner = 'SELECT * FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) WHERE gibbonTT.gibbonTTID=:gibbonTTID AND date=:date';
                        $resultCheckInner = $connection2->prepare($sqlCheckInner);
                        $resultCheckInner->execute($dataCheckInner);
                    } catch (PDOException $e) {
                    }
                    if ($resultCheckInner->fetch() == 0) {
                        $tt[$count] = $rowCheck['gibbonTTID'];
                        ++$count;
                    }
                }
                for ($i = 0; $i < count($tt); ++$i) {
                    try {
                        $dataSelect = array('gibbonTTID' => $tt[$i]);
                        $sqlSelect = 'SELECT gibbonTTDay.*, gibbonTT.name AS ttName FROM gibbonTTDay JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE gibbonTT.gibbonTTID=:gibbonTTID ORDER BY gibbonTTDay.name';
                        $resultSelect = $connection2->prepare($sqlSelect);
                        $resultSelect->execute($dataSelect);
                    } catch (PDOException $e) {
                    }
                    while ($rowSelect = $resultSelect->fetch()) {
                        echo "<option value='".$rowSelect['gibbonTTDayID']."'>".$rowSelect['ttName'].': '.$rowSelect['nameShort'].'</option>';
                    }
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
}
?>