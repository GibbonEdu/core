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
            if (isset($_POST['gibbonINArchiveID']) && $_POST['gibbonINArchiveID'] != '') {
                $gibbonINArchiveID = $_POST['gibbonINArchiveID'];
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
                $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID, 'disabled');
            } elseif ($highestAction == 'Individual Needs Records_viewEdit') {
                if ($gibbonINArchiveID != '') {
                    $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID, 'disabled', $archiveDescriptors);
                } else {
                    $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID);
                }
            }

            if ($statusTable == false) {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
            } else {
                echo $statusTable;
            }

            //LIST EDUCATIONAL ASSISTANTS
            echo '<h3>';
            echo __($guid, 'Educational Assistants');
            echo '</h3>';

            try {
                $data = array('gibbonPersonIDStudent' => $gibbonPersonID);
                $sql = "SELECT gibbonPersonIDAssistant, preferredName, surname, comment FROM gibbonINAssistant JOIN gibbonPerson ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='warning'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                if ($highestAction == 'Individual Needs Records_viewEdit') {
                    echo '<th>';
                    echo __($guid, 'Action');
                    echo '</th>';
                }
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
                    echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true);
                    echo '</td>';
                    echo '<td>';
                    echo $row['comment'];
                    echo '</td>';
                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                        echo '<td>';
                        echo "<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record? Any unsaved changes to this record will be lost.')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/in_edit_assistant_deleteProcess.php?address='.$_GET['q'].'&gibbonPersonIDAssistant='.$row['gibbonPersonIDAssistant']."&gibbonPersonIDStudent=$gibbonPersonID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
            }

            //ADD EDUCATIONAL ASSISTANTS
            if ($highestAction == 'Individual Needs Records_viewEdit') {
                echo '<h3>';
                echo __($guid, 'Add New Assistants');
                echo '</h3>';
                ?>
                <table class='smallIntBorder fullWidth' cellspacing='0'>
                    <tr>
                        <td>
                            <b>Staff</b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
                        </td>
                        <td class="right">
                            <select name="staff[]" id="staff[]" multiple class='standardWidth' style="height: 150px">
                                <?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                while ($rowSelect = $resultSelect->fetch()) {
                                    echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Comment') ?></b><br/>
                        </td>
                        <td class="right">
                            <textarea rows=4 name="comment" id="comment" class="standardWidth"></textarea>
                        </td>
                    </tr>

                    </table>
                <?php
            }

            //DISPLAY AND EDIT IEP
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
                                echo '<p>'.$archiveTargets.'</p>'; ?>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Teaching Strategies') ?></span><br/>
								<?php
                                echo '<p>'.$archiveStrategies.'</p>'; ?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'>
								<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Notes & Review') ?></span><br/>
								<?php
                                echo '<p>'.$archiveNotes.'</p>'; ?>
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
                    //Set field values/templates
                    if ($resultIEP->rowCount() == 0) { //New record, get templates if they exist
                        $targets = getSettingByScope($connection2, 'Individual Needs', 'targetsTemplate');
                        $strategies = getSettingByScope($connection2, 'Individual Needs', 'teachingStrategiesTemplate');
                        $notes = getSettingByScope($connection2, 'Individual Needs', 'notesReviewTemplate');
                    }
                    else { //Existing record, set values from database
                        $rowIEP = $resultIEP->fetch();
                        $targets = $rowIEP['targets'];
                        $strategies = $rowIEP['strategies'];
                        $notes = $rowIEP['notes'];
                    }
                    ?>
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr>
								<td colspan=2 style='padding-top: 25px'>
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Targets') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                                        echo getEditor($guid,  true, 'targets', $targets, 20, true);
                                    } else {
                                        echo '<p>'.$targets.'</p>';
                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Teaching Strategies') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                                        echo getEditor($guid,  true, 'strategies', $strategies, 20, true);
                                    } else {
                                        echo '<p>'.$strategies.'</p>';
                                    }
                   		 			?>
								</td>
							</tr>
							<tr>
								<td colspan=2 style='padding-top: 25px'>
									<span style='font-weight: bold; font-size: 135%'><?php echo __($guid, 'Notes & Review') ?></span><br/>
									<?php
                                    if ($highestAction == 'Individual Needs Records_viewEdit') {
                                        echo getEditor($guid,  true, 'notes', $notes, 20, true);
                                    } else {
                                        echo '<p>'.$notes.'</p>';
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
										<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
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
