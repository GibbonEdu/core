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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityType_rollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Activity Type by Roll Group').'</div>';
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

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activityType_rollGroup.php");

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
            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'No Type');
            echo '</th>';
            $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
            if ($options != '') {
                $options = explode(',', $options);
                for ($i = 0; $i < count($options); ++$i) {
                    echo '<th>';
                    $optionExplode = explode('/', trim($options[$i]));
                    for ($y = 0; $y < count($optionExplode); ++$y) {
                        echo $optionExplode[$y].'<br/>';
                    }
                    echo '</th>';
                }
            }
            echo '<th>';
            echo __($guid, 'Total');
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

                    //Set status for sql statements
                    $status = " AND NOT status='Not Accepted'";
                if ($_GET['status'] == 'Accepted') {
                    $status = " AND status='Accepted'";
                }

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $row['name'];
                echo '</td>';
                echo '<td>';
				//List activities seleted in title of student name
				try {
					$dataActivities = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
					$sqlActivities = "SELECT gibbonActivity.* FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID $status ORDER BY name";
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
                echo '<td>';
                try {
                    $dataCount = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlCount = "SELECT gibbonActivity.*, gibbonActivityStudent.status FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' $status AND type=''";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultCount->rowCount() > 0) {
                    echo $resultCount->rowCount();
                } else {
                    echo '0';
                }
                echo '</td>';
                for ($i = 0; $i < count($options); ++$i) {
                    echo '<td>';
                    try {
                        $dataCount = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlCount = "SELECT gibbonActivity.*, gibbonActivityStudent.status FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' $status AND type='".trim($options[$i])."'";
                        $resultCount = $connection2->prepare($sqlCount);
                        $resultCount->execute($dataCount);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultCount->rowCount() > 0) {
                        echo $resultCount->rowCount();
                    } else {
                        echo '0';
                    }
                    echo '</td>';
                }
                echo '<td>';
				//Get total
				try {
					$dataCount = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
					$sqlCount = "SELECT gibbonActivity.*, gibbonActivityStudent.status FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' $status";
					$resultCount = $connection2->prepare($sqlCount);
					$resultCount->execute($dataCount);
				} catch (PDOException $e) {
					echo "<div class='error'>".$e->getMessage().'</div>';
				}
                if ($resultCount->rowCount() > 0) {
                    echo $resultCount->rowCount();
                } else {
                    echo '0';
                }
                echo '</td>';
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
