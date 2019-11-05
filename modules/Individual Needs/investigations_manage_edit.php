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
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
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
            ->add(__('Manage Investigations'), 'investigations_manage.php')
            ->add(__('Edit'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $gibbonINInvestigationID = $_GET['gibbonINInvestigationID'];
        if ($gibbonINInvestigationID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            // Validate the database record exist
            $investigationGateway = $container->get(INInvestigationGateway::class);
            $criteria = $investigationGateway->newQueryCriteria();
            $investigation = $investigationGateway->queryInvestigationsByID($criteria, $gibbonINInvestigationID, $_SESSION[$guid]['gibbonSchoolYearID']);

            $investigation = $investigation->getRow(0);

            if (empty($investigation)) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $canEdit = false ;
                if ($highestAction == 'Manage Investigations_all' || ($highestAction == 'Manage Investigations_my' && ($investigation['gibbonPersonIDCreator'] == $_SESSION[$guid]['gibbonPersonID']))) {
                    $canEdit = true ;
                }

                $isTutor = false ;
                if ($investigation['gibbonPersonIDTutor'] == $_SESSION[$guid]['gibbonPersonID'] || $investigation['gibbonPersonIDTutor2'] == $_SESSION[$guid]['gibbonPersonID'] || $investigation['gibbonPersonIDTutor3'] == $_SESSION[$guid]['gibbonPersonID']) {
                    $isTutor = true ;
                }

                if (!$canEdit && !$isTutor) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {

                    if ($gibbonPersonID != '' or $gibbonRollGroupID != '' or $gibbonYearGroupID != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_manage.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    $form = Form::create('addform', $_SESSION[$guid]['absoluteURL']."/modules/Individual Needs/investigations_manage_editProcess.php?gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', "/modules/Individual Needs/investigations_manage_edit.php");
                    $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
                    $form->addRow()->addHeading(__('Basic Information'));

                    //Student
                    $row = $form->addRow();
                    	$row->addLabel('gibbonPersonIDStudent', __('Student'));
                    	$row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder(__('Please select...'))->selected($gibbonPersonID)->required()->readonly();

                    //Status
                    $row = $form->addRow();
                    	$row->addLabel('status', __('Status'));
                    	$row->addTextField('status')->setValue(__('Referral'))->required()->readonly();

                    //Date
                    $row = $form->addRow();
                    	$row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                    	$row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required()->readonly();

            		//Reason
                    $row = $form->addRow();
                        $column = $row->addColumn();
                        $column->addLabel('reason', __('Reason'))->description(__('Why should this student\'s individual needs should be investigated?'));;
                    	$column->addTextArea('reason')->setRows(5)->setClass('fullWidth')->required()->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    //Strategies Tried
                    $row = $form->addRow();
                    	$column = $row->addColumn();
                    	$column->addLabel('strategiesTried', __('Strategies Tried'));
                    	$column->addTextArea('strategiesTried')->setRows(5)->setClass('fullWidth')->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    //Parents Informed?
                    $row = $form->addRow();
                        $row->addLabel('parentsInformed', __('Parents Informed?'));
                        $row->addYesNo('parentsInformed')->selected('N')->required()->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    $form->toggleVisibilityByClass('parentsInformed')->onSelect('parentsInformed')->when('Y');

                    //Parent Response
                    $row = $form->addRow()->addClass('parentsInformed');
                    	$column = $row->addColumn();
                    	$column->addLabel('parentsResponse', __('Parent Response'));
                    	$column->addTextArea('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    //Form Tutor Resolution
                    if ($investigation['status'] == 'Resolved' || ($investigation['status'] == 'Referral' && $isTutor)) {
                        $form->addRow()->addHeading(__('Form Tutor Resolution'));
                        if ($isTutor && $investigation['status'] == 'Referral') {
                            $row = $form->addRow();
                                $row->addLabel('resolvable', __('Resolvable?'))->description(__('Is form tutor able to resolve without further input? If no, further investigation will be launched.'));
                                $row->addYesNo('resolvable')->required()->placeholder();

                                $form->toggleVisibilityByClass('resolutionDetails')->onSelect('resolvable')->when('Y');
                        }

                        $form->toggleVisibilityByClass('invitationDetails')->onSelect('resolvable')->when('N');

                        //Resolvable by tutor
                        $row = $form->addRow()->addClass('resolutionDetails');
                            $column = $row->addColumn();
                            $column->addLabel('resolutionDetails', __('Resolution Details'));
                            $column->addTextArea('resolutionDetails')->setRows(5)->setClass('fullWidth')->readonly(!$isTutor || $investigation['status'] != 'Referral');

                        //Not resolvable by tutor
                        try {
                            $dataClass = array('gibbonSchoolYearID' => $investigation['gibbonSchoolYearID'], 'gibbonPersonID' => $investigation['gibbonPersonIDStudent']);
                            $sqlClass = "SELECT gibbonCourseClassTeacher.gibbonCourseClassPersonID, gibbonCourseClassTeacher.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course, surname, preferredName
                                FROM gibbonCourse
                                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                                    JOIN gibbonCourseClassPerson AS gibbonCourseClassStudent ON (gibbonCourseClassStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassStudent.role='Student')
                                    JOIN gibbonCourseClassPerson AS gibbonCourseClassTeacher ON (gibbonCourseClassTeacher.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassTeacher.role='Teacher')
                                    JOIN gibbonPerson ON (gibbonCourseClassTeacher.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                    AND gibbonCourseClassStudent.gibbonPersonID=:gibbonPersonID
                                    AND gibbonCourseClass.reportable='Y'
                                    AND gibbonCourseClassStudent.reportable='Y'
                                ORDER BY course, class";
                            $resultClass = $connection2->prepare($sqlClass);
                            $resultClass->execute($dataClass);
                        } catch (PDOException $e) {}

                        try {
                            $dataHOY = array('gibbonSchoolYearID' => $investigation['gibbonSchoolYearID'], 'gibbonPersonID' => $investigation['gibbonPersonIDStudent']);
                            $sqlHOY = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName
                                FROM gibbonStudentEnrolment
                                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                                    LEFT JOIN gibbonPerson ON (gibbonYearGroup.gibbonPersonIDHOY=gibbonPerson.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                    AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID";
                            $resultHOY = $connection2->prepare($sqlHOY);
                            $resultHOY->execute($dataHOY);
                        } catch (PDOException $e) {}

                        if ($resultClass->rowCount() < 1 && $resultHOY->rowCount() < 1) {
                            $form->addRow()->addClass('invitationDetails')->addAlert(__('There are no records to display.'), 'warning');

                        }
                        else {
                            $row = $form->addRow()->addClass('invitationDetails');
                            $row->addLabel('invitation', __('Invite Input'))->description(__('Which teachers would you like to gather input from?'));
                            $column = $row->addColumn()->setClass('flex-col items-end');
                            if ($resultHOY->rowCount() == 1) {
                                $rowHOY = $resultHOY->fetch();
                                $column->addCheckbox('gibbonPersonIDHOY')
                                    ->setName('gibbonPersonIDHOY')
                                    ->setValue($rowHOY['gibbonPersonID'])
                                    ->description(Format::name('', $rowHOY['preferredName'], $rowHOY['surname'], 'Student', false).' ('.__('Head of Year').')')
                                    ->readonly(!$isTutor)
                                    ->checked($rowHOY['gibbonPersonID']);
                            }
                            while ($rowClass = $resultClass->fetch()) {
                                $column->addCheckbox('gibbonCourseClassPersonID'.$rowClass['gibbonCourseClassPersonID'])
                                    ->setName('gibbonCourseClassPersonID[]')
                                    ->setValue($rowClass['gibbonPersonID'].'-'.$rowClass['gibbonCourseClassPersonID'])
                                    ->description(Format::name('', $rowClass['preferredName'], $rowClass['surname'], 'Student', false).' ('.$rowClass['course'].'.'.$rowClass['class'].')')
                                    ->readonly(!$isTutor)
                                    ->checked($rowClass['gibbonPersonID'].'-'.$rowClass['gibbonCourseClassPersonID']);
                            }
                        }
                    }

                    $row = $form->addRow();
                    	$row->addFooter();
                        if ($investigation['status'] == 'Referral') {
                            $row->addSubmit();
                        }

                    $form->loadAllValuesFrom($investigation);

                    echo $form->getOutput();
                }
            }
        }
    }
}
?>
