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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'];

        echo "<div class='trail'>";
        if ($highestAction == 'Individual Needs Records_view') {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/in_view.php'>".__($guid, 'All Student Records')."</a> > </div><div class='trailEnd'>".__($guid, 'View Individual Needs Record').'</div>';
        } elseif ($highestAction == 'Individual Needs Records_viewContribute') {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/in_view.php'>".__($guid, 'All Student Records')."</a> > </div><div class='trailEnd'>".__($guid, 'View & Contribute To Individual Needs Record').'</div>';
        } elseif ($highestAction == 'Individual Needs Records_viewEdit') {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/in_view.php'>".__($guid, 'All Student Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Individual Needs Record').'</div>';
        }
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $source = null;
            if (isset($_GET['source'])) {
                $source = $_GET['source'];
            }
            $gibbonINDescriptorID = null;
            if (isset($_GET['gibbonINDescriptorID'])) {
                $gibbonINDescriptorID = $_GET['gibbonINDescriptorID'];
            }
            $gibbonAlertLevelID = null;
            if (isset($_GET['gibbonAlertLevelID'])) {
                $gibbonAlertLevelID = $_GET['gibbonAlertLevelID'];
            }
            $gibbonRollGroupID = null;
            if (isset($_GET['gibbonRollGroupID'])) {
                $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
            }
            $gibbonYearGroupID = null;
            if (isset($_GET['gibbonYearGroupID'])) {
                $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
            }

            if ($search != '' and $source == '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/in_view.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            } elseif (($gibbonINDescriptorID != '' or $gibbonAlertLevelID != '' or $gibbonRollGroupID != '' or $gibbonYearGroupID != '') and $source == 'summary') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/in_summary.php&gibbonINDescriptorID='.$gibbonINDescriptorID.'&gibbonAlertLevelID='.$gibbonAlertLevelID.'&=gibbonRollGroupID'.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $gibbonINArchiveID = null;
            if (isset($_POST['gibbonINArchiveID'])) {
                if ($_POST['gibbonINArchiveID'] != '') {
                    $gibbonINArchiveID = $_POST['gibbonINArchiveID'];
                }
            }
            $archiveStrategies = null;
            $archiveTargets = null;
            $archiveNotes = null;
            $archiveDescriptors = null;

            try {
                $dataArchive = array('gibbonPersonID' => $gibbonPersonID);
                $sqlArchive = 'SELECT * FROM gibbonINArchive WHERE gibbonPersonID=:gibbonPersonID ORDER BY archiveTimestamp DESC';
                $resultArchive = $connection2->prepare($sqlArchive);
                $resultArchive->execute($dataArchive);
            } catch (PDOException $e) {
            }
            if ($resultArchive->rowCount() > 0) {
                echo "<div class='linkTop'>";
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/in_edit.php&gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>";
                echo __($guid, 'Archived Plans').' ';
                echo '<select name="gibbonINArchiveID" style="float: none; width: 200px; margin-top: -10px; margin-bottom: 5px">';
                echo "<option value=''>".__($guid, 'Current Plan').'</option>';
                while ($rowArchive = $resultArchive->fetch()) {
                    $selected = '';
                    if ($rowArchive['gibbonINArchiveID'] == $gibbonINArchiveID) {
                        $selected = 'selected';
                        $archiveStrategies = $rowArchive['strategies'];
                        $archiveTargets = $rowArchive['targets'];
                        $archiveNotes = $rowArchive['notes'];
                        $archiveDescriptors = $rowArchive['descriptors'];
                    }
                    echo "<option $selected value='".$rowArchive['gibbonINArchiveID']."'>".$rowArchive['archiveTitle'].' ('.dateConvertBack($guid, substr($rowArchive['archiveTimestamp'], 0, 10)).')</option>';
                }
                echo '</select>';
                echo "<input style='margin-top: 0px; margin-right: -2px' type='submit' value='".__($guid, 'Go')."'>";
                echo '</form>';
                echo '</div>';
            }

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student');
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
            echo '<i>'.__($guid, $row['yearGroup']).'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
            echo '<i>'.$row['rollGroup'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/in_editProcess.php?gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>";
            echo '<h3>';
            echo __($guid, 'Individual Needs Status');
            echo '</h3>';
            if ($highestAction == 'Individual Needs Records_view' or $highestAction == 'Individual Needs Records_viewContribute') {
                $statusTable = printINStatusTable($connection2, $gibbonPersonID, 'disabled');
            } elseif ($highestAction == 'Individual Needs Records_viewEdit') {
                if ($gibbonINArchiveID != '') {
                    $statusTable = printINStatusTable($connection2, $gibbonPersonID, 'disabled', $archiveDescriptors);
                } else {
                    $statusTable = printINStatusTable($connection2, $gibbonPersonID);
                }
            }

            if ($statusTable == false) {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
            } else {
                echo $statusTable;
            }

            echo '<h3>';
            echo __($guid, 'Individual Education Plan');
            echo '</h3>';

            if (is_null($gibbonINArchiveID) == false) { //SHOW ARCHIVE
                    ?>
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Targets') ?></span><br/>
								<?php
                                echo '<p>'.$archiveTargets.'</p>';
                ?>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Teaching Strategies') ?></span><br/>
								<?php
                                echo '<p>'.$archiveStrategies.'</p>';
                ?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Notes & Review') ?></span><br/>
								<?php
                                echo '<p>'.$archiveNotes.'</p>';
                ?>
							</td>
						</tr>
					</table>
					<?php

            } else { //SHOW CURRENT
                    try {
                        $dataIEP = array('gibbonPersonID' => $gibbonPersonID);
                        $sqlIEP = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                        $resultIEP = $connection2->prepare($sqlIEP);
                        $resultIEP->execute($dataIEP);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                if ($resultIEP->rowCount() > 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                } else {
                    $rowIEP = $resultIEP->fetch();
                    ?>	
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr>
								<td colspan=2 style='padding-top: 25px'> 
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Targets') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                                        echo getEditor($guid,  true, 'targets', $rowIEP['targets'], 20, true);
                                    } else {
                                        echo '<p>'.$rowIEP['targets'].'</p>';
                                    }
                    ?>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Teaching Strategies') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                                        echo getEditor($guid,  true, 'strategies', $rowIEP['strategies'], 20, true);
                                    } else {
                                        echo '<p>'.$rowIEP['strategies'].'</p>';
                                    }
                    ?>
								</td>
							</tr>
							<tr>
								<td colspan=2 style='padding-top: 25px'> 
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Notes & Review') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                                        echo getEditor($guid,  true, 'notes', $rowIEP['notes'], 20, true);
                                    } else {
                                        echo '<p>'.$rowIEP['notes'].'</p>';
                                    }
                    ?>
								</td>
							</tr>
							<?php
                            if ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                                ?>
								<tr>
									<td class="right" colspan=2>
										<input type="hidden" name="gibbonPersonID" value="<?php echo $gibbonPersonID ?>">
										<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
										<input type="submit" value="<?php echo __($guid, 'Submit');
                                ?>">
									</td>
								</tr>
								<?php

                            }
                    ?>
						</table>
						<?php

                }
            }
            echo '</form>';
        }
    }
    //Set sidebar
    $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
}
?>