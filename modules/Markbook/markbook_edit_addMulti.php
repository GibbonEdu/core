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
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_addMulti.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false or ($highestAction != 'Edit Markbook_multipleClassesAcrossSchool' and $highestAction != 'Edit Markbook_multipleClassesInDepartment' and $highestAction != 'Edit Markbook_everything')) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        if ($gibbonCourseClassID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $course = $result->fetch();
                $date = date('Y-m-d');

                $page->breadcrumbs
                    ->add(
                        strtr(
                            ':action :courseClass :property',
                            [
                                ':action' => __('View'),
                                ':courseClass' => Format::courseClassName($course['course'], $course['class']),
                                ':property' => __('Markbook'),
                            ]
                        ),
                        'markbook_view.php',
                        [
                            'gibbonCourseClassID' => $gibbonCourseClassID,
                        ]
                    )
                    ->add(__('Add Multiple Columns'));

                $form = Form::create('markbook', $session->get('absoluteURL').'/modules/'.$session->get('module').'/markbook_edit_addMultiProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&address='.$session->get('address'));
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->addHiddenValue('address', $session->get('address'));

                $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                if ($highestAction == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
                } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = "(
                        SELECT DISTINCT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClass
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                        JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                        WHERE (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID
                    ) UNION ALL (
                        SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClass
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                        LEFT JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID)
                        WHERE gibbonDepartmentStaffID IS NULL AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.role='Teacher' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                    ) ORDER BY name";
                }

                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassIDMulti', __('Class'))->append(sprintf(__('The current class (%1$s.%2$s) has already been selected.'), $course['course'], $course['class']));
                    $row->addSelect('gibbonCourseClassIDMulti')
                        ->fromQuery($pdo, $sql, $data)
                        ->required()
                        ->selectMultiple()
                        ->selected($course['gibbonCourseClassID']);

                $row = $form->addRow();
                    $row->addLabel('name', __('Name'));
                    $row->addTextField('name')->required()->maxLength(40);

                $row = $form->addRow();
                    $row->addLabel('description', __('Description'));
                    $row->addTextField('description')->required()->maxLength(1000);

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
}
