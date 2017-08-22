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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Sync Course Enrolment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $step = (isset($_REQUEST['step']) && $_REQUEST['step'] <= 3)? $_REQUEST['step'] : 1;

    //Step 1
    if ($step == 1) {
        echo '<h3>';
        echo __($guid, 'Step 1');
        echo '</h3>';

        $form = Form::create('courseEnrolmentSyncStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('step', 2);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
            $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple();

        $studentGroupings = array(
            'yearGroup' => __('Year Group'),
            'rollGroup' => __('Roll Group'),
            'house' => __('House'),
        );

        $row = $form->addRow();
            $row->addLabel('syncBy', __('Sync By'))->description(__('Select a grouping of students to be enroled in a set of matching classes.'));
            $row->addSelect('syncBy')->fromArray($studentGroupings)->isRequired()->placeholder();

        $form->toggleVisibilityByClass('mapping')->onSelect('syncBy')->whenNot('Please select...');

        $form->toggleVisibilityByClass('mapYearGroup')->onSelect('syncBy')->when('yearGroup');
        $form->toggleVisibilityByClass('mapRollGroup')->onSelect('syncBy')->when('rollGroup');
        $form->toggleVisibilityByClass('mapHouse')->onSelect('syncBy')->when('house');

        $row = $form->addRow()->addClass('mapping');
            $row->addLabel('mapLabel', __('Map Classes By'));
            $column = $row->addColumn();
            $column->addTextField('mapYearGroup')->setValue(__('Year Group').' '.__('Short Name'))->addClass('mapYearGroup')->readonly();
            $column->addTextField('mapRollGroup')->setValue(__('Roll Group').' '.__('Short Name'))->addClass('mapRollGroup')->readonly();
            $column->addTextField('mapHouse')->setValue(__('House').' '.__('Short Name'))->addClass('mapHouse')->readonly();

        $row = $form->addRow()->addClass('mapping');
            $row->addLabel('courseClassMapping', __('Compare to Format'))->description(__('How should class names be compared? Choose from [yearGroupShortName] [rollGroupShortName] [houseShortName]. Must contain [classShortName]'));
            $row->addTextField('formatString')
                ->isRequired()
                ->setValue('[yearGroupShortName]-[classShortName]')
                ->addValidation('Validate.Format', 'pattern: /(\[classShortName\])/');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

    } else if ($step == 2) {
        echo '<h3>';
        echo __($guid, 'Step 2');
        echo '</h3>';

        $gibbonYearGroupIDList = (isset($_POST['gibbonYearGroupIDList']))? $_POST['gibbonYearGroupIDList'] : null;
        $gibbonYearGroupIDList = implode(',', $gibbonYearGroupIDList);

        $syncBy = (isset($_POST['syncBy']))? $_POST['syncBy'] : null;
        $formatString = (isset($_POST['formatString']))? $_POST['formatString'] : null;

        if (empty($gibbonYearGroupIDList) || empty($syncBy) || empty($formatString)) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because your inputs were invalid.');
            echo '</div>';
            return;
        }

        $form = Form::create('courseEnrolmentSyncStep2', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_syncProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $renderer = $form->getRenderer();
        $renderer->setWrapper('form', 'div');
        $renderer->setWrapper('row', 'div');
        $renderer->setWrapper('cell', 'div');

        $form->addHiddenValue('step', 3);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addContent('<h4>'.__('Options').'</h4>');

        $row = $form->addRow();
        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

        $row = $table->addRow();
            $row->addLabel('includeStudents', __('Include Students'));
            $row->addCheckbox('includeStudents')->checked(true);

        $row = $table->addRow();
            $row->addLabel('includeTeachers', __('Include Teachers'));
            $row->addCheckbox('includeTeachers')->checked(true);

        $subQuery = "(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort = REPLACE(REPLACE(REPLACE(:formatString, '[classShortName]', gibbonCourseClass.nameShort), '[yearGroupShortName]', gibbonYearGroup.nameShort), '[rollGroupShortName]', nameShort) AND gibbonSchoolYearID=:gibbonSchoolYearID)";

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'formatString' => $formatString);
        $sql = "SELECT gibbonYearGroup.name, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as classShortName, gibbonYearGroup.nameShort as yearGroupShortName, $subQuery as gibbonRollGroupID
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList))
                WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClass.gibbonCourseClassID
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort
                ";
        $result = $pdo->executeQuery($data, $sql);

        if ($result->rowCount() > 0) {
            $courses = $result->fetchAll(PDO::FETCH_GROUP);

            foreach ($courses as $courseName => $classes) {
                $form->addRow()->addHeading($courseName);
                $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

                $header = $table->addHeaderRow();
                    $header->addContent(__('Enrol'));
                    $header->addContent(__('Class'));
                    $header->addContent('');
                    $header->addContent(__('Roll Group'));

                foreach ($classes as $class) {
                    $row = $table->addRow();
                        $row->addCheckbox('className['.$class['gibbonCourseClassID'].']')->checked(!empty($class['gibbonRollGroupID']))->setClass();
                        $row->addLabel('className['.$class['gibbonCourseClassID'].']', $class['courseNameShort'].'.'.$class['classShortName'])->setTitle($class['courseNameShort'])->setClass('standardWidth');
                        $row->addContent( (empty($class['gibbonRollGroupID'])? '<em>'.__('No match found').'</em>' : '') )->setClass('shortWidth right');
                        $row->addSelectRollGroup('rollGroup[]', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($class['gibbonRollGroupID'])->setClass('mediumWidth');
                }
            }
        }

        $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

        $row = $table->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

    } else if ($step == 3) {
        echo '<h3>';
        echo __($guid, 'Step 3');
        echo '</h3>';
    }


}
?>
