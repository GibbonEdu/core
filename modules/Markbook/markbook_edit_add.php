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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Get settings
$settingGateway = $container->get(SettingGateway::class);
$enableEffort = $settingGateway->getSettingByScope('Markbook', 'enableEffort');
$enableRubrics = $settingGateway->getSettingByScope('Markbook', 'enableRubrics');
$enableColumnWeighting = $settingGateway->getSettingByScope('Markbook', 'enableColumnWeighting');
$enableRawAttainment = $settingGateway->getSettingByScope('Markbook', 'enableRawAttainment');
$enableGroupByTerm = $settingGateway->getSettingByScope('Markbook', 'enableGroupByTerm');
$attainmentAltName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAltNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$effortAltName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAltNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');

//Get variables from Planner
$gibbonUnitID = $_GET['gibbonUnitID'] ?? null;
$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? null;
$name = $_GET['name'] ?? null;
$summary = $_GET['summary'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        if ($gibbonCourseClassID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    LEFT JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID)
                    LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID)
                    WHERE ((gibbonCourseClassPerson.gibbonCourseClassPersonID IS NOT NULL AND gibbonCourseClassPerson.role='Teacher')
                        OR (gibbonDepartmentStaff.gibbonDepartmentStaffID IS NOT NULL AND (gibbonDepartmentStaff.role = 'Coordinator' OR gibbonDepartmentStaff.role = 'Assistant Coordinator' OR gibbonDepartmentStaff.role= 'Teacher (Curriculum)'))
                        )
                    AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
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
                $course = $result->fetch();

                $page->breadcrumbs
                    ->add(
                        __('View {courseClass} Markbook', [
                            'courseClass' => Format::courseClassName($course['course'], $course['class']),
                        ]),
                        'markbook_view.php',
                        [
                            'gibbonCourseClassID' => $gibbonCourseClassID,
                        ]
                    )
                    ->add(__('Add Column'));

                $returns = array();
                $returns['error6'] = __('Your request failed because you already have one "End of Year" column for this class.');
                $returns['success1'] = __('Planner was successfully added: you opted to add a linked Markbook column, and you can now do so below.');
                $editLink = '';
                if (isset($_GET['editID'])) {
                    $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Markbook/markbook_edit_edit.php&gibbonMarkbookColumnID='.$_GET['editID'].'&gibbonCourseClassID='.$gibbonCourseClassID;
                }
                $page->return->setEditLink($editLink);
                $page->return->addReturns($returns);

                $form = Form::create('markbook', $session->get('absoluteURL').'/modules/'.$session->get('module').'/markbook_edit_addProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&address='.$session->get('address'));
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->addHiddenValue('address', $session->get('address'));

                $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                $row = $form->addRow();
                    $row->addLabel('courseName', __('Class'));
                    $row->addTextField('courseName')->required()->readOnly()->setValue($course['course'].'.'.$course['class']);

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonUnit.gibbonUnitID as value, gibbonUnit.name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY ordering, name";

                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $units = $row->addSelect('gibbonUnitID')->fromQuery($pdo, $sql, $data)->placeholder()->selected($gibbonUnitID);

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonUnitID as chainedTo, gibbonPlannerEntryID as value, name FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, name";
                $row = $form->addRow();
                    $row->addLabel('gibbonPlannerEntryID', __('Lesson'));
                    $row->addSelect('gibbonPlannerEntryID')->fromQueryChained($pdo, $sql, $data, 'gibbonUnitID')->placeholder()->selected($gibbonPlannerEntryID);

                $row = $form->addRow();
                    $row->addLabel('name', __('Name'));
                    $row->addTextField('name')->required()->maxLength(20)->setValue($name);

                $row = $form->addRow();
                    $row->addLabel('description', __('Description'));
                    $row->addTextField('description')->required()->maxLength(1000)->setValue($summary);

                // TYPE
                $types = $settingGateway->getSettingByScope('Markbook', 'markbookType');
                if (!empty($types)) {
                    $row = $form->addRow();
                        $row->addLabel('type', __('Type'));
                        $typesSelect = $row->addSelect('type')->required()->placeholder();

                    if ($enableColumnWeighting == 'Y') {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'perTerm' => __('Per Term'), 'wholeYear' => __('Whole Year'));
                        $sql = "SELECT (CASE WHEN calculate='term' THEN :perTerm ELSE :wholeYear END) as groupBy, type as value, description as name FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, type";
                        $typesSelect->fromQuery($pdo, $sql, $data, 'groupBy');
                    }

                    if ($typesSelect->getOptionCount() == 0) {
                        $typesSelect->fromString($types);
                    }
                }

                $row = $form->addRow();
                    $row->addLabel('file', __('Attachment'));
                    $row->addFileUpload('file');

                // DATE
                if ($enableGroupByTerm == 'Y') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => $date);
                    $sql = "SELECT gibbonSchoolYearTermID FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND :date BETWEEN firstDay AND lastDay ORDER BY sequenceNumber";
                    $result = $pdo->executeQuery($data, $sql);
                    $currentTerm = ($result->rowCount() > 0)? $result->fetchColumn(0) : '';

                    $form->addRow()->addHeading('Term Date', __('Term Date'));

                    $row = $form->addRow();
                        $row->addLabel('gibbonSchoolYearTermID', __('Term'));
                        $row->addSelectSchoolYearTerm('gibbonSchoolYearTermID', $session->get('gibbonSchoolYearID'))->selected($currentTerm);

                    $row = $form->addRow();
                        $row->addLabel('date', __('Date'));
                        $row->addDate('date')->setValue(Format::date($date))->required();
                } else {
                    $form->addHiddenValue('date', Format::date($date));
                }

                $form->addRow()->addHeading('Assessment', __('Assessment'));

                // ATTAINMENT
                $attainmentLabel = !empty($attainmentAltName)? sprintf(__('Assess %1$s?'), $attainmentAltName) : __('Assess Attainment?');
                $attainmentScaleLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Scale') : __('Attainment Scale');
                $attainmentRawMaxLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Total Mark') : __('Attainment Total Mark');
                $attainmentWeightingLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Weighting') : __('Attainment Weighting');
                $attainmentRubricLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Rubric') : __('Attainment Rubric');

                $row = $form->addRow();
                    $row->addLabel('attainment', $attainmentLabel);
                    $row->addYesNoRadio('attainment')->required();

                $form->toggleVisibilityByClass('attainmentRow')->onRadio('attainment')->when('Y');

                $row = $form->addRow()->addClass('attainmentRow');
                    $row->addLabel('gibbonScaleIDAttainment', $attainmentScaleLabel);
                    $row->addSelectGradeScale('gibbonScaleIDAttainment')->required()->selected($session->get('defaultAssessmentScale'));

                if ($enableRawAttainment == 'Y') {
                    $row = $form->addRow()->addClass('attainmentRow');
                        $row->addLabel('attainmentRawMax', $attainmentRawMaxLabel)->description(__('Leave blank to omit raw marks.'));
                        $row->addNumber('attainmentRawMax')->maxLength(8)->onlyInteger(false);
                }

                if ($enableColumnWeighting == 'Y') {
                    $row = $form->addRow()->addClass('attainmentRow');
                        $row->addLabel('attainmentWeighting', $attainmentWeightingLabel);
                        $row->addNumber('attainmentWeighting')->maxLength(5)->onlyInteger(false)->setValue(1);
                }

                if ($enableRubrics == 'Y') {
                    $row = $form->addRow()->addClass('attainmentRow');
                        $row->addLabel('gibbonRubricIDAttainment', $attainmentRubricLabel)->description(__('Choose predefined rubric, if desired.'));
                        $row->addSelectRubric('gibbonRubricIDAttainment', $course['gibbonYearGroupIDList'], $course['gibbonDepartmentID'])->placeholder();
                }

                // EFFORT
                if ($enableEffort == 'Y') {
                    $effortLabel = !empty($effortAltName)? sprintf(__('Assess %1$s?'), $effortAltName) : __('Assess Effort?');
                    $effortScaleLabel = !empty($effortAltName)? $effortAltName.' '.__('Scale') : __('Effort Scale');
                    $effortRubricLabel = !empty($effortAltName)? $effortAltName.' '.__('Rubric') : __('Effort Rubric');

                    $row = $form->addRow();
                        $row->addLabel('effort', $effortLabel);
                        $row->addYesNoRadio('effort')->required();

                    $form->toggleVisibilityByClass('effortRow')->onRadio('effort')->when('Y');

                    $row = $form->addRow()->addClass('effortRow');
                        $row->addLabel('gibbonScaleIDEffort', $effortScaleLabel);
                        $row->addSelectGradeScale('gibbonScaleIDEffort')->required()->selected($session->get('defaultAssessmentScale'));

                    if ($enableRubrics == 'Y') {
                        $row = $form->addRow()->addClass('effortRow');
                            $row->addLabel('gibbonRubricIDEffort', $effortRubricLabel)->description(__('Choose predefined rubric, if desired.'));
                            $row->addSelectRubric('gibbonRubricIDEffort', $course['gibbonYearGroupIDList'], $course['gibbonDepartmentID'])->placeholder();
                    }
                }

                $row = $form->addRow();
                    $row->addLabel('comment', __('Include Comment?'));
                    $row->addYesNoRadio('comment')->required();

                $row = $form->addRow();
                    $row->addLabel('uploadedResponse', __('Include Uploaded Response?'));
                    $row->addYesNoRadio('uploadedResponse')->required();

                $form->addRow()->addHeading('Access', __('Access'));

                $row = $form->addRow();
                    $row->addLabel('viewableStudents', __('Viewable to Students'));
                    $row->addYesNo('viewableStudents')->required();

                $row = $form->addRow();
                    $row->addLabel('viewableParents', __('Viewable to Parents'));
                    $row->addYesNo('viewableParents')->required();

                $row = $form->addRow();
                    $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                    $row->addDate('completeDate');

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'markbook_edit_add.php'));
}
