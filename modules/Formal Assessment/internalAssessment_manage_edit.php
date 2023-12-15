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
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Get alternative header names
$settingGateway = $container->get(SettingGateway::class);
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonCourseClassID and gibbonInternalAssessmentColumnID specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'] ?? '';
    if ($gibbonCourseClassID == '' or $gibbonInternalAssessmentColumnID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {

                $data2 = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID);
                $sql2 = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID';
                $result2 = $connection2->prepare($sql2);
                $result2->execute($data2);

            if ($result2->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Let's go!
                $class = $result->fetch();
                $values = $result2->fetch();

                $page->breadcrumbs
                    ->add(__('Manage {courseClass} Internal Assessments', ['courseClass' => $class['course'].'.'.$class['class']]), 'internalAssessment_manage.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
                    ->add(__('Edit Column'));

                if ($values['groupingID'] != '' and $values['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
                    echo "<div class='error'>";
                    echo __('This column is part of a set of columns, which you did not create, and so cannot be individually edited.');
                    echo '</div>';
                } else {
                    $page->return->addReturns(['error3' => __('Your request failed due to an attachment error.')]);

                    $form = Form::create('internalAssessment', $session->get('absoluteURL').'/modules/'.$session->get('module').'/internalAssessment_manage_editProcess.php?gibbonInternalAssessmentColumnID='.$gibbonInternalAssessmentColumnID.'&gibbonCourseClassID='.$gibbonCourseClassID.'&address='.$session->get('address'));
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $session->get('address'));

                    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                    $row = $form->addRow();
                        $row->addLabel('className', __('Class'));
                        $row->addTextField('className')->required()->readonly()->setValue(htmlPrep($class['course'].'.'.$class['class']));

                    $row = $form->addRow();
                        $row->addLabel('name', __('Name'));
                        $row->addTextField('name')->required()->maxLength(30);

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->required()->maxLength(1000);

                    $types = $settingGateway->getSettingByScope('Formal Assessment', 'internalAssessmentTypes');
                    if (!empty($types)) {
                        $row = $form->addRow();
                            $row->addLabel('type', __('Type'));
                            $row->addSelect('type')->fromString($types)->required()->placeholder();
                    }

                    $row = $form->addRow();
                        $row->addLabel('file', __('Attachment'));
                        $row->addFileUpload('file')->setAttachment('attachment', $session->get('absoluteURL'), $values['attachment']);

                    $form->addRow()->addHeading('Assessment', __('Assessment'));

                    $attainmentLabel = !empty($attainmentAlternativeName)? sprintf(__('Assess %1$s?'), $attainmentAlternativeName) : __('Assess Attainment?');
                    $row = $form->addRow();
                        $row->addLabel('attainment', $attainmentLabel);
                        $row->addYesNoRadio('attainment')->required();

                    $form->toggleVisibilityByClass('attainmentRow')->onRadio('attainment')->when('Y');

                    $attainmentScaleLabel = !empty($attainmentAlternativeName)? $attainmentAlternativeName.' '.__('Scale') : __('Attainment Scale');
                    $row = $form->addRow()->addClass('attainmentRow');
                        $row->addLabel('gibbonScaleIDAttainment', $attainmentScaleLabel);
                        $row->addSelectGradeScale('gibbonScaleIDAttainment')->required()->selected($session->get('defaultAssessmentScale'));

                    $effortLabel = !empty($effortAlternativeName)? sprintf(__('Assess %1$s?'), $effortAlternativeName) : __('Assess Effort?');
                    $row = $form->addRow();
                        $row->addLabel('effort', $effortLabel);
                        $row->addYesNoRadio('effort')->required();

                    $form->toggleVisibilityByClass('effortRow')->onRadio('effort')->when('Y');

                    $effortScaleLabel = !empty($effortAlternativeName)? $effortAlternativeName.' '.__('Scale') : __('Effort Scale');
                    $row = $form->addRow()->addClass('effortRow');
                        $row->addLabel('gibbonScaleIDEffort', $effortScaleLabel);
                        $row->addSelectGradeScale('gibbonScaleIDEffort')->required()->selected($session->get('defaultAssessmentScale'));

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

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }

        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $gibbonCourseClassID));
    }
}
