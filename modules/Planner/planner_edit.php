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
use Gibbon\Module\Planner\Forms\PlannerFormFactory;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Set variables
        $today = date('Y-m-d');

        $settingGateway = $container->get(SettingGateway::class);
        $homeworkNameSingular = $settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');
        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

        //Proceed!
        //Get viewBy, date and class variables
        $params = [];
        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'] ?? '';
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'] ?? '';
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'] ?? '';
            if (isset($_GET['dateHuman'])) {
                $date = Format::dateConvert($_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            [$dateYear, $dateMonth, $dateDay] = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
            $params += [
                'viewBy' => 'date',
                'date' => $date,
            ];
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'] ?? '';
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
            $params += [
                'viewBy' => 'class',
                'date' => $class,
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'subView' => $subView,
            ];
        }
        $paramsVar = '&' . http_build_query($params); // for backward compatibile uses below (should be get rid of)

        [$todayYear, $todayMonth, $todayDay] = explode('-', $today);
        $todayStamp = mktime(12, 0, 0, $todayMonth, $todayDay, $todayYear);

        //Check if gibbonPlannerEntryID and gibbonCourseClassID specified
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        }
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
        if ($gibbonPlannerEntryID == '' or ($viewBy == 'class' and $gibbonCourseClassID == 'Y')) {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            try {
                if ($viewBy == 'date') {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('date' => $date, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                    } else {
                        $data = array('date' => $date, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                    }
                } else {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                    } else {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonPlannerEntry.*, gibbonCourse.gibbonYearGroupIDList FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                    }
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Let's go!
                $values = $result->fetch();

                if ($viewBy == 'date') {
                    $extra = Format::date($date);
                } else {
                    $extra = $values['course'].'.'.$values['class'];
                    $gibbonDepartmentID = $values['gibbonDepartmentID'];
                }
                $gibbonYearGroupIDList = $values['gibbonYearGroupIDList'];

                $page->breadcrumbs
                    ->add(__('Planner for {classDesc}', [
                        'classDesc' => $extra,
                    ]), 'planner.php', $params)
                    ->add(__('Edit Lesson Plan'));

                //Get gibbonUnitClassID
                $gibbonUnitID = $values['gibbonUnitID'];
                $gibbonUnitClassID = null;

                $dataUnitClass = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                $sqlUnitClass = 'SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID';
                $resultUnitClass = $connection2->prepare($sqlUnitClass);
                $resultUnitClass->execute($dataUnitClass);
                if ($resultUnitClass->rowCount() == 1) {
                    $rowUnitClass = $resultUnitClass->fetch();
                    $gibbonUnitClassID = $rowUnitClass['gibbonUnitClassID'];
                }

                $returns = array();
                $returns['success1'] = __('Your request was completed successfully.').__('You can now edit more details of your newly duplicated entry.');
                $page->return->addReturns($returns);

                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/planner_editProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&address=".$session->get('address'));
                $form->setFactory(PlannerFormFactory::create($pdo));

                $form->addHiddenValue('address', $session->get('address'));
                
                $params["gibbonPlannerEntryID"] = $gibbonPlannerEntryID;
                $form->addHeaderAction('view', __('View'))
                    ->setURL('/modules/Planner/planner_view_full.php')
                    ->addParams($params)
                    ->setIcon('plus')
                    ->displayLabel();

                //BASIC INFORMATION
                $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name';
                }
                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassID', __('Class'));
                    $row->addSelect('gibbonCourseClassID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

                $sql = "SELECT GROUP_CONCAT(gibbonCourseClassID SEPARATOR ' ') AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE active='Y' AND running='Y'  GROUP BY gibbonUnit.gibbonUnitID ORDER BY ordering, name";
                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $row->addSelect('gibbonUnitID')->fromQueryChained($pdo, $sql, [], 'gibbonCourseClassID')->placeholder();

                $row = $form->addRow();
                    $row->addLabel('name', __('Lesson Name'));
                    $row->addTextField('name')->setValue()->maxLength(50)->required();

                $row = $form->addRow();
                    $row->addLabel('summary', __('Summary'));
                    $row->addTextField('summary')->setValue()->maxLength(255);

                $row = $form->addRow();
                    $row->addLabel('date', __('Date'));
                    $row->addDate('date')->required();

                $nextTimeStart = !empty($nextTimeStart) ? substr($nextTimeStart, 0, 5) : null;
                $row = $form->addRow();
                    $row->addLabel('timeStart', __('Start Time'));
                    $row->addTime('timeStart')->required();

                $nextTimeEnd = !empty($nextTimeEnd) ? substr($nextTimeEnd, 0, 5) : null;
                $row = $form->addRow();
                    $row->addLabel('timeEnd', __('End Time'));
                    $row->addTime('timeEnd')->required();


                //LESSON
                $form->addRow()->addHeading('Lesson Content', __('Lesson Content'));

                $description = $settingGateway->getSettingByScope('Planner', 'lessonDetailsTemplate') ;
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('description', __('Lesson Details'));
                    $column->addEditor('description', $guid)->setRows(25)->showMedia()->setValue($description);

                $teachersNotes = $settingGateway->getSettingByScope('Planner', 'teachersNotesTemplate');
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('teachersNotes', __('Teacher\'s Notes'));
                    $column->addEditor('teachersNotes', $guid)->setRows(25)->showMedia()->setValue($teachersNotes);

                //SMART BLOCKS
                if (!empty($values['gibbonUnitID'])) {
                    $form->addRow()->addHeading('Smart Blocks', __('Smart Blocks'));

                    $form->addRow()->addContent("<div class='float-right'><a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/units_edit_working.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=".$values['gibbonCourseID'].'&gibbonUnitID='.$values['gibbonUnitID'].'&gibbonSchoolYearID='.$session->get('gibbonSchoolYearID')."&gibbonUnitClassID=$gibbonUnitClassID'>".__('Edit Unit').'</a></div>');

                    $row = $form->addRow();
                        $customBlocks = $row->addPlannerSmartBlocks('smart', $session, $guid);

                    $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sqlBlocks = 'SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber';
                    $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

                    while ($rowBlocks = $resultBlocks->fetch()) {
                        $smart = array(
                            'title' => $rowBlocks['title'],
                            'type' => $rowBlocks['type'],
                            'length' => $rowBlocks['length'],
                            'contents' => $rowBlocks['contents'],
                            'teachersNotes' => $rowBlocks['teachersNotes'],
                            'gibbonUnitClassBlockID' => $rowBlocks['gibbonUnitClassBlockID']
                        );
                        $customBlocks->addBlock($rowBlocks['gibbonUnitClassBlockID'], $smart);
                    }
                }

                //HOMEWORK
                $form->addRow()->addHeading('Homework', __($homeworkNameSingular));

                $form->toggleVisibilityByClass('homework')->onRadio('homework')->when('Y');
                $row = $form->addRow();
                    $row->addLabel('homework', __('Add {homeworkName}?', ['homeworkName' => __($homeworkNameSingular)]));
                    $row->addRadio('homework')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

                if (!empty($values['homeworkDueDateTime'])) {
                    $values['homeworkDueDate'] = substr(Format::date($values['homeworkDueDateTime'], 'Y-m-d H:i:s'), 0, 10);
                    $values['homeworkDueDateTime'] = substr($values['homeworkDueDateTime'], 11, 5);
                }

                $row = $form->addRow()->addClass('homework');
                    $row->addLabel('homeworkDueDate', __('Due Date'))->description(__('Date is required, time is optional.'));
                    $col = $row->addColumn('homeworkDueDate')->addClass('homework');
                    $col->addDate('homeworkDueDate')->addClass('mr-2')->required();
                    $col->addTime('homeworkDueDateTime');

                $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkTimeCap', __('Time Cap?'))->description(__('The maximum time, in minutes, for students to work on this.'));
                    $row->addNumber('homeworkTimeCap');

                $row = $form->addRow()->addClass('homework');
                    $column = $row->addColumn();
                    $column->addLabel('homeworkDetails', __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]));
                    $column->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->setValue($description)->required();

                $form->toggleVisibilityByClass('homeworkSubmission')->onRadio('homeworkSubmission')->when('Y');
                $row = $form->addRow()->addClass('homework');
                    $row->addLabel('homeworkSubmission', __('Online Submission?'));
                    $row->addRadio('homeworkSubmission')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

                $values['homeworkSubmissionDateOpen'] = (!empty($values['homeworkSubmissionDateOpen'])) ? $values['homeworkSubmissionDateOpen'] : date('Y-m-d') ;
                $row = $form->addRow()->setClass('homeworkSubmission');
                    $row->addLabel('homeworkSubmissionDateOpen', __('Submission Open Date'));
                    $row->addDate('homeworkSubmissionDateOpen')->required();

                $row = $form->addRow()->setClass('homeworkSubmission');
                    $row->addLabel('homeworkSubmissionDrafts', __('Drafts'));
                    $row->addSelect('homeworkSubmissionDrafts')->fromArray(array('' => __('None'), '1' => __('1'), '2' => __('2'), '3' => __('3')));

                $row = $form->addRow()->setClass('homeworkSubmission');
                    $row->addLabel('homeworkSubmissionType', __('Submission Type'));
                    $row->addSelect('homeworkSubmissionType')->fromArray(array('Link' => __('Link'), 'File' => __('File'), 'Link/File' => __('Link/File')))->required();

                $row = $form->addRow()->setClass('homeworkSubmission');
                    $row->addLabel('homeworkSubmissionRequired', __('Submission Required'));
                    $row->addSelect('homeworkSubmissionRequired')->fromArray(array('Optional' => __('Optional'), 'Required' => __('Required')))->required();

                if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php')) {
                    $form->toggleVisibilityByClass('homeworkCrowdAssess')->onRadio('homeworkCrowdAssess')->when('Y');
                    $row = $form->addRow()->addClass('homeworkSubmission');
                        $row->addLabel('homeworkCrowdAssess', __('Crowd Assessment?'));
                        $row->addRadio('homeworkCrowdAssess')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->inline(true);

                    $row = $form->addRow()->addClass('homeworkCrowdAssess');
                        $row->addLabel('homeworkCrowdAssessControl', __('Access Controls?'))->description(__('Decide who can see this homework.'));
                        $column = $row->addColumn()->setClass('flex-col items-end');
                            $column->addCheckbox('homeworkCrowdAssessClassTeacher')->checked(true)->description(__('Class Teacher'))->disabled();
                            $column->addCheckbox('homeworkCrowdAssessClassSubmitter')->checked(true)->description(__('Submitter'))->disabled();
                            $column->addCheckbox('homeworkCrowdAssessClassmatesRead')->setValue('Y')->description(__('Classmates'));
                            $column->addCheckbox('homeworkCrowdAssessOtherStudentsRead')->setValue('Y')->description(__('Other Students'));
                            $column->addCheckbox('homeworkCrowdAssessOtherTeachersRead')->setValue('Y')->description(__('Other Teachers'));
                            $column->addCheckbox('homeworkCrowdAssessSubmitterParentsRead')->setValue('Y')->description(__("Submitter's Parents"));
                            $column->addCheckbox('homeworkCrowdAssessClassmatesParentsRead')->setValue('Y')->description(__("Classmates's Parents"));
                            $column->addCheckbox('homeworkCrowdAssessOtherParentsRead')->setValue('Y')->description(__('Other Parents'));
                }

                // MARKBOOK
                $form->addRow()->addHeading(__('Markbook'));
                // Check database for a linked markbook column
                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = 'SELECT mb.gibbonMarkbookColumnID FROM gibbonMarkbookColumn AS mb WHERE :gibbonPlannerEntryID=mb.gibbonPlannerEntryID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
                if ($result->rowCount() != 0) {
                    $row = $form->addRow();
                    $row->addLabel('markbook', __('Markbook Column Already Created'))->description(__('A Markbook column has already been created for this assignment.'));
                } else {
                    $row = $form->addRow();
                    $row->addLabel('markbook', __('Create Markbook Column?'))->description(__('Linked to this lesson by default.'));
                    $row->addRadio('markbook')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);
                }

                // OUTCOMES
                if ($viewBy == 'date') {
                    $form->addRow()->addHeading('Outcomes', __('Outcomes'));
                    $form->addRow()->addAlert(__('Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.'), 'warning');
                } else {
                    $form->addRow()->addHeading('Outcomes', __('Outcomes'));
                    $form->addRow()->addContent(__('Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.'));

                    $allowOutcomeEditing = $settingGateway->getSettingByScope('Planner', 'allowOutcomeEditing');

                    $row = $form->addRow();
                        $customBlocks = $row->addPlannerOutcomeBlocks('outcome', $session, $gibbonYearGroupIDList, $gibbonDepartmentID, $allowOutcomeEditing);

                    $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sqlBlocks = 'SELECT gibbonPlannerEntryOutcome.*, scope, name, category FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryOutcome.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber';
                    $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

                    while ($rowBlocks = $resultBlocks->fetch()) {
                        $outcome = array(
                            'outcometitle' => $rowBlocks['name'],
                            'outcomegibbonOutcomeID' => $rowBlocks['gibbonOutcomeID'],
                            'outcomecategory' => $rowBlocks['category'],
                            'outcomecontents' => $rowBlocks['content']
                        );
                        $customBlocks->addBlock($rowBlocks['gibbonOutcomeID'], $outcome);
                    }
                }

                //Access
                $form->addRow()->addHeading('Access', __('Access'));

                $row = $form->addRow();
                    $row->addLabel('viewableStudents', __('Viewable to Students'));
                    $row->addYesNo('viewableStudents')->required();

                $row = $form->addRow();
                    $row->addLabel('viewableParents', __('Viewable to Parents'));
                    $row->addYesNo('viewableParents')->required();

                //Guests
                $form->addRow()->addHeading('Guests', __('Current Guests'));

                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = "SELECT title, preferredName, surname, category, gibbonPlannerEntryGuest.* FROM gibbonPlannerEntryGuest JOIN gibbonPerson ON (gibbonPlannerEntryGuest.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY surname, preferredName";

                $results = $pdo->executeQuery($data, $sql);

                if ($results->rowCount() == 0) {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                } else {
                    $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a guest, any unsaved changes to this planner entry will be lost!'))->wrap('<i>', '</i>');

                    $table = $form->addRow()->addTable()->addClass('colorOddEven');

                    $header = $table->addHeaderRow();
                    $header->addContent(__('Name'));
                    $header->addContent(__('Role'));
                    $header->addContent(__('Action'));

                    while ($staff = $results->fetch()) {
                        $row = $table->addRow();
                        $row->addContent(Format::name('', $staff['preferredName'], $staff['surname'], 'Staff', true, true));
                        $row->addContent($staff['role']);
                        $row->addContent("<a onclick='return confirm(\"".__('Are you sure you wish to delete this record?')."\")' href='".$session->get('absoluteURL')."/modules/".$session->get('module')."/planner_edit_guest_deleteProcess.php?gibbonPlannerEntryGuestID=".$staff['gibbonPlannerEntryGuestID']."&gibbonPlannerEntryID=".$gibbonPlannerEntryID."&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&address=".$_GET['q']."'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>");
                    }
                }

                $form->addRow()->addHeading('New Guests', __('New Guests'));

                $row = $form->addRow();
                    $row->addLabel('guests', __('Guest List'));
                    $row->addSelectUsers('guests', $session->get('gibbonSchoolYearID'))->selectMultiple();

                $roles = array(
                    'Guest Student' => __('Guest Student'),
                    'Guest Teacher' => __('Guest Teacher'),
                    'Guest Assistant' => __('Guest Assistant'),
                    'Guest Technician' => __('Guest Technician'),
                    'Guest Parent' => __('Guest Parent'),
                    'Other Guest' => __('Other Guest'),
                );
                $row = $form->addRow();
                    $row->addLabel('role', __('Role'));
                    $row->addSelect('role')->fromArray($roles);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addCheckbox('notify')->description(__('Notify all class participants'));
                    $row->addSubmit();

                $form->loadAllValuesFrom($values);

                // CUSTOM FIELDS
                $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Lesson Plan', [], $values['fields'] ?? '');

                echo $form->getOutput();

            }
        }
        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $todayStamp, $session->get('gibbonPersonID'), $dateStamp, $gibbonCourseClassID));
    }
}
