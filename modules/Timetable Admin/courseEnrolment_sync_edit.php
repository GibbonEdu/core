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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/courseEnrolment_sync.php'>".__($guid, 'Sync Course Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Map Classes').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonYearGroupID = (isset($_REQUEST['gibbonYearGroupID']))? $_REQUEST['gibbonYearGroupID'] : null;
    $pattern = (isset($_POST['pattern']))? $_POST['pattern'] : null;

    if (empty($gibbonYearGroupID)) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</div>';
        return;
    }

    $form = Form::create('courseEnrolmentSyncEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $renderer = $form->getRenderer();
    $renderer->setWrapper('form', 'div');
    $renderer->setWrapper('row', 'div');
    $renderer->setWrapper('cell', 'div');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
    $form->addHiddenValue('pattern', $pattern);

    if (!empty($pattern)) {
        $subQuery = "(SELECT syncBy.gibbonRollGroupID FROM gibbonRollGroup AS syncBy WHERE REPLACE(REPLACE(REPLACE(REPLACE(:pattern, '[courseShortName]', gibbonCourse.nameShort), '[classShortName]', gibbonCourseClass.nameShort), '[yearGroupShortName]', gibbonYearGroup.nameShort), '[rollGroupShortName]', nameShort) LIKE CONCAT('%', syncBy.nameShort) AND syncBy.gibbonSchoolYearID=:gibbonSchoolYearID LIMIT 1)";

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $gibbonYearGroupID, 'pattern' => $pattern);
        $sql = "SELECT gibbonCourse.name as courseName, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as classShortName, gibbonYearGroup.nameShort as yearGroupShortName, $subQuery as syncTo
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=:gibbonYearGroupID)
                WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClass.gibbonCourseClassID
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort
                ";
        $result = $pdo->executeQuery($data, $sql);
    } else {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "SELECT gibbonCourse.name as courseName, gibbonCourseClassMap.gibbonRollGroupID as syncTo,  gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as classShortName, gibbonYearGroup.nameShort as yearGroupShortName
                FROM gibbonCourseClass
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                LEFT JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassMap.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonYearGroup.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClass.gibbonCourseClassID
                ORDER BY gibbonCourse.name, gibbonCourseClass.nameShort
                ";
        $result = $pdo->executeQuery($data, $sql);
    }

    if ($result->rowCount() > 0) {
        $coursesGrouped = $result->fetchAll(PDO::FETCH_GROUP);

        foreach ($coursesGrouped as $courseName => $classes) {
            $form->addRow()->addHeading($courseName);
            $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

            $course = current($classes);
            $checkallSelected = array_filter($classes, function($item) {
                return !empty($item['syncTo']);
            });

            $yearGroupSelector = str_replace(' ', '', strtolower($courseName));
            $header = $table->addHeaderRow();
                //$header->addContent(__('Enrol'));
                $header->addCheckbox('checkall'.$course['gibbonCourseID'])->checked(!empty($checkallSelected));
                $header->addContent(__('Class'));
                $header->addContent('');
                $header->addContent(__('Roll Group'));

            foreach ($classes as $class) {
                $row = $table->addRow();
                    $row->addCheckbox('syncEnabled['.$class['gibbonCourseClassID'].']')
                        ->checked(!empty($class['syncTo']))
                        ->setClass($course['gibbonCourseID'])
                        ->description('&nbsp;&nbsp;');
                    $row->addLabel('syncEnabled['.$class['gibbonCourseClassID'].']', $class['courseNameShort'].'.'.$class['classShortName'])->setTitle($class['courseNameShort'])->setClass('mediumWidth');
                    $row->addContent( (empty($class['syncTo'])? '<em>'.__('No match found').'</em>' : '') )->setClass('shortWidth right');
                    $row->addSelectRollGroup('syncTo['.$class['gibbonCourseClassID'].']', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($class['syncTo'])->setClass('mediumWidth');
            }

            echo '<script type="text/javascript">';
            echo '$(function () {';
                echo "$('#checkall".$course['gibbonCourseID']."').click(function () {";
                echo "$('.".$course['gibbonCourseID']."').find(':checkbox').attr('checked', this.checked);";
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


}
