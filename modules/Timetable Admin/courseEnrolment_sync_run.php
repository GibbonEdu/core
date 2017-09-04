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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/courseEnrolment_sync.php'>".__($guid, 'Sync Course Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Sync Now').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonYearGroupID = (isset($_GET['gibbonYearGroupID']))? $_GET['gibbonYearGroupID'] : null;

    if (is_array($gibbonYearGroupID)) {
        $gibbonYearGroupID = implode(',', $gibbonYearGroupID);
    }

    if (empty($gibbonYearGroupID)) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</div>';
        return;
    }

    $form = Form::create('courseEnrolmentSyncRun', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync_runProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $renderer = $form->getRenderer();
    $renderer->setWrapper('form', 'div');
    $renderer->setWrapper('row', 'div');
    $renderer->setWrapper('cell', 'div');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);

    $row = $form->addRow()->addContent('<h4>'.__('Options').'</h4>');
    $row = $form->addRow();
    $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

    $row = $table->addRow();
        $row->addLabel('includeStudents', __('Include Students'));
        $row->addCheckbox('includeStudents')->checked(true);

    $row = $table->addRow();
        $row->addLabel('includeTeachers', __('Include Teachers'));
        $row->addCheckbox('includeTeachers')->checked(true);


    if ($gibbonYearGroupID == 'all') {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonCourseClassMap.*
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                WHERE  gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    } else {
        // Pull up the class mapping for this year group
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "SELECT gibbonCourseClassMap.*
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                WHERE FIND_IN_SET(gibbonCourseClassMap.gibbonYearGroupID, :gibbonYearGroupID)
                AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    }

    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() == 0) {

    } else {
        echo '<pre>';
        print_r($result->fetchAll());
        echo '</pre>';
    }


    $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');

    $row = $table->addRow();
        $row->addSubmit(__('Proceed'));

    echo $form->getOutput();
}
