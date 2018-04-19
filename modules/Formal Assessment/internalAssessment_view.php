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

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if ($highestAction == 'View Internal Assessments_all') { //ALL STUDENTS
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View All Internal Assessments').'</div>';
            echo '</div>';

            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }

            echo '<h3>';
            echo __($guid, 'Choose A Student');
            echo '</h3>';

            $form = Form::create("filter", $_SESSION[$guid]['absoluteURL']."/index.php", "get", "noIntBorder fullWidth standardForm");
			$form->setFactory(DatabaseFormFactory::create($pdo));
			
			$form->addHiddenValue('q', '/modules/Formal Assessment/internalAssessment_view.php');
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
				$row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]["gibbonSchoolYearID"], array())->selected($gibbonPersonID)->placeholder();
				
            $row = $form->addRow();
				$row->addSearchSubmit($gibbon->session);
				
			echo $form->getOutput();
			
			if ($gibbonPersonID) {
				echo '<h3>';
				echo __($guid, 'Internal Assessments');
				echo '</h3>';

				//Check for access
				try {
					$dataCheck = array('gibbonPersonID' => $gibbonPersonID);
					$sqlCheck = "SELECT DISTINCT gibbonPerson.* FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')";
					$resultCheck = $connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				} catch (PDOException $e) {
					echo "<div class='error'>".$e->getMessage().'</div>';
				}

				if ($resultCheck->rowCount() != 1) {
					echo "<div class='error'>";
					echo __($guid, 'The selected record does not exist, or you do not have access to it.');
					echo '</div>';
				} else {
					echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID);
				}
			}
		} elseif ($highestAction == 'View Internal Assessments_myChildrens') { //MY CHILDREN
			echo "<div class='trail'>";
			echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View My Childrens\'s Internal Assessments').'</div>';
			echo '</div>';

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
				$options = array();
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
						$options[$rowChild['gibbonPersonID']]=formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
					}
				}

				$gibbonPersonID = (isset($_GET['search']))? $_GET['search'] : null;

				if (count($options) == 0) {
					echo "<div class='error'>";
					echo __($guid, 'Access denied.');
					echo '</div>';
				} elseif (count($options) == 1) {
					$gibbonPersonID = key($options);
				} else {
					echo '<h2>';
					echo 'Choose Student';
					echo '</h2>';

					$form = Form::create("filter", $_SESSION[$guid]['absoluteURL']."/index.php", "get");
					$form->setClass('noIntBorder fullWidth standardForm');

					$form->addHiddenValue('q', '/modules/Formal Assessment/internalAssessment_view.php');
					$form->addHiddenValue('address', $_SESSION[$guid]['address']);
					
					$row = $form->addRow();
						$row->addLabel('search', __('Student'));
						$row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

					$row = $form->addRow();
						$row->addSearchSubmit($gibbon->session);

					echo $form->getOutput();
                }
				
                $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
                $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');

                if ($gibbonPersonID != '' and count($options) > 0) {
                    //Confirm access to this student
                    try {
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'date' => date('Y-m-d'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $rowChild = $resultChild->fetch();
                        echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, 'parent');
                    }
                }
            }
        } else { //My Internal Assessments
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View My Internal Assessments').'</div>';
            echo '</div>';

            echo '<h3>';
            echo __($guid, 'Internal Assessments');
            echo '</h3>';

            echo getInternalAssessmentRecord($guid, $connection2, $_SESSION[$guid]['gibbonPersonID'], 'student');
        }
    }
}
?>
