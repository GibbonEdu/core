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
$enableRawAttainment = $settingGateway->getSettingByScope('Markbook', 'enableRawAttainment');
$enableModifiedAssessment = $settingGateway->getSettingByScope('Markbook', 'enableModifiedAssessment');

//Get alternative header names
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$hasAttainmentName = ($attainmentAlternativeName != '' && $attainmentAlternativeNameAbrev != '');

$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');
$hasEffortName = ($effortAlternativeName != '' && $effortAlternativeNameAbrev != '');

// Get the sort order, if it exists
$studentOrderBy = $session->get('markbookOrderBy', null) ?? $_GET['markbookOrderBy'] ?? 'surname';

// Register scripts available to the core, but not included by default
$page->scripts->add('chart');

// This script makes entering raw marks easier, by capturing the enter key and moving to the next field insted of submitting
echo "<script type='text/javascript'>";
?>
    $(document).ready(function(){
        autosize($('textarea'));
    });

    // Map [Enter] key to work like the [Tab] key
    // Daniel P. Clark 2014
    // Modified for Gibbon Markbook Edit Data

    $(window).keydown(function(e) {

        // Set self as the current item in focus
        var self = $(':focus'),
          // Set the form by the current item in focus
          form = self.parents('form:eq(0)'),
          focusable;

        // Sometimes :focus selector doesnt work (in Chrome specifically)
        if (self.length == false) {
            self = e.target.value;
        }

        function enterKey(){

            if (e.which === 13 && !self.is('textarea,div[contenteditable=true]')) { // [Enter] key

                var index = self.attr('name').substr(0, self.attr('name').indexOf('-'));
                var attainmentNext = $( '#' + (parseInt(index) + 1) + '-attainmentValueRaw');

                //If not a regular hyperlink/button/textarea
                if ($.inArray(self, focusable) && (!self.is('a,button'))){
                    // Then prevent the default [Enter] key behaviour from submitting the form
                    e.preventDefault();
                } // Otherwise follow the link/button as by design, or put new line in textarea

                self.change();

                if (attainmentNext.length) {

                    attainmentNext.focus();
                    attainmentNext.select();

                    // Scroll to the next raw score
                    $('html,body').animate( {
                        scrollTop: $(document).scrollTop() + ( attainmentNext.offset().top - self.offset().top ),
                    }, 250);
                }

                return false;
            }
        }

        // We need to capture the [Shift] key and check the [Enter] key either way.
        if (e.shiftKey) { enterKey() } else { enterKey() }
    });

    <?php
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_data.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if gibbonCourseClassID and gibbonMarkbookColumnID specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'] ?? '';
        if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList, gibbonScale.name as targetGradeScale
                            FROM gibbonCourse
                            JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                            LEFT JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonCourseClass.gibbonScaleIDTarget)
                            WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                            ORDER BY course, class";
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
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList, gibbonScale.name as targetGradeScale
                            FROM gibbonCourse
                            JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                            LEFT JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonCourseClass.gibbonScaleIDTarget)
                            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher'
                            AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                            ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

                    $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql2 = "SELECT gibbonMarkbookColumn.*, gibbonUnit.name as unitName, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort
                            FROM gibbonMarkbookColumn
                            LEFT JOIN gibbonUnit ON (gibbonMarkbookColumn.gibbonUnitID=gibbonUnit.gibbonUnitID)
                            LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonMarkbookColumn.gibbonScaleIDAttainment)
                            LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonMarkbookColumn.gibbonScaleIDEffort)
                            WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID";
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);

                if ($result2->rowCount() != 1) {
                    $page->addError(__('The selected column does not exist, or you do not have access to it.'));
                } else {
                    //Let's go!
                    $course = $result->fetch();
                    $values = $result2->fetch();

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
                        ->add(__('Enter Marks'));

                    // Added an info message to let uers know about enter / automatic calculations
                    if ($values['attainment'] == 'Y' && $values['attainmentRaw'] == 'Y' && !empty($values['attainmentRawMax']) && $enableRawAttainment == 'Y') {
                        echo '<p>';
                        echo __('Press enter when recording marks to jump to the next student. Attainment values with a percentage grade scale will be calculated automatically. You can override the automatic value by selecting a different grade.');
                        echo '</p>';
                    }

                    $columns = 1;

                    $hasTarget = !empty($course['targetGradeScale']);
                    $hasSubmission = false;
                    $hasAttainment = $values['attainment'] == 'Y';
                    $hasRawAttainment = $values['attainmentRaw'] == 'Y' && !empty($values['attainmentRawMax']) && $enableRawAttainment == 'Y';
                    $hasAttainmentRubric = $values['gibbonRubricIDAttainment'] != '' && $enableRubrics =='Y';
                    $hasEffort = $values['effort'] == 'Y';
                    $hasEffortRubric = $values['gibbonRubricIDEffort'] != '' && $enableRubrics =='Y';
                    $hasComment = $values['comment'] == 'Y';
                    $hasUpload = $values['uploadedResponse'] == 'Y';

                    $data = array(
                        'gibbonCourseClassID' => $gibbonCourseClassID,
                        'gibbonMarkbookColumnID' => $values['gibbonMarkbookColumnID'],
                        'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
                        'today' => date('Y-m-d'),
                    );
                    $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy, title, surname, preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.dateStart, gibbonStudentEnrolment.rollOrder, gibbonScaleGrade.value as targetScaleGrade, modifiedAssessment, gibbonMarkbookEntry.attainmentValue, gibbonMarkbookEntry.attainmentValueRaw, gibbonMarkbookEntry.effortValue, gibbonMarkbookEntry.comment, gibbonMarkbookEntry.response
                            FROM gibbonCourseClassPerson
                            JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            LEFT JOIN gibbonMarkbookEntry ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonMarkbookEntry.gibbonPersonIDStudent=gibbonCourseClassPerson.gibbonPersonID)
                            LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                            LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                            AND gibbonPerson.status='Full' AND gibbonCourseClassPerson.role='Student'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";

                    if ($studentOrderBy == 'rollOrder') {
                        $sql .= " ORDER BY ISNULL(rollOrder), rollOrder, surname, preferredName";
                    } else if ($studentOrderBy == 'preferredName') {
                        $sql .= " ORDER BY preferredName, surname";
                    } else {
                        $sql .= " ORDER BY surname, preferredName";
                    }
                    $result = $pdo->executeQuery($data, $sql);
                    $students = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

                    // WORK OUT IF THERE IS SUBMISSION
                    if (is_null($values['gibbonPlannerEntryID']) == false) {
                        $dataSub = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                        $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                        $resultSub = $connection2->prepare($sqlSub);
                        $resultSub->execute($dataSub);

                        if ($resultSub->rowCount() == 1) {
                            $hasSubmission = true;
                            $rowSub = $resultSub->fetch();
                            $values['homeworkDueDateTime'] = $rowSub['homeworkDueDateTime'];
                            $values['homeworkSubmissionRequired'] = $rowSub['homeworkSubmissionRequired'];
                            $values['lessonDate'] = $rowSub['date'];
                        }
                    }

                    // Grab student submissions
                    foreach ($students as $gibbonPersonID => $student) {
                        $students[$gibbonPersonID]['submission'] = '';

                        if ($hasSubmission) {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                            $sql = "SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC LIMIT 1";
                            $result = $pdo->executeQuery($data, $sql);
                            $submission = ($result->rowCount() > 0)? $result->fetch() : '';

                            $students[$gibbonPersonID]['submission'] = renderStudentSubmission($gibbonPersonID, $submission, $values);
                        }
                    }

                    //Grab student individual needs flag
                    $data = array(
                        'gibbonCourseClassID' => $gibbonCourseClassID,
                        'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
                        'today' => date('Y-m-d')
                    );
                    $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID
                            FROM gibbonCourseClassPerson
                            JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            JOIN gibbonINPersonDescriptor ON (gibbonPerson.gibbonPersonID=gibbonINPersonDescriptor.gibbonPersonID)
                            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                            AND gibbonPerson.status='Full' AND gibbonCourseClassPerson.role='Student'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                    $result = $pdo->executeQuery($data, $sql);
                    $individualNeeds = ($result->rowCount() > 0)? $result->fetchAll() : array();

                    $form = Form::create('markbookEditData', $session->get('absoluteURL').'/modules/'.$session->get('module').'/markbook_edit_dataProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonMarkbookColumnID='.$gibbonMarkbookColumnID.'&address='.$session->get('address'));
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $session->get('address'));

                    // Add header actions
                    if (!empty($values['gibbonPlannerEntryID'])) {
                        $params = [
                            "viewBy" => 'class',
                            "gibbonCourseClassID" => $gibbonCourseClassID,
                            "gibbonPlannerEntryID" => $values['gibbonPlannerEntryID'],

                        ];
                        $form->addHeaderAction('view', __('View Linked Lesson'))
                            ->setURL('/modules/Planner/planner_view_full.php')
                            ->addParams($params)
                            ->setIcon('planner')
                            ->displayLabel();
                    }
                    $params = [
                        "gibbonCourseClassID" => $gibbonCourseClassID,
                        "gibbonMarkbookColumnID" => $gibbonMarkbookColumnID,

                    ];
                    $form->addHeaderAction('edit', __('Edit'))
                        ->setURL('/modules/Markbook/markbook_edit_edit.php')
                        ->addParams($params)
                        ->setIcon('config')
                        ->displayLabel()
                        ->prepend((!empty($values['gibbonPlannerEntryID'])) ? ' | ' : '');

                    if (count($students) == 0) {
                        $form->addRow()->addHeading('Students', __('Students'));
                        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                    } else {
                        $attainmentScale = '';
                        if ($hasAttainment) {
                            $form->addHiddenValue('scaleAttainment', $values['gibbonScaleIDAttainment']);
                            $form->addHiddenValue('lowestAcceptableAttainment', $values['lowestAcceptableAttainment']);
                            $attainmentScale = ' - '.$values['scaleNameAttainment'];
                            $attainmentScale .= $values['usageAttainment']? ': '.$values['usageAttainment'] : '';
                        }

                        if ($hasAttainment && $hasRawAttainment) {
                            $form->addHiddenValue('attainmentRawMax', $values['attainmentRawMax']);

                            $scaleType = (strpos( strtolower($values['scaleNameAttainment']), 'percent') !== false)? '%' : '';
                            $form->addHiddenValue('attainmentScaleType', $scaleType);
                        }

                        $effortScale = '';
                        if ($hasEffort) {
                            $form->addHiddenValue('scaleEffort', $values['gibbonScaleIDEffort']);
                            $form->addHiddenValue('lowestAcceptableEffort', $values['lowestAcceptableEffort']);
                            $effortScale = ' - '.$values['scaleNameEffort'];
                            $effortScale .= $values['usageEffort']? ': '.$values['usageEffort'] : '';
                        }

                        // Create a rubric link object (for reusabilty)
                        $rubricLinkSource = $form->getFactory()
                            ->createWebLink('<img title="'.__('Mark Rubric').'" src="./themes/'.$session->get('gibbonThemeName').'/img/rubric.png" style="margin-left:4px;"/>')
                            ->setURL($session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php')
                            ->setClass('thickbox')
                            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                            ->addParam('gibbonMarkbookColumnID', $gibbonMarkbookColumnID)
                            ->addParam('width', '1100')
                            ->addParam('height', '550');

                        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding noBorder');

                        $detailsText = ($values['unitName'] != '')? $values['unitName'].'<br/>' : '';
                        $detailsText .= !empty($values['completeDate'])? __('Marked on').' '.Format::date($values['completeDate']) : __('Unmarked');
                        $detailsText .= '<br/>'.$values['type'];

                        if ($values['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$values['attachment'])) {
                            $detailsText .= " | <a title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$values['attachment']."'>".__('More info').'</a>';
                        }

                        $header = $table->addHeaderRow();

                        $header->addTableCell(__('Student'))->rowSpan(2);

                        $header->onlyIf($hasTarget)
                            ->addTableCell(__('Target'))
                            ->setTitle(__('Personalised target grade').' | '.($course['targetGradeScale'] ?? '').' '.__('Scale'))
                            ->rowSpan(2)
                            ->addClass('textCenter smallColumn dataColumn noPadding')
                            ->wrap('<div class="verticalText">', '</div>');

                        $header->addTableCell($values['name'])
                            ->setTitle($values['description'])
                            ->append('<br><span class="small emphasis" style="font-weight:normal;">'.$detailsText.'</span>')
                            ->setClass('textCenter')
                            ->colSpan(5);

                        $header = $table->addHeaderRow();

                        $header->onlyIf($enableModifiedAssessment == 'Y')
                            ->addContent(__('Mod'))
                            ->setTitle(__('Modified Assessment'))
                            ->setClass('textCenter');

                        $header->onlyIf($hasSubmission)
                            ->addContent(__('Sub'))
                            ->setTitle(__('Submitted Work'))
                            ->setClass('textCenter');

                        $header->onlyIf($hasAttainment && $hasRawAttainment)
                            ->addContent(__('Mark'))
                            ->setTitle(__('Raw Attainment Mark'))
                            ->setClass('textCenter');

                        $header->onlyIf($hasAttainment)
                            ->addContent($hasAttainmentName? $attainmentAlternativeNameAbrev : __('Att'))
                            ->setTitle(($hasAttainmentName? $attainmentAlternativeName : __('Attainment')).$attainmentScale)
                            ->setClass('textCenter');

                        $header->onlyIf($hasEffort)
                            ->addContent($hasEffortName? $effortAlternativeNameAbrev : __('Eff'))
                            ->setTitle(($hasEffortName? $effortAlternativeName : __('Effort')).$effortScale)
                            ->setClass('textCenter');

                        $header->onlyIf($hasComment || $hasUpload)
                            ->addContent(__('Com'))
                            ->setTitle(__('Comment'))
                            ->setClass('textCenter');
                    }

                    $count = 0;
                    foreach ($students as $gibbonPersonID => $student) {
                        $count = $count+1;
                        $rollOrder = ($studentOrderBy == 'rollOrder')? $student['rollOrder'] : $count;

                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);

                        if (!$hasRawAttainment) {
                            $form->addHiddenValue($count.'-attainmentValueRaw', $student['attainmentValueRaw']);
                        }

                        $row = $table->addRow()->setID($student['gibbonPersonID']);

                        $row->addWebLink(Format::name('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Markbook')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($rollOrder.') ');

                        $row->onlyIf($hasTarget)
                            ->addContent($student['targetScaleGrade']);


                        //Is modified assessment on?
                        if ($enableModifiedAssessment == 'Y') {
                            if(array_search($student['gibbonPersonID'], array_column($individualNeeds, 'gibbonPersonID')) !== false || !is_null($student['modifiedAssessment'])) { //Student has individual needs record now, or used to in the past (inferred by modifiedAssessment set to Y)
                                $form->addHiddenValue($count.'-modifiedAssessmentEligible', 'Y');
                                $row->addCheckbox($count.'-modifiedAssessment')
                                    ->setClass('textCenter')
                                    ->setValue('on')->checked($student['modifiedAssessment'] == 'Y');
                            }
                            else {
                                $row->addContent('');
                            }
                        }

                        $row->onlyIf($hasSubmission)
                            ->addContent($student['submission']);

                        $col = $row->onlyIf($hasAttainment && $hasRawAttainment)->addColumn();
                        $col->addNumber($count.'-attainmentValueRaw')
                            ->onlyInteger(false)
                            ->setClass('inline-block')
                            ->setValue($student['attainmentValueRaw']);
                        $col->addContent('/ '.floatval($values['attainmentRawMax']))->setClass('inline-block ml-1');

                        $col = $row->onlyIf($hasAttainment)->addColumn();
                        $col->addSelectGradeScaleGrade($count.'-attainmentValue', $values['gibbonScaleIDAttainment'])
                            ->setClass('textCenter gradeSelect inline-block')
                            ->selected($student['attainmentValue']);

                        if ($hasAttainment && $hasAttainmentRubric) {
                            $rubricLink = clone $rubricLinkSource;
                            $rubricLink->addParam('gibbonPersonID', $student['gibbonPersonID']);
                            $rubricLink->addParam('gibbonRubricID', $values['gibbonRubricIDAttainment']);
                            $rubricLink->addParam('type', 'attainment');
                            $col->addContent($rubricLink->getOutput())->setClass('inline-block ml-1');
                        }

                        $effort = $row->onlyIf($hasEffort)
                            ->addSelectGradeScaleGrade($count.'-effortValue', $values['gibbonScaleIDEffort'])
                            ->setClass('textCenter gradeSelect')
                            ->selected($student['effortValue']);

                        if ($hasEffort && $hasEffortRubric) {
                            $rubricLink = clone $rubricLinkSource;
                            $rubricLink->addParam('gibbonPersonID', $student['gibbonPersonID']);
                            $rubricLink->addParam('gibbonRubricID', $values['gibbonRubricIDEffort']);
                            $rubricLink->addParam('type', 'effort');
                            $effort->append($rubricLink->getOutput());
                        }

                        $col = $row->onlyIf($hasComment || $hasUpload)->addColumn()->addClass('stacked');

                            $col->onlyIf($hasComment)->addTextArea('comment'.$count)->setRows(6)->setValue($student['comment']);

                            $col->onlyIf($hasUpload)
                                ->addFileUpload('response'.$count)
                                ->setAttachment('attachment'.$count, $session->get('absoluteURL'), $student['response'])
                                ->setMaxUpload(false);
                    }

                    $form->addHiddenValue('count', $count);

                    $form->addRow()->addHeading('Assessment Complete?', __('Assessment Complete?'));

                    $row = $form->addRow();
                        $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                        $row->addDate('completeDate');

                    $row = $form->addRow()->addClass('submitRow sticky -bottom-px bg-gray-100 border-t -mt-px mb-px z-50');
                        $row->addContent(getMaxUpload(true));
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'markbook_view.php'));
}
?>
