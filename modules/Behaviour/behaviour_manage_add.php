<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs
            ->add(__('Manage Behaviour Records'), 'behaviour_manage.php')
            ->add(__('Add'));

        $gibbonBehaviourID = $_GET['gibbonBehaviourID'] ?? null;
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $type = $_GET['type'] ?? '';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID='.$_GET['editID'].'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type;
            $editID = $_GET['editID'] ?? '';
        }

        $page->return->setEditLink($editLink);
        $page->return->addReturns(['warning1' => __('Your request was successful, but some data was not properly saved.'), 'success1' => __('Your request was completed successfully. You can now add extra information below if you wish.')]);

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'] ?? '';
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1 or $gibbonBehaviourID == null) {
            $form = Form::create('addform', $session->get('absoluteURL').'/modules/Behaviour/behaviour_manage_addProcess.php?step=1&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type);
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
            $form->addRow()->addHeading('Step 1', __('Step 1'));

            //Student
            $row = $form->addRow();
            	$row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonPersonID)->required();

            //Date
            $row = $form->addRow();
            	$row->addLabel('date', __('Date'));
            	$row->addDate('date')->setValue(date($session->get('i18n')['dateFormatPHP']))->required();

            //Type
            $row = $form->addRow();
            	$row->addLabel('type', __('Type'));
            	$row->addSelect('type')->fromArray(array('Positive' => __('Positive'), 'Negative' => __('Negative')))->selected($type)->required();

            //Descriptor
            if ($enableDescriptors == 'Y') {
                $negativeDescriptors = $settingGateway->getSettingByScope('Behaviour', 'negativeDescriptors');
                $negativeDescriptors = (!empty($negativeDescriptors))? explode(',', $negativeDescriptors) : array();
                $positiveDescriptors = $settingGateway->getSettingByScope('Behaviour', 'positiveDescriptors');
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
                	$row->addSelect('level')->fromArray($optionsLevels)->placeholder();
            }

            $form->addRow()->addHeading('Details', __('Details'));

			//Incident
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('comment', __('Incident'));
            	$column->addTextArea('comment')->setRows(5)->setClass('fullWidth');

            //Follow Up
            $row = $form->addRow();
            	$column = $row->addColumn();
            	$column->addLabel('followup', __('Follow Up'));
            	$column->addTextArea('followUp')->setRows(5)->setClass('fullWidth');

            // CUSTOM FIELDS
            $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Behaviour', []);

            //Copy to Notes
            $row = $form->addRow();
                $row->addLabel('copyToNotes', __('Copy To Notes'));
                $row->addCheckbox('copyToNotes');

            $row = $form->addRow();
            	$row->addFooter();
            	$row->addSubmit();

            echo $form->getOutput();

        } elseif ($step == 2 and $gibbonBehaviourID != null) {
            if ($gibbonBehaviourID == '') {
                $page->addError(__('You have not specified one or more required parameters.'));
            } else {
                //Check for existence of behaviour record

                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = "SELECT * FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonBehaviourID=:gibbonBehaviourID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() != 1) {
                    $page->addError(__('The specified record cannot be found.'));
                } else {
                    $values = $result->fetch();

                    $form = Form::create('addform', $session->get('absoluteURL').'/modules/Behaviour/behaviour_manage_addProcess.php?step=2&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&type='.$type);
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_add.php");
                    $form->addHiddenValue('gibbonBehaviourID', $gibbonBehaviourID);
                    $form->addRow()->addHeading(__('Step 2 (Optional)'));

                    //Student
                    $row = $form->addRow();
                    	$row->addLabel('students', __('Student'));
                    	$row->addTextField('students')->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student'))->readonly();
                        $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

                    //Lessons
                    $lessons = array();
                    $minDate = date('Y-m-d', (time() - (24 * 60 * 60 * 30)));

                        $dataSelect = array('date1' => date('Y-m-d', time()), 'date2' => $minDate, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $values['gibbonPersonID']);
                        $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.name AS lesson, gibbonPlannerEntryID, date, homework, homeworkSubmission FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonPlannerEntry ON (gibbonCourseClass.gibbonCourseClassID=gibbonPlannerEntry.gibbonCourseClassID) WHERE (date<=:date1 AND date>=:date2) AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' ORDER BY course, class, date DESC, timeStart";
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
                            $lessons[$rowSelect['gibbonPlannerEntryID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' '.htmlPrep($rowSelect['lesson']).' - '.substr(Format::date($rowSelect['date']), 0, 5).$submission;
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
