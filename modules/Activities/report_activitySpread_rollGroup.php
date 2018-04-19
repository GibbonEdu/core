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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activitySpread_rollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Activity Spread by Roll Group').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Roll Group');
    echo '</h2>';

    $gibbonRollGroupID = null;
    if (isset($_GET['gibbonRollGroupID'])) {
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
    }

    $status = null;
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activitySpread_rollGroup.php");

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->isRequired();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray(array('Accepted' => __('Accepted'), 'Registered' => __('Registered')))->selected($status)->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonRollGroupID != '') {
        $output = '';
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th rowspan=2>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th rowspan=2>';
            echo __($guid, 'Student');
            echo '</th>';
                    //Get terms and days of week
                    $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
            $days = false;

            try {
                $dataDays = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlDays = "SELECT DISTINCT gibbonDaysOfWeek.* FROM gibbonDaysOfWeek JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) JOIN gibbonActivity ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND schoolDay='Y' ORDER BY sequenceNumber";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            while ($rowDays = $resultDays->fetch()) {
                $days = $days.$rowDays['gibbonDaysOfWeekID'].',';
                $days = $days.$rowDays['nameShort'].',';
            }
            if ($days != false) {
                $days = substr($days, 0, (strlen($days) - 1));
                $days = explode(',', $days);
            }

			//Create columns
			$columns = array();
            $columnCount = 0;
            for ($i = 0; $i < count($terms); $i = $i + 2) {
                echo '<th colspan='.count($days) / 2 .'>';
                echo $terms[($i + 1)];
                echo '</th>';
            }
            echo '</tr>';
            echo "<tr class='head'>";
            for ($i = 0; $i < count($terms); $i = $i + 2) {
                for ($j = 0; $j < count($days); $j = $j + 2) {
                    echo '<th>';
                    echo __($guid, $days[($j + 1)]);
                    $columns[$columnCount][0] = $terms[$i];
                    $columns[$columnCount][1] = $days[$j];
                    ++$columnCount;
                    echo '</th>';
                }
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
                echo $row['name'];
                echo '</td>';
                echo '<td>';
				//List activities seleted in title of student name
				try {
					$dataActivities = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
					$sqlActivities = "SELECT gibbonActivity.* FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT status='Not Accepted' ORDER BY name";
					$resultActivities = $connection2->prepare($sqlActivities);
					$resultActivities->execute($dataActivities);
				} catch (PDOException $e) {
					echo "<div class='error'>".$e->getMessage().'</div>';
				}

                $title = '';
                while ($rowActivities = $resultActivities->fetch()) {
                    $title = $title.$rowActivities['name'].' | ';
                }
                $title = substr($title, 0, -3);
                echo "<span title='$title'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</span>';
                echo '</td>';
                for ($i = 0; $i < $columnCount; ++$i) {
                    echo '<td>';
                    try {
                        $dataReg = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $row['gibbonPersonID'], 'gibbonDaysOfWeekID' => $columns[$i][1], 'gibbonSchoolYearTermIDList' => '%'.$columns[$i][0].'%');
                        if ($_GET['status'] == 'Accepted') {
                            $sqlReg = "SELECT DISTINCT gibbonActivity.name, gibbonActivityStudent.status FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonDaysOfWeekID=:gibbonDaysOfWeekID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND status='Accepted'";
                        } else {
                            $sqlReg = "SELECT DISTINCT gibbonActivity.name, gibbonActivityStudent.status FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonDaysOfWeekID=:gibbonDaysOfWeekID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                        }
                        $resultReg = $connection2->prepare($sqlReg);
                        $resultReg->execute($dataReg);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    $title = '';
                    $notAccepted = false;
                    while ($rowReg = $resultReg->fetch()) {
                        $title .= $rowReg['name'].', ';
                        if ($rowReg['status'] != 'Accepted') {
                            $notAccepted = true;
                        }
                    }
                    if ($title == '') {
                        $title = __($guid, 'There are no records to display.');
                    } else {
                        $title = substr($title, 0, -2);
                    }
                    echo "<span title='".htmlPrep($title)."'>".$resultReg->rowCount().'<span>';
                    if ($notAccepted == true and $_GET['status'] == 'Registered') {
                        echo "<span style='color: #cc0000' title='".__($guid, 'Some activities not accepted.')."'> *</span>";
                    }
                    echo '</td>';
                }

                echo '</tr>';
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=2>';
                echo __($guid, 'There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
