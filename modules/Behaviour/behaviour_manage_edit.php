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

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_edit.php') == false) {
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
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage.php'>".__($guid, 'Manage Behaviour Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonBehaviourID = $_GET['gibbonBehaviourID'];
        if ($gibbonBehaviourID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Behaviour Records_all') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC';
                } elseif ($highestAction == 'Manage Behaviour Records_my') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC';
                }
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
                echo "<div class='linkTop'>";
                $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
                if ($policyLink != '') {
                    echo "<a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
                }
                if ($_GET['gibbonPersonID'] != '' or $_GET['gibbonRollGroupID'] != '' or $_GET['gibbonYearGroupID'] != '' or $_GET['type'] != '') {
                    if ($policyLink != '') {
                        echo ' | ';
                    }
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']."'>".__($guid, 'Back to Search Results').'</a>';
                }
                echo '</div>';

                //Let's go!
                $values = $result->fetch();

                $form = Form::create('addform', $_SESSION[$guid]['absoluteURL'].'/modules/Behaviour/behaviour_manage_editProcess.php?gibbonBehaviourID='.$gibbonBehaviourID.'&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']);
                    $form->setClass('smallIntBorder fullWidth');
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_add.php");

                //Student
                $row = $form->addRow();
                    $row->addLabel('students', __('Student'));
                    $row->addTextField('students')->setValue(formatName('', $values['preferredNameStudent'], $values['surnameStudent'], 'Student'))->readonly();
                    $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                //Date
                $row = $form->addRow();
                	$row->addLabel('date', __('Date'));
                	$row->addDate('date')->setValue(dateConvertBack($guid, $values['date']))->isRequired()->readonly();

                //Date
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addTextField('type')->setValue($values['type'])->isRequired()->readonly();

                //Descriptor
                if ($enableDescriptors == 'Y') {
                    if ($values['type'] == 'Negative') {
                        $descriptors = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors');
                    }
                    else {
                        $descriptors = getSettingByScope($connection2, 'Behaviour', 'positiveDescriptors');
                    }
                    $descriptors = (!empty($descriptors))? explode(',', $descriptors) : array();

                    $row = $form->addRow();
                		$row->addLabel('descriptor', __('Descriptor'));
                        $row->addSelect('descriptor')
                            ->fromArray($descriptors)
                            ->selected($values['descriptor'])
                            ->isRequired()
                            ->placeholder();
                }

                //Level
                if ($enableLevels == 'Y') {
                    $optionsLevels = getSettingByScope($connection2, 'Behaviour', 'levels');
                    if ($optionsLevels != '') {
                        $optionsLevels = explode(',', $optionsLevels);
                    }
                    $row = $form->addRow();
                    	$row->addLabel('level', __('Level'));
                    	$row->addSelect('level')->fromArray($optionsLevels)->selected($values['level'])->placeholder();
                }

                //Incident
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('comment', __('Incident'));
                    $column->addTextArea('comment')->setRows(5)->setClass('fullWidth')->setValue($values['comment']);

                //Follow Up
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('followup', __('Follow Up'));
                    $column->addTextArea('followup')->setRows(5)->setClass('fullWidth')->setValue($values['followup']);

                //Lesson link
                $lessons = array();
                $minDate = date('Y-m-d', (strtotime($values['date']) - (24 * 60 * 60 * 30)));
                try {
                    $dataSelect = array('date' => date('Y-m-d', strtotime($values['date'])), 'minDate' => $minDate, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $values['gibbonPersonID']);
                    $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date AND date>=:minDate) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $show = true;
                    if ($highestAction == 'Manage Behaviour Records_my') {
                        try {
                            $dataShow = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                            $sqlShow = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                            $resultShow = $connection2->prepare($sqlShow);
                            $resultShow->execute($dataShow);
                        } catch (PDOException $e) {
                        }
                        if ($resultShow->rowCount() != 1) {
                            $show = false;
                        }
                    }
                    if ($show == true) {
                        $submission = '';
                        if ($rowSelect['homework'] == 'Y') {
                            $submission = 'HW';
                            if ($rowSelect['homeworkSubmission'] == 'Y') {
                                $submission .= '+OS';
                            }
                        }
                        if ($submission != '') {
                            $submission = ' - '.$submission;
                        }
                        $selected = '';
                        if ($rowSelect['gibbonPlannerEntryID'] == $values['gibbonPlannerEntryID']) {
                            $selected = 'selected';
                        }
                        $lessons[$rowSelect['gibbonPlannerEntryID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' '.htmlPrep($rowSelect['lesson']).' - '.substr(dateConvertBack($guid, $rowSelect['date']), 0, 5).$submission;
                    }
                }

                $row = $form->addRow();
                    $row->addLabel('gibbonPlannerEntryID', __('Link To Lesson?'))->description(__('From last 30 days'));
                    if (count($lessons) < 1) {
                        $row->addSelect('gibbonPlannerEntryID')->placeholder();
                    }
                    else {
                        $row->addSelect('gibbonPlannerEntryID')->fromArray($lessons)->placeholder()->selected($values['gibbonPlannerEntryID']);
                    }

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
?>
