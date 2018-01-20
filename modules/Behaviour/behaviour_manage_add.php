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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
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
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage.php'>".__($guid, 'Manage Behaviour Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Add').'</div>';
        echo '</div>';

        $gibbonBehaviourID = isset($_GET['gibbonBehaviourID'])? $_GET['gibbonBehaviourID'] : null;
        $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
        $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : '';
        $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';
        $type = isset($_GET['type'])? $_GET['type'] : '';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID='.$_GET['editID'].'&gibbonPersonID='.$gibbonPersonID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type;
            $editID = $_GET['editID'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'success1' => 'Your request was completed successfully. You can now add extra information below if you wish.'));
        }

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1 or $gibbonBehaviourID == null) {
            echo "<div class='linkTop'>";
            $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
            if ($policyLink != '') {
                echo "<a target='_blank' href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
            }
            if ($gibbonPersonID != '' or $gibbonRollGroupID != '' or $gibbonYearGroupID != '' or $type != '') {
                if ($policyLink != '') {
                    echo ' | ';
                }
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID='.$gibbonPersonID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type."'>".__($guid, 'Back to Search Results').'</a>';
            }
            echo '</div>';

            $form = Form::create('addform', $_SESSION[$guid]['absoluteURL'].'/modules/Behaviour/behaviour_manage_addProcess.php?step=1&gibbonPersonID='.$gibbonPersonID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type);
                $form->setClass('smallIntBorder fullWidth');
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_add.php");
                $form->addRow()->addHeading(__('Step 1'));

            //Student
            $row = $form->addRow();
            	$row->addLabel('gibbonPersonID', __('Student'));
            	$row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder(__('Please select...'))->selected($gibbonPersonID)->isRequired();

            //Date
            $row = $form->addRow();
            	$row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            	$row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->isRequired();

            //Type
            $row = $form->addRow();
            	$row->addLabel('type', __('Type'));
            	$row->addSelect('type')->fromArray(array('Positive' => __('Positive'), 'Negative' => __('Negative')))->selected($type)->isRequired();

            //Descriptor
            if ($enableDescriptors == 'Y') {
                $negativeDescriptors = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors');
                $negativeDescriptors = (!empty($negativeDescriptors))? explode(',', $negativeDescriptors) : array();
                $positiveDescriptors = getSettingByScope($connection2, 'Behaviour', 'positiveDescriptors');
                $positiveDescriptors = (!empty($positiveDescriptors))? explode(',', $positiveDescriptors) : array();

                $chainedToNegative = array_combine($negativeDescriptors, array_fill(0, count($negativeDescriptors), 'Negative'));
                $chainedToPositive = array_combine($positiveDescriptors, array_fill(0, count($positiveDescriptors), 'Positive'));
                $chainedTo = array_merge($chainedToNegative, $chainedToPositive);

                $row = $form->addRow();
            		$row->addLabel('descriptor', __('Descriptor'));
                    $row->addSelect('descriptor')
                        ->fromArray($positiveDescriptors)
                        ->fromArray($negativeDescriptors)
                        ->chainedTo('type', $chainedTo)
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
                	$row->addSelect('level')->fromArray($optionsLevels)->placeholder();
            }

			//Incident
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('comment', __('Incident'));
            	$column->addTextArea('comment')->setRows(5)->setClass('fullWidth');

            //Follow Up
            $row = $form->addRow();
            	$column = $row->addColumn();
            	$column->addLabel('followup', __('Follow Up'));
            	$column->addTextArea('followup')->setRows(5)->setClass('fullWidth');

            $row = $form->addRow();
            	$row->addFooter();
            	$row->addSubmit();

            echo $form->getOutput();

        } elseif ($step == 2 and $gibbonBehaviourID != null) {
            if ($gibbonBehaviourID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                //Check for existence of behaviour record
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = "SELECT * FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonBehaviourID=:gibbonBehaviourID";
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
                    $values = $result->fetch();

                    $form = Form::create('addform', $_SESSION[$guid]['absoluteURL'].'/modules/Behaviour/behaviour_manage_addProcess.php?step=2&gibbonPersonID='.$gibbonPersonID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type);
                        $form->setClass('smallIntBorder fullWidth');
                        $form->setFactory(DatabaseFormFactory::create($pdo));
                        $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_add.php");
                        $form->addHiddenValue('gibbonBehaviourID', $gibbonBehaviourID);
                        $form->addRow()->addHeading(__($guid, 'Step 2 (Optional)'));

                    //Student
                    $row = $form->addRow();
                    	$row->addLabel('students', __('Student'));
                    	$row->addTextField('students')->setValue(formatName('', $values['preferredName'], $values['surname'], 'Student'))->readonly();
                        $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                    //Lessons
                    $lessons = array();
                    $minDate = date('Y-m-d', (time() - (24 * 60 * 60 * 30)));
                    try {
                        $dataSelect = array('date1' => date('Y-m-d', time()), 'date2' => $minDate, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $values['gibbonPersonID']);
                        $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date1 AND date>=:date2) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date DESC, timeStart";
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
                            } catch (PDOException $e) { }
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
                            $lessons[$rowSelect['gibbonPlannerEntryID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' '.htmlPrep($rowSelect['lesson']).' - '.substr(dateConvertBack($guid, $rowSelect['date']), 0, 5).$submission;
                        }
                    }

                    $row = $form->addRow();
                        $row->addLabel('gibbonPlannerEntryID', __('Link To Lesson?'))->description(__('From last 30 days'));
                        if (count($lessons) < 1) {
                            $row->addSelect('gibbonPlannerEntryID')->placeholder();
                        }
                        else {
                            $row->addSelect('gibbonPlannerEntryID')->fromArray($lessons)->placeholder();
                        }

                    $row = $form->addRow();
                    	$row->addFooter();
                    	$row->addSubmit();

                    echo $form->getOutput();
                }
            }
        }
    }
}
?>
