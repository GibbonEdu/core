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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student History').'</div>';
    echo '</div>';

    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if ($highestAction == 'Student History_all') {
            echo '<h2>';
            echo __($guid, 'Choose Student');
            echo '</h2>';

            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }
            ?>
			
			<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Student') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="gibbonPersonID">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
									$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								while ($rowSelect = $resultSelect->fetch()) {
									if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
										echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
									} else {
										echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
									}
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_studentHistory.php">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

            if ($gibbonPersonID != '') {
                $output = '';
                echo '<h2>';
                echo __($guid, 'Report Data');
                echo '</h2>';

                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
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
                    report_studentHistory($guid, $gibbonPersonID, true, $_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_studentHistory_print.php&gibbonPersonID=$gibbonPersonID", $connection2, $row['dateStart'], $row['dateEnd']);
                }
            }
        } elseif ($highestAction == 'Student History_myChildren') {
            $gibbonPersonID = null;
            if (isset($_GET['search'])) {
                $gibbonPersonID = $_GET['search'];
            }
            //Test data access field for permission
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'Access denied.');
                echo '</div>';
            } else {
                //Get child list
                $count = 0;
                $options = '';
                while ($row = $result->fetch()) {
                    try {
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    while ($rowChild = $resultChild->fetch()) {
                        $select = '';
                        if ($rowChild['gibbonPersonID'] == $gibbonPersonID) {
                            $select = 'selected';
                        }

                        $options = $options."<option $select value='".$rowChild['gibbonPersonID']."'>".formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true).'</option>';
                        $gibbonPersonID[$count] = $rowChild['gibbonPersonID'];
                        ++$count;
                    }
                }

                if ($count == 0) {
                    echo "<div class='error'>";
                    echo __($guid, 'Access denied.');
                    echo '</div>';
                } elseif ($count == 1) {
                    $gibbonPersonID = $gibbonPersonID[0];
                } else {
                    echo '<h2>';
                    echo __($guid, 'Choose');
                    echo '</h2>';

                    ?>
					<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
						<table class='noIntBorder' cellspacing='0' style="width: 100%">	
							<tr><td style="width: 30%"></td><td></td></tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Search For') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Preferred, surname, username.') ?></span>
								</td>
								<td class="right">
									<select name="search" id="search" class="standardWidth">
										<option value=""></value>
										<?php echo $options;
                    					?> 
									</select>
								</td>
							</tr>
							<tr>
								<td colspan=2 class="right">
									<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_studentHistory.php">
									<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
									<?php
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/report_studentHistory.php'>".__($guid, 'Clear Search').'</a>';
                    				?>
									<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php

                }

                if ($gibbonPersonID != '' and $count > 0) {
                    //Confirm access to this student
                    try {
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        @$resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $rowChild = $resultChild->fetch();

                        if ($gibbonPersonID != '') {
                            $output = '';
                            echo '<h2>';
                            echo __($guid, 'Report Data');
                            echo '</h2>';

                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID);
                                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
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
                                report_studentHistory($guid, $gibbonPersonID, true, $_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_studentHistory_print.php&gibbonPersonID=$gibbonPersonID", $connection2, $row['dateStart'], $row['dateEnd']);
                            }
                        }
                    }
                }
            }
        }
    }
}
?>