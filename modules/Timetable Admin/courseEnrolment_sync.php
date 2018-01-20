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

    echo '<h3>';
    echo __('Settings');
    echo '</h3>';

    $form = Form::create('settings', $_SESSION[$guid]['absoluteURL'].'/modules/Timetable Admin/courseEnrolment_sync_settingsProcess.php');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'Timetable Admin', 'autoEnrolCourses', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    // Grab all mapped classes grouped by year group
    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonCourseClassMap.*, gibbonYearGroup.gibbonYearGroupID, gibbonRollGroup.name as gibbonRollGroupName, gibbonYearGroup.name as gibbonYearGroupName, COUNT(DISTINCT gibbonCourseClassMap.gibbonCourseClassID) as classCount, GROUP_CONCAT(DISTINCT gibbonRollGroup.nameShort ORDER BY gibbonRollGroup.nameShort SEPARATOR ', ') as rollGroupList, GROUP_CONCAT(DISTINCT gibbonRollGroup.gibbonRollGroupID ORDER BY gibbonRollGroup.gibbonRollGroupID SEPARATOR ',') as gibbonRollGroupIDList
            FROM gibbonCourseClassMap
            JOIN gibbonRollGroup ON (gibbonCourseClassMap.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
            JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID)
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)
            GROUP BY gibbonYearGroup.gibbonYearGroupID
            ORDER BY gibbonYearGroup.sequenceNumber";

    $results = $pdo->executeQuery($data, $sql);

    $classMaps = $results->fetchAll();

    $classMapsAllYearGroups = array_column($classMaps, 'gibbonYearGroupID');
    $classMapsAllYearGroups = implode(',', $classMapsAllYearGroups);

    echo '<h3>';
    echo __('Map Classes');
    echo '</h3>';

    echo '<p>';
    echo __('Syncing enrolment lets you enrol students into courses by mapping them to a Roll Group and Year Group within the school. If auto-enrol is turned on, new students accepted through the application form and student enrolment process will be enroled in courses automatically.');
    echo '<p>';

    echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_sync_add.php'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>&nbsp; | ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_sync_run.php&gibbonYearGroupIDList=".$classMapsAllYearGroups."'>".__('Sync All')."<img style='margin-left: 5px;width:22px;height:22px;' title='".__('Sync All')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/refresh.png'/></a>";
    echo '</div>';

    if (empty($classMaps)) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
        echo '<th>';
                echo __('Year Group');
            echo '</th>';
            echo '<th>';
                echo __('Roll Groups');
            echo '</th>';
            echo '<th>';
                echo __('Classes');
            echo '</th>';
            echo '<th style="width: 120px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        foreach ($classMaps as $mapping) {
            echo '<tr>';
                echo '<td>'.$mapping['gibbonYearGroupName'].'</td>';
                echo '<td>'.$mapping['rollGroupList'].'</td>';
                echo '<td>'.$mapping['classCount'].'</td>';
                echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/courseEnrolment_sync_edit.php&gibbonYearGroupID=".$mapping['gibbonYearGroupID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL']."/fullscreen.php?q=/modules/".$_SESSION[$guid]['module']."/courseEnrolment_sync_delete.php&gibbonYearGroupID=".$mapping['gibbonYearGroupID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> &nbsp;";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/courseEnrolment_sync_run.php&gibbonYearGroupIDList=".$mapping['gibbonYearGroupID']."'><img title='".__('Sync Now')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/refresh.png' style='width:22px;height:22px;'/></a>";
                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
