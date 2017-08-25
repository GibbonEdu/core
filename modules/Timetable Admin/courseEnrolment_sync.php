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

    $step = (isset($_REQUEST['step']) && $_REQUEST['step'] <= 3)? $_REQUEST['step'] : 1;

    if ($step > 3) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</div>';
    }

    echo '<h3>';
    echo __($guid, sprintf('Step %1$s', $step));
    echo '</h3>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Step 1
    if ($step == 1) {
        echo '<p>';
        echo __('Syncing enrolment lets you automaticaly enrol students into classes that match a similar grouping of students within the school, such as a Roll Group or House.');
        echo '<p>';

        $form = Form::create('courseEnrolmentSyncStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('step', 2);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Enrolable year groups.'));
            $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple()->isRequired();

        $studentGroupings = array(
            'rollGroup' => __('Roll Group'),
            'yearGroup' => __('Year Group'),
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
            $column = $row->addColumn();
            $column->addLabel('courseClassMapping', __('Compare to Pattern'))->description(sprintf(__('Classes will be matched if they fit the specified pattern. Choose from %1$s. Must contain %2$s'), '[courseShortName] [yearGroupShortName] [rollGroupShortName] [houseShortName]', '[classShortName]'));

            $row->addTextField('pattern')
                ->isRequired()
                ->setValue('[yearGroupShortName]-[classShortName]')
                ->addValidation('Validate.Format', 'pattern: /(\[classShortName\])/');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

    } else if ($step == 2) {
        $gibbonYearGroupIDList = (isset($_POST['gibbonYearGroupIDList']))? $_POST['gibbonYearGroupIDList'] : null;
        $syncBy = (isset($_POST['syncBy']))? $_POST['syncBy'] : null;
        $pattern = (isset($_POST['pattern']))? $_POST['pattern'] : null;

        if (empty($gibbonYearGroupIDList) || empty($syncBy) || empty($pattern)) {
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
        $form->addHiddenValue('gibbonYearGroupIDList', implode(',', $gibbonYearGroupIDList));
        $form->addHiddenValue('syncBy', $syncBy);
        $form->addHiddenValue('pattern', $pattern);

        $row = $form->addRow()->addContent('<h4>'.__('Options').'</h4>');

        $row = $form->addRow();
        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

        $row = $table->addRow();
            $row->addLabel('includeStudents', __('Include Students'));
            $row->addCheckbox('includeStudents')->checked(true);

        if ($syncBy == 'rollGroup') {
            $row = $table->addRow();
                $row->addLabel('includeTeachers', __('Include Teachers'));
                $row->addCheckbox('includeTeachers')->checked(true);
        }

        if ($syncBy == 'rollGroup') {
            $subQuery = "(SELECT syncBy.gibbonRollGroupID FROM gibbonRollGroup AS syncBy WHERE REPLACE(REPLACE(REPLACE(REPLACE(:pattern, '[courseShortName]', gibbonCourse.nameShort), '[classShortName]', gibbonCourseClass.nameShort), '[yearGroupShortName]', gibbonYearGroup.nameShort), '[rollGroupShortName]', nameShort) LIKE CONCAT('%', syncBy.nameShort) AND syncBy.gibbonSchoolYearID=:gibbonSchoolYearID LIMIT 1)";
        } else if ($syncBy == 'yearGroup') {
            $subQuery = "(SELECT syncBy.gibbonYearGroupID FROM gibbonYearGroup AS syncBy WHERE syncBy.nameShort = REPLACE(REPLACE(REPLACE(:pattern, '[classShortName]', gibbonCourseClass.nameShort), '[yearGroupShortName]', gibbonYearGroup.nameShort), '[rollGroupShortName]', nameShort) LIMIT 1)";
        } else if ($syncBy == 'house') {
            $subQuery = "(SELECT syncBy.gibbonHouseID FROM gibbonHouse AS syncBy WHERE syncBy.nameShort = REPLACE(REPLACE(REPLACE(:pattern, '[classShortName]', gibbonCourseClass.nameShort), '[yearGroupShortName]', gibbonYearGroup.nameShort), '[rollGroupShortName]', nameShort) LIMIT 1)";
        }

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupIDList' => implode(',', $gibbonYearGroupIDList), 'pattern' => $pattern);
        $sql = "SELECT gibbonYearGroup.name, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as classShortName, gibbonYearGroup.nameShort as yearGroupShortName, $subQuery as syncTo
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
            $yearGroups = $result->fetchAll(PDO::FETCH_GROUP);

            foreach ($yearGroups as $yearGroupName => $classes) {
                $form->addRow()->addHeading($yearGroupName);
                $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

                $yearGroupSelector = str_replace(' ', '', $yearGroupName);
                $header = $table->addHeaderRow();
                    //$header->addContent(__('Enrol'));
                    $header->addCheckbox('checkall'.$yearGroupSelector)->checked(true);
                    $header->addContent(__('Class'));
                    $header->addContent('');
                    $header->addContent(__('Roll Group'));

                foreach ($classes as $class) {
                    $row = $table->addRow();
                        $row->addCheckbox('syncEnabled['.$class['gibbonCourseClassID'].']')->checked(!empty($class['syncTo']))->setClass($yearGroupSelector);
                        $row->addLabel('className['.$class['gibbonCourseClassID'].']', $class['courseNameShort'].'.'.$class['classShortName'])->setTitle($class['courseNameShort'])->setClass('standardWidth');
                        $row->addContent( (empty($class['syncTo'])? '<em>'.__('No match found').'</em>' : '') )->setClass('shortWidth right');

                    if ($syncBy == 'rollGroup') {
                        $row->addSelectRollGroup('syncTo['.$class['gibbonCourseClassID'].']', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($class['syncTo'])->setClass('mediumWidth');
                    } else if ($syncBy == 'yearGroup') {
                        $row->addSelectYearGroup('syncTo['.$class['gibbonCourseClassID'].']')->selected($class['syncTo'])->setClass('mediumWidth');
                    } else if ($syncBy == 'house') {
                        $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
                        $row->addSelect('syncTo['.$class['gibbonCourseClassID'].']')->fromQuery($pdo, $sql)->selected($class['syncTo'])->placeholder()->setClass('mediumWidth');
                    }
                }

                echo '<script type="text/javascript">';
                echo '$(function () {';
                    echo "$('#checkall".$yearGroupSelector."').click(function () {";
                    echo "$('.".$yearGroupSelector."').find(':checkbox').attr('checked', this.checked);";
                    echo '});';
                echo '});';
                echo '</script>';
            }
        }

        $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

        $row = $table->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

    } else if ($step == 3) {

    }


}
?>
