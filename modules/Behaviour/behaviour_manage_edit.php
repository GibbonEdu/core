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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        $page->breadcrumbs
            ->add(__('Manage Behaviour Records'), 'behaviour_manage.php')
            ->add(__('Edit'));
        
        $gibbonBehaviourID = $_GET['gibbonBehaviourID'] ?? null;
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $type = $_GET['type'] ?? '';

        //Check if gibbonBehaviourID specified
        $gibbonBehaviourID = $_GET['gibbonBehaviourID'];
        if ($gibbonBehaviourID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Behaviour Records_all') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC';
                } elseif ($highestAction == 'Manage Behaviour Records_my') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC';
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Let's go!
                $values = $result->fetch();

                $form = Form::create('addform', $session->get('absoluteURL').'/modules/Behaviour/behaviour_manage_editProcess.php?gibbonBehaviourID='.$gibbonBehaviourID.'&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonFormGroupID='.$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']);
                $form->setFactory(DatabaseFormFactory::create($pdo));
                
                $policyLink = $settingGateway->getSettingByScope('Behaviour', 'policyLink');
                if (!empty($policyLink)) {
                    $form->addHeaderAction('viewPolicy', __('View Behaviour Policy'))
                        ->setExternalURL($policyLink);
                }
                if (!empty($gibbonPersonID) or !empty($gibbonFormGroupID) or !empty($gibbonYearGroupID) or !empty($type)) {
                    $form->addHeaderAction('back', __('Back to Search Results'))
                        ->setURL('/modules/Behaviour/behaviour_manage.php')
                        ->setIcon('search')
                        ->displayLabel()
                        ->addParam('gibbonPersonID', $_GET['gibbonPersonID'])
                        ->addParam('gibbonFormGroupID', $_GET['gibbonFormGroupID'])
                        ->addParam('gibbonYearGroupID', $_GET['gibbonYearGroupID'])
                        ->addParam('type', $_GET['type'])
                        ->prepend((!empty($policyLink)) ? ' | ' : '');
                }
            
                $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_add.php");
                $form->addRow()->addClass('hidden')->addHeading('Step 1', __('Step 1'));

                //Student
                $row = $form->addRow();
                    $row->addLabel('students', __('Student'));
                    $row->addTextField('students')->setValue(Format::name('', $values['preferredNameStudent'], $values['surnameStudent'], 'Student'))->readonly();
                    $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                //Date
                $row = $form->addRow();
                	$row->addLabel('date', __('Date'));
                	$row->addDate('date')->setValue(Format::date($values['date']))->required()->readonly();

                //Date
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addTextField('type')->setValue($values['type'])->required()->readonly();

                //Descriptor
                if ($enableDescriptors == 'Y') {
                    if ($values['type'] == 'Negative') {
                        $descriptors = $settingGateway->getSettingByScope('Behaviour', 'negativeDescriptors');
                    }
                    else {
                        $descriptors = $settingGateway->getSettingByScope('Behaviour', 'positiveDescriptors');
                    }
                    $descriptors = (!empty($descriptors))? explode(',', $descriptors) : array();

                    $row = $form->addRow();
                		$row->addLabel('descriptor', __('Descriptor'));
                        $row->addSelect('descriptor')
                            ->fromArray($descriptors)
                            ->selected($values['descriptor'])
                            ->required()
                            ->placeholder();
                }

                //Level
                if ($enableLevels == 'Y') {
                    $optionsLevels = $settingGateway->getSettingByScope('Behaviour', 'levels');
                    if ($optionsLevels != '') {
                        $optionsLevels = explode(',', $optionsLevels);
                    }
                    $row = $form->addRow();
                    	$row->addLabel('level', __('Level'));
                    	$row->addSelect('level')->fromArray($optionsLevels)->selected($values['level'])->placeholder();
                }

                $form->addRow()->addHeading('Details', __('Details'));

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

                    $dataSelect = array('date' => date('Y-m-d', strtotime($values['date'])), 'minDate' => $minDate, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $values['gibbonPersonID']);
                    $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date AND date>=:minDate) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date, timeStart";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                while ($rowSelect = $resultSelect->fetch()) {
                    $show = true;
                    if ($highestAction == 'Manage Behaviour Records_my') {

                            $dataShow = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                            $sqlShow = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                            $resultShow = $connection2->prepare($sqlShow);
                            $resultShow->execute($dataShow);
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
                        $lessons[$rowSelect['gibbonPlannerEntryID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' '.htmlPrep($rowSelect['lesson']).' - '.substr(Format::date($rowSelect['date']), 0, 5).$submission;
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

                // CUSTOM FIELDS
                $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Behaviour', [], $values['fields']);
                
                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
?>
