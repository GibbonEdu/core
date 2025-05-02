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
use Gibbon\Data\Validator;

  

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

//Get alternative header names
$settingGateway = $container->get(SettingGateway::class);
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$hasAttainmentName = ($attainmentAlternativeName != '' && $attainmentAlternativeNameAbrev != '');

$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');
$hasEffortName = ($effortAlternativeName != '' && $effortAlternativeNameAbrev != '');

echo "<script type='text/javascript'>";
    echo '$(document).ready(function(){';
        echo "autosize($('textarea'));";
    echo '});';
echo '</script>';

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID');
$URL = $session->get('absoluteURL')
    . "/index.php?q=/modules/Formal Assessment/internalAssessment_write_data.php"
    . "&gibbonCourseClassID=$gibbonCourseClassID"
    . "&gibbonInternalAssessmentColumnID=$gibbonInternalAssessmentColumnID"
    . "&gibbonPersonID=$gibbonPersonID";

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write_data.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if gibbonCourseClassID and gibbonInternalAssessmentColumnID specified
        if (empty($gibbonCourseClassID) || empty($gibbonInternalAssessmentColumnID) || empty($gibbonPersonID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        } else {
            try {
                if ($highestAction == 'Write Internal Assessments_all') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

                    $data2 = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID);
                    $sql2 = "SELECT gibbonInternalAssessmentColumn.*, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort
                        FROM gibbonInternalAssessmentColumn
                        LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDAttainment)
                        LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDEffort)
                        WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID";
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);

                if ($result2->rowCount() != 1) {
                    $page->addError(__('The selected column does not exist, or you do not have access to it.'));
                } else {
                    //Let's go!
                    $class = $result->fetch();
                    $values = $result2->fetch();

                    $page->breadcrumbs
                        ->add(__('Write {courseClass} Internal Assessments', ['courseClass' => $class['course'].'.'.$class['class']]), 'internalAssessment_write.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
                        ->add(__('Enter Internal Assessment Results'));

                    $page->return->addReturns(['error3' => __('Your request failed due to an attachment error.'), 'success0' => __('Your request was completed successfully.')]);

                    $hasAttainment = $values['attainment'] == 'Y';
                    $hasEffort = $values['effort'] == 'Y';
                    $hasComment = $values['comment'] == 'Y';
                    $hasUpload = $values['uploadedResponse'] == 'Y';

                    if ($hasComment) {
                        // Define categorized comments for dropdown
                        $categorizedComments = [
                            'Excellent Academic Performance' => [
                                'Demonstrates outstanding academic achievement.',
                                'Consistently exceeds expectations in all subject areas.',
                                'Displays exceptional critical thinking and problem-solving skills.',
                                'Submits high-quality work well ahead of deadlines.',
                                'Actively participates and leads in class discussions.',
                            ],
                            'Good Academic Performance' => [
                                'Shows a strong understanding of key concepts.',
                                'Completes tasks diligently and on time.',
                                'Demonstrates growing confidence and independence in work.',
                                'A positive and enthusiastic learner.',
                                'Performs well in both individual and group activities.',
                            ],
                            'Average Performance' => [
                                'Meets basic academic expectations.',
                                'Can benefit from more consistent study habits.',
                                'Demonstrates satisfactory understanding of concepts.',
                                'Participates when encouraged but needs to show more initiative.',
                                'Work is generally correct but sometimes lacks detail.',
                            ],
                            'Satisfactory Performance' => [
                                'Progressing steadily but improvement is needed in some areas.',
                                'Effort is inconsistent across topics.',
                                'Tends to rush work; more focus needed on accuracy.',
                                'Occasionally submits incomplete assignments.',
                                'Can achieve more with greater attention to detail.',
                            ],
                            'Poor Performance' => [
                                'Rarely completes assignments on time.',
                                'Demonstrates minimal understanding of key concepts.',
                                'Requires frequent redirection to stay on task.',
                                'Poor performance due to lack of preparation and effort.',
                                'Attendance and punctuality are affecting academic progress.',
                            ],
                            'Performance Concerns' => [
                                'Fails to meet the minimum academic standards.',
                                'Needs ongoing support to improve understanding.',
                                'Shows limited engagement in learning activities.',
                                'Behavioral issues are interfering with academic success.',
                                'At risk of not meeting course requirements without intervention.',
                            ],
                        ];
                        echo "<script>const commentsData = " . json_encode($categorizedComments) . ";</script>";
                    }

                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'today' => date('Y-m-d'));
                    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.dateStart, gibbonInternalAssessmentEntry.*
                        FROM gibbonCourseClassPerson
                        JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        LEFT JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID)
                        WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                        AND gibbonCourseClassPerson.reportable='Y' AND gibbonCourseClassPerson.role='Student'
                        AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today)
                        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                    $result = $pdo->executeQuery($data, $sql);
                    $students = ($result->rowCount() > 0)? $result->fetchAll() : array();

                    $form = Form::create('internalAssessment', $session->get('absoluteURL').'/modules/'.$session->get('module').'/internalAssessment_write_dataProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonInternalAssessmentColumnID='.$gibbonInternalAssessmentColumnID.'&address='.$session->get('address'));
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $session->get('address'));

                    $form->addRow()->addHeading('Assessment Details', __('Assessment Details'));

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->required()->maxLength(1000);

                    $row = $form->addRow();
                        $row->addLabel('file', __('Attachment'));
                        $row->addFileUpload('file')->setAttachment('attachment', $session->get('absoluteURL'), $values['attachment']);

                    if (count($students) == 0) {
                        $form->addRow()->addHeading('Students', __('Students'));
                        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                    } else {
                        $table = $form->addRow()->setHeading('table')->addTable()->setClass('smallIntBorder w-full colorOddEven noMargin noPadding noBorder');

                        $completeText = !empty($values['completeDate'])? __('Marked on').' '.Format::date($values['completeDate']) : __('Unmarked');
                        $detailsText = $values['type'];
                        if ($values['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$values['attachment'])) {
                            $detailsText .= " | <a title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$values['attachment']."'>".__('More info').'</a>';
                        }

                        $header = $table->addHeaderRow();
                            $header->addTableCell(__('Student'))->rowSpan(2);
                            $header->addTableCell($values['name'])
                                ->setTitle($values['description'])
                                ->append('<br><span class="text-xs italic" style="font-weight:normal;">'.$completeText.'</span>')
                                ->append('<br><span class="text-xs italic" style="font-weight:normal;">'.$detailsText.'</span>')
                                ->setClass('textCenter')
                                ->colSpan(3);

                        $header = $table->addHeaderRow();
                            if ($hasAttainment) {
                                $scale = '';
                                if (!empty($values['gibbonScaleIDAttainment'])) {
                                    $form->addHiddenValue('scaleAttainment', $values['gibbonScaleIDAttainment']);
                                    $form->addHiddenValue('lowestAcceptableAttainment', $values['lowestAcceptableAttainment']);
                                    $scale = ' - '.$values['scaleNameAttainment'];
                                    $scale .= $values['usageAttainment']? ': '.$values['usageAttainment'] : '';
                                }
                                $header->addContent($hasAttainmentName? $attainmentAlternativeNameAbrev : __('Att'))
                                    ->setTitle(($hasAttainmentName? $attainmentAlternativeName : __('Attainment')).$scale)
                                    ->setClass('textCenter');
                            }

                            if ($hasEffort) {
                                $scale = '';
                                if (!empty($values['gibbonScaleIDEffort'])) {
                                    $form->addHiddenValue('scaleEffort', $values['gibbonScaleIDEffort']);
                                    $form->addHiddenValue('lowestAcceptableEffort', $values['lowestAcceptableEffort']);
                                    $scale = ' - '.$values['scaleNameEffort'];
                                    $scale .= $values['usageEffort']? ': '.$values['usageEffort'] : '';
                                }
                                $header->addContent($hasEffortName? $effortAlternativeNameAbrev : __('Eff'))
                                    ->setTitle(($hasEffortName? $effortAlternativeName : __('Effort')).$scale)
                                    ->setClass('textCenter');
                            }

                            if ($hasComment || $hasUpload) {
                                $header->addContent(__('Com'))->setTitle(__('Comment'))->setClass('textCenter');
                            }
                    }

                    foreach ($students as $index => $student) {
                        $count = $index+1;
                        $row = $table->addRow();

                        $row->addWebLink(Format::name('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Internal Assessment')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($count.') ');

                        if ($hasAttainment) {
                            $attainment = $row->addSelectGradeScaleGrade($count.'-attainmentValue', $values['gibbonScaleIDAttainment'])->setClass('textCenter gradeSelect');
                            if (!empty($student['attainmentValue'])) $attainment->selected($student['attainmentValue']);
                        }

                        if ($hasEffort) {
                            $effort = $row->addSelectGradeScaleGrade($count.'-effortValue', $values['gibbonScaleIDEffort'])->setClass('textCenter gradeSelect');
                            if (!empty($student['effortValue'])) $effort->selected($student['effortValue']);
                        }

                        if ($hasComment || $hasUpload) {
                            $col = $row->addColumn()->addClass('stacked');

                            if ($hasComment) {
                                // Category → Comment dropdown with improved styling
                                $categoryId = 'commentCategory'.$count;
                                $commentId  = 'commentSelect'.$count;
                                $fieldName  = 'comment'.$count;

                                $col->addContent('
                                    <div class="flex flex-col space-y-2 w-full comment-section" id="commentSection'.$count.'">
                                        <div class="form-group">
                                            <label for="'.$categoryId.'" class="text-xs text-gray-600">Category:</label>
                                            <select id="'.$categoryId.'" class="w-full rounded border border-gray-300 px-2 py-1 text-sm" 
                                                    onchange="populateComments(this.value, '.$count.')">
                                                <option value="">-- Select Category --</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="'.$commentId.'" class="text-xs text-gray-600">Comment:</label>
                                            <select id="'.$commentId.'" 
                                                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                                    onchange="updateComment('.$count.')">
                                                <option value="">-- Select Comment --</option>
                                            </select>
                                        </div>
                                        <div class="mt-2">
                                            <textarea name="'.$fieldName.'" id="customComment'.$count.'" 
                                                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm" 
                                                    rows="2" placeholder="Or type custom comment...">'.$student['comment'].'</textarea>
                                        </div>
                                    </div>
                                ');
                            }

                            if ($hasUpload) {
                                $col->addFileUpload('response'.$count)->setAttachment('attachment'.$count, $session->get('absoluteURL'), $student['response'])->setMaxUpload(false);
                            }
                        }
                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
                    }

                    $form->addHiddenValue('count', $count);

                    $form->addRow()->addHeading('Assessment Complete?', __('Assessment Complete?'));

                    $row = $form->addRow();
                        $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                        $row->addDate('completeDate');

                    $row = $form->addRow();
                        $row->addContent(getMaxUpload(true));
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                    // Update JavaScript for improved functionality
                    echo <<<JS
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Populate category dropdowns
    const total = {$count};
    for (let i = 1; i <= total; i++) {
        const cat = document.getElementById('commentCategory' + i);
        if (!cat) continue;
        Object.keys(commentsData).forEach(category => {
            let opt = document.createElement('option');
            opt.value = category;
            opt.text  = category;
            cat.add(opt);
        });

        // Set up event listeners for comment selection
        const commentSelect = document.getElementById('commentSelect' + i);
        const customComment = document.getElementById('customComment' + i);
        const commentSection = document.getElementById('commentSection' + i);
        
        if (commentSelect && customComment) {
            commentSelect.addEventListener('change', function() {
                updateComment(i);
            });
        }

        // Add focus/blur events for section highlighting
        if (commentSection) {
            const inputs = commentSection.querySelectorAll('select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    commentSection.classList.add('active-section');
                });
                input.addEventListener('blur', () => {
                    if (!commentSection.contains(document.activeElement)) {
                        commentSection.classList.remove('active-section');
                    }
                });
            });
        }
    }
});

function updateComment(idx) {
    const commentSelect = document.getElementById('commentSelect' + idx);
    const customComment = document.getElementById('customComment' + idx);
    if (commentSelect && customComment && commentSelect.value) {
        customComment.value = commentSelect.value;
    }
}

function populateComments(category, idx) {
    const sel = document.getElementById('commentSelect' + idx);
    sel.innerHTML = "<option value=''>-- Select Comment --</option>";
    if (commentsData[category]) {
        commentsData[category].forEach(text => {
            let opt = document.createElement('option');
            opt.value = text;
            opt.text  = text;
            sel.add(opt);
        });
    }
}
</script>

<style>
.form-group {
    margin-bottom: 0.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.25rem;
}
select, textarea {
    width: 100%;
    transition: all 0.2s ease-in-out;
    font-size: 0.875rem;
}
select:focus, textarea:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 1px #4f46e5;
}
.comment-section {
    padding: 0.75rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s ease-in-out;
}
.active-section {
    background-color: #f3f4f6;
}
.text-xs {
    font-size: 0.75rem;
}
.text-sm {
    font-size: 0.875rem;
}
.text-gray-600 {
    color: #4b5563;
}
</style>
JS;
                }
            }
        }

        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write'));
    }
}
