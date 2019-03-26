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
use Gibbon\Module\Planner\Forms\PlannerFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Set variables
        $today = date('Y-m-d');

        //Proceed!
        //Get viewBy, date and class variables
        $params = [];
        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'];
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'];
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'] ?? '';
            if (isset($_GET['dateHuman']) == true) {
                $date = dateConvert($guid, $_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
            $params += [
                'viewBy' => 'date',
                'date' => $date,
            ];
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
            $params += [
                'viewBy' => 'class',
                'date' => $class,
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'subView' => $subView,
            ];
        }

        list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
        $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);

        $proceed = true;
        $extra = '';
        if ($viewBy == 'class') {
            if ($gibbonCourseClassID == '') {
                $proceed = false;
            } else {
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    $proceed = false;
                } else {
                    $values = $result->fetch();
                    $extra = $values['course'].'.'.$values['class'];
                    $gibbonDepartmentID = $values['gibbonDepartmentID'];
                    $gibbonYearGroupIDList = $values['gibbonYearGroupIDList'];
                }
            }
        } else {
            $extra = dateConvertBack($guid, $date);
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __('Your request failed because you do not have access to this action.');
            echo '</div>';
        } else {
            $page->breadcrumbs
                ->add(
                    empty($extra) ?
                        __('Planner') :
                        __('Planner for {classDesc}', ['classDesc' => $extra]),
                    'planner.php',
                    $params
                )
                ->add(__('Add Lesson Plan'));

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?' . http_build_query($params + [
                    'q' => '/modules/Planner/planner_edit.php',
                    'gibbonPlannerEntryID' => $_GET['editID'] ?? '',
                ]);
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_addProcess.php?viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address']);
            $form->setFactory(PlannerFormFactory::create($pdo));

            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            //BASIC INFORMATION
            $form->addRow()->addHeading(__('Basic Information'));

            if ($viewBy == 'class') {
                $form->addHiddenValue('gibbonCourseClassID', $values['gibbonCourseClassID']);
                $row = $form->addRow();
                    $row->addLabel('schoolYearName', __('Class'));
                    $row->addTextField('schoolYearName')->setValue($values['course'].'.'.$values['class'])->required()->readonly();
            } else {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                } else {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name';
                }
                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassID', __('Class'));
                    $row->addSelect('gibbonCourseClassID')->fromQuery($pdo, $sql, $data)->required()->placeholder();
            }

            if ($viewBy == 'class') {
                $data = array('gibbonCourseClassID' => $values['gibbonCourseClassID']);
                $sql = "SELECT gibbonCourseClassID AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND active='Y' AND running='Y' ORDER BY name";
                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $row->addSelect('gibbonUnitID')->fromQuery($pdo, $sql, $data)->placeholder();
            }
            else {
                $data = array();
                $sql = "SELECT gibbonCourseClassID AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE active='Y' AND running='Y' ORDER BY name";
                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $row->addSelect('gibbonUnitID')->fromQueryChained($pdo, $sql, $data, 'gibbonCourseClassID')->placeholder();
            }

            $row = $form->addRow();
                $row->addLabel('name', __('Lesson Name'));
                $row->addTextField('name')->setValue()->maxLength(50)->required();

            $row = $form->addRow();
                $row->addLabel('summary', __('Summary'));
                $row->addTextField('summary')->setValue()->maxLength(255);

            //Try and find the next unplanned slot for this class.
            if ($viewBy == 'class') {
                //Get $_GET values
                $nextDate = null;
                if (isset($_GET['date'])) {
                    $nextDate = $_GET['date'];
                }
                $nextTimeStart = null;
                if (isset($_GET['timeStart'])) {
                    $nextTimeStart = $_GET['timeStart'];
                }
                $nextTimeEnd = null;
                if (isset($_GET['timeEnd'])) {
                    $nextTimeEnd = $_GET['timeEnd'];
                }

                if ($nextDate == '') {
                    try {
                        $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => date('Y-m-d'));
                        $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10';
                        $resultNext = $connection2->prepare($sqlNext);
                        $resultNext->execute($dataNext);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    $nextDate = '';
                    $nextTimeStart = '';
                    $nextTimeEnd = '';
                    while ($rowNext = $resultNext->fetch()) {
                        try {
                            $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                            $resultPlanner = $connection2->prepare($sqlPlanner);
                            $resultPlanner->execute($dataPlanner);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultPlanner->rowCount() == 0) {
                            $nextDate = $rowNext['date'];
                            $nextTimeStart = $rowNext['timeStart'];
                            $nextTimeEnd = $rowNext['timeEnd'];
                            break;
                        }
                    }
                }
            }

            if ($viewBy == 'date') {
                $row = $form->addRow();
                    $row->addLabel('date', __('Date'));
                    $row->addDate('date')->setValue(dateConvertBack($guid, $date))->required()->readonly();
            }
            else {
                $row = $form->addRow();
                    $row->addLabel('date', __('Date'));
                    $row->addDate('date')->setValue(dateConvertBack($guid, $nextDate))->required();
            }

            $nextTimeStart = (isset($nextTimeStart)) ? substr($nextTimeStart, 0, 5) : null;
            $row = $form->addRow();
                $row->addLabel('timeStart', __('Start Time'))->description("Format: hh:mm (24hr)");
                $row->addTime('timeStart')->setValue($nextTimeStart)->required();

            $nextTimeEnd = (isset($nextTimeEnd)) ? substr($nextTimeEnd, 0, 5) : null;
            $row = $form->addRow();
                $row->addLabel('timeEnd', __('End Time'))->description("Format: hh:mm (24hr)");
                $row->addTime('timeEnd')->setValue($nextTimeEnd)->required();

            $description = getSettingByScope($connection2, 'Planner', 'lessonDetailsTemplate') ;
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('description', __('Lesson Details'));
                $column->addEditor('description', $guid)->setRows(25)->showMedia()->setValue($description);

            $teachersNotes = getSettingByScope($connection2, 'Planner', 'teachersNotesTemplate');
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('teachersNotes', __('Teacher\'s Notes'));
                $column->addEditor('teachersNotes', $guid)->setRows(25)->showMedia()->setValue($teachersNotes);

            //HOMEWORK
            $form->addRow()->addHeading(__('Homework'));

            $form->toggleVisibilityByClass('homework')->onRadio('homework')->when('Y');
            $row = $form->addRow();
                $row->addLabel('homework', __('Homework?'));
                $row->addRadio('homework')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkDueDate', __('Homework Due Date'));
                $row->addDate('homeworkDueDate')->required();

            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkDueDateTime', __('Homework Due Date Time'))->description("Format: hh:mm (24hr)");
                $row->addTime('homeworkDueDateTime');

            $row = $form->addRow()->addClass('homework');
                $column = $row->addColumn();
                $column->addLabel('homeworkDetails', __('Homework Details'));
                $column->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->setValue($description)->required();

            $form->toggleVisibilityByClass('homeworkSubmission')->onRadio('homeworkSubmission')->when('Y');
            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkSubmission', __('Online Submission?'));
                $row->addRadio('homeworkSubmission')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            $row = $form->addRow()->setClass('homework homeworkSubmission');
                $row->addLabel('homeworkSubmissionDateOpen', __('Submission Open Date'));
                $row->addDate('homeworkSubmissionDateOpen')->required();

            $row = $form->addRow()->setClass('homework homeworkSubmission');
                $row->addLabel('homeworkSubmissionDrafts', __('Drafts'));
                $row->addSelect('homeworkSubmissionDrafts')->fromArray(array('0' => __('None'), '1' => __('1'), '2' => __('2'), '3' => __('3')))->required();

            $row = $form->addRow()->setClass('homework homeworkSubmission');
                $row->addLabel('homeworkSubmissionType', __('Submission Type'));
                $row->addSelect('homeworkSubmissionType')->fromArray(array('Link' => __('Link'), 'File' => __('File'), 'Link/File' => __('Link/File')))->required();

            $row = $form->addRow()->setClass('homework homeworkSubmission');
                $row->addLabel('homeworkSubmissionRequired', __('Submission Required'));
                $row->addSelect('homeworkSubmissionRequired')->fromArray(array('Optional' => __('Optional'), 'Compulsory' => __('Compulsory')))->required();

            if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php')) {
                $form->toggleVisibilityByClass('homeworkCrowdAssess')->onRadio('homeworkCrowdAssess')->when('Y');
                $row = $form->addRow()->addClass('homework homeworkSubmission');
                    $row->addLabel('homeworkCrowdAssess', __('Crowd Assessment?'));
                    $row->addRadio('homeworkCrowdAssess')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

                $row = $form->addRow()->addClass('homework homeworkSubmission homeworkCrowdAssess');
                    $row->addLabel('homeworkCrowdAssessControl', __('Access Controls?'))->description(__('Decide who can see this homework.'));
                    $column = $row->addColumn();
                        $column->addCheckbox('homeworkCrowdAssessClassTeacher')->checked(true)->description(__('Class Teacher'))->disabled();
                        $column->addCheckbox('homeworkCrowdAssessClassSubmitter')->checked(true)->description(__('Submitter'))->disabled();
                        $column->addCheckbox('homeworkCrowdAssessClassmatesRead')->description(__('Classmates'));
                        $column->addCheckbox('homeworkCrowdAssessOtherStudentsRead')->description(__('Other Students'));
                        $column->addCheckbox('homeworkCrowdAssessOtherTeachersRead')->description(__('Other Teachers'));
                        $column->addCheckbox('homeworkCrowdAssessSubmitterParentsRead')->description(__('Submitter\'s Parents'));
                        $column->addCheckbox('homeworkCrowdAssessClassmatesParentsRead')->description(__('Classmates\'s Parents'));
                        $column->addCheckbox('homeworkCrowdAssessOtherParentsRead')->description(__('Other Parents'));
            }

            //OUTCOMES
            if ($viewBy == 'date') {
                $form->addRow()->addHeading(__('Outcomes'));
                $form->addRow()->addAlert(__('Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.'), 'warning');
            }
            else {
                $form->addRow()->addHeading(__('Outcomes'));
                $form->addRow()->addContent(__('Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.'));

                // Fee selector
                $outcomeSelector = $form->getFactory()->createSelectOutcome('newOutcome', $gibbonYearGroupIDList, $gibbonDepartmentID)->addClass('addBlock');

                // Block template
                $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
                    $row = $blockTemplate->addRow();
                        $row->addTextField('outcometitle')->setClass('standardWidth floatLeft noMargin title')->required()->placeholder(__('Outcome Name'))->readonly()
                            ->append('<input type="hidden" id="outcomegibbonOutcome" name="outcomegibbonOutcome" value="">');

                    $col = $blockTemplate->addRow()->addColumn()->addClass('inline');
                        $col->addTextField('outcomecategory')->setClass('standardWidth floatLeft noMargin')->required()->placeholder(__('Category'))->readonly();

                    $col = $blockTemplate->addRow()->addClass('showHide fullWidth')->addColumn();
                        $col->addLabel('description', __('Description'));
                        $col->addTextArea('description')->setRows('auto')->setClass('fullWidth floatNone noMargin');

                // Custom Blocks for Fees
                $row = $form->addRow();
                    $customBlocks = $row->addCustomBlocks('feesBlock', $gibbon->session)
                        ->fromTemplate($blockTemplate)
                        ->settings(array('inputNameStrategy' => 'string', 'addOnEvent' => 'change', 'sortable' => true))
                        ->placeholder(__('Key outcomes listed here...'))
                        ->addToolInput($outcomeSelector)
                        ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

                // Add predefined block data (for templating new blocks, triggered with the outcomeSelector)
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonOutcomeID as groupBy, gibbonOutcomeID, name, description FROM gibbonOutcome ORDER BY name";
                $result = $pdo->executeQuery($data, $sql);
                $outcomeData = $result->rowCount() > 0? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

                $customBlocks->addPredefinedBlock('Ad Hoc Fee', array('feeType' => 'Ad Hoc', 'gibbonFinanceFeeID' => 0));
                foreach ($outcomeData as $gibbonOutcomeID => $data) {
                    $customBlocks->addPredefinedBlock($gibbonOutcomeID, $data);
                }
            }

            //MARKBOOK
            $form->addRow()->addHeading(__('Markbook'));

            $form->toggleVisibilityByClass('homework')->onRadio('homework')->when('Y');
            $row = $form->addRow();
                $row->addLabel('markbook', __('Create Markbook Column?'))->description('Linked to this lesson by default.');
                $row->addRadio('markbook')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            //ADVANCED
            $form->addRow()->addHeading(__('Advanced Options'));

            $form->toggleVisibilityByClass('advanced')->onCheckbox('advanced')->when('Y');
            $row = $form->addRow();
                $row->addCheckbox('advanced')->setValue('Y')->description('Show Advanced Options');

            //Access
            $form->addRow()->addHeading(__('Access'))->addClass('advanced');

            $sharingDefaultStudents = getSettingByScope($connection2, 'Planner', 'sharingDefaultStudents');
            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('viewableStudents', __('Viewable to Students'));
                $row->addYesNo('viewableStudents')->required()->selected($sharingDefaultStudents);

            $sharingDefaultParents = getSettingByScope($connection2, 'Planner', 'sharingDefaultParents');
            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('viewableParents', __('Viewable to Parents'));
                $row->addYesNo('viewableParents')->required()->selected($sharingDefaultParents);

            //Guests
            $form->addRow()->addHeading(__('Guests'))->addClass('advanced');

            $row = $form->addRow()->addClass('advanced');;
                $row->addLabel('guests', __('Guest List'));
                $row->addSelectUsers('guests')->selectMultiple();

            $roles = array(
                'Guest Student' => __('Guest Student'),
                'Guest Teacher' => __('Guest Teacher'),
                'Guest Assistant' => __('Guest Assistant'),
                'Guest Technician' => __('Guest Technician'),
                'Guest Parent' => __('Guest Parent'),
                'Other Guest' => __('Other Guest'),
            );
            $row = $form->addRow()->addClass('advanced');;;
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($roles);

            // Outcomes

            $form->addRow()->addHeading(__('Outcomes'));

            if ($viewBy == 'date') {
                $form->addRow()->addAlert(__('Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.'), 'warning');
            } else {
                $form->addRow()->addContent(__('Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.'));

                $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');

                // CUSTOM BLOCKS
        
                // Outcome selector
                $outcomeSelector = $form->getFactory()
                    ->createSelectOutcome('addOutcome', $gibbonYearGroupIDList, $gibbonDepartmentID)
                    ->addClass('addBlock');

                // Block template
                $blockTemplate = $form->getFactory()->createTable()->setClass('blank w-full');
                    $row = $blockTemplate->addRow();
                    $row->addTextField('outcometitle')
                        ->setClass('w-3/4 floatLeft noMargin title readonly')
                        ->readonly()
                        ->placeholder(__('Outcome Name'))
                        ->append('<input type="hidden" id="gibbonOutcomeID" name="gibbonOutcomeID" value="">');

                    $row = $blockTemplate->addRow();
                    $row->addTextField('outcomecategory')
                        ->setClass('w-3/4 floatLeft noMargin readonly')
                        ->readonly();
                        
                    $col = $blockTemplate->addRow()->addClass('showHide fullWidth max-w-full')->addColumn();
                    $col->addEditor('outcomecontents', $guid)->setRows(10)->setClass('max-w-full');

                // Custom Blocks for Outcomes
                $row = $form->addRow()->addClass('ui-state-default_dud');
                    $customBlocks = $row->addCustomBlocks('outcomes', $gibbon->session)
                        ->fromTemplate($blockTemplate)
                        ->settings([
                            'inputNameStrategy' => 'string',
                            'addOnEvent' => 'change',
                            'sortable' => true,
                        ])
                        ->placeholder(__('Key outcomes listed here...'))
                        ->addToolInput($outcomeSelector)
                        ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

                // Add predefined block data (for templating new blocks, triggered with the feeSelector)
                $sql = "SELECT gibbonOutcomeID, name as outcometitle, category as outcomecategory FROM gibbonOutcome ORDER BY name";
                $outcomeData = $pdo->select($sql)->fetchAll();

                foreach ($outcomeData as $outcome) {
                    $customBlocks->addPredefinedBlock($outcome['gibbonOutcomeID'], $outcome);
                }
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addCheckbox('notify')->description('Notify all class participants');
                $row->addSubmit();
                
            echo $form->getOutput();


            ?>

			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_addProcess.php?viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<?php
					//OUTCOMES
                    if ($viewBy == 'date') {

                    } else {
                        $type = 'outcome';
                        $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');
                        $categories = array();
                        $categoryCount = 0;
                        ?>
						<style>
							#<?php echo $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<?php echo $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<?php echo $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<?php echo $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<?php echo $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<script>
							$(function() {
								$( "#<?php echo $type ?>" ).sortable({
									placeholder: "<?php echo $type ?>-ui-state-highlight",
									axis: 'y'
								});
							});
						</script>
						<tr>
							<td colspan=2>
								<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
										<div id="outcomeOuter0">
											<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>Key outcomes listed here...</div>
										</div>
									</div>
								<div style='width: 100%; padding: 0px 0px 0px 0px'>
									<div class="ui-state-default_dud" style='padding: 0px; min-height: 66px'>
										<table class='blank' cellspacing='0' style='width: 100%'>
											<tr>
												<td style='width: 50%'>
													<script type="text/javascript">
														var outcomeCount=1 ;
														/* Unit type control */
														$(document).ready(function(){
															$("#new").click(function(){

															 });
														});
													</script>
                                                    <select id='newOutcome2' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'><?php echo __('Choose an outcome to add it to this lesson') ?></option>
														<?php
                                                        $currentCategory = '';
														$lastCategory = '';
														$switchContents = '';

														try {
															$countClause = 0;
															$years = explode(',', $gibbonYearGroupIDList);
															$dataSelect = array();
															$sqlSelect = '';
															foreach ($years as $year) {
																$dataSelect['clause'.$countClause] = '%'.$year.'%';
																$sqlSelect .= "(SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
																++$countClause;
															}
															$resultSelect = $connection2->prepare(substr($sqlSelect, 0, -6).'ORDER BY category, name');
															$resultSelect->execute($dataSelect);
														} catch (PDOException $e) {
															echo "<div class='error'>".$e->getMessage().'</div>';
														}
														echo "<optgroup label='--".__('SCHOOL OUTCOMES')."--'>";
														while ($rowSelect = $resultSelect->fetch()) {
															$currentCategory = $rowSelect['category'];
															if (($currentCategory != $lastCategory) and $currentCategory != '') {
																echo "<optgroup label='--".$currentCategory."--'>";
																echo "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>";
																$categories[$categoryCount] = $currentCategory;
																++$categoryCount;
															}
															echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
															$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
															$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
															$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/units_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
															$switchContents .= 'outcomeCount++ ;';
															$switchContents .= "$('#newOutcome2').val('0');";
															$switchContents .= 'break;';
															$lastCategory = $rowSelect['category'];
														}

														if ($gibbonDepartmentID != '') {
															$currentCategory = '';
															$lastCategory = '';
															$currentLA = '';
															$lastLA = '';
															try {
																$countClause = 0;
																$years = explode(',', $gibbonYearGroupIDList);
																$dataSelect = array('gibbonDepartmentID' => $gibbonDepartmentID);
																$sqlSelect = '';
																foreach ($years as $year) {
																	$dataSelect['clause'.$countClause] = '%'.$year.'%';
																	$sqlSelect .= "(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
																	++$countClause;
																}
																$resultSelect = $connection2->prepare(substr($sqlSelect, 0, -6).'ORDER BY learningArea, category, name');
																$resultSelect->execute($dataSelect);
															} catch (PDOException $e) {
																echo "<div class='error'>".$e->getMessage().'</div>';
															}
															while ($rowSelect = $resultSelect->fetch()) {
																$currentCategory = $rowSelect['category'];
																$currentLA = $rowSelect['learningArea'];
																if (($currentLA != $lastLA) and $currentLA != '') {
																	echo "<optgroup label='--".strToUpper($currentLA).' '.__('OUTCOMES')."--'>";
																}
																if (($currentCategory != $lastCategory) and $currentCategory != '') {
																	echo "<optgroup label='--".$currentCategory."--'>";
																	echo "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>";
																	$categories[$categoryCount] = $currentCategory;
																	++$categoryCount;
																}
																echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
																$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
																$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
																$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/units_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
																$switchContents .= 'outcomeCount++ ;';
																$switchContents .= "$('#newOutcome2').val('0');";
																$switchContents .= 'break;';
																$lastCategory = $rowSelect['category'];
																$lastLA = $rowSelect['learningArea'];
															}
														}
														?>
													</select><br/>
													<?php
                                                    if (count($categories) > 0) {
                                                        ?>
														<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
															<option value='all'><?php echo __('View All') ?></option>
															<?php
                                                            $categories = array_unique($categories);
                                                        $categories = msort($categories);
                                                        foreach ($categories as $category) {
                                                            echo "<option value='$category'>$category</option>";
                                                        }
                                                        ?>
														</select>
														<script type="text/javascript">
															$("#newOutcome2").chainedTo("#outcomeFilter");
														</script>
														<?php

                                                    }
                        							?>
                        							<script type='text/javascript'>
														var <?php echo $type ?>Used=new Array();
														var <?php echo $type ?>UsedCount=0 ;

														function outcomeDisplayElements(number) {
															$("#<?php echo $type ?>Outer0").css("display", "none") ;
															if (<?php echo $type ?>Used.indexOf(number)<0) {
																<?php echo $type ?>Used[<?php echo $type ?>UsedCount]=number ;
																<?php echo $type ?>UsedCount++ ;
																switch(number) {
																	<?php echo $switchContents ?>
																}
															}
															else {
																alert("This element has already been selected!") ;
																$('#newOutcome2').val('0');
															}
														}
													</script>
												</td>
											</tr>
										</table>
									</div>
								</div>
							</td>
						</tr>
						<?php

                    }
            		?>
				</table>
			</form>
			<?php

        }

        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
    }
}
?>
