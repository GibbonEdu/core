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

if (isActionAccessible($guid, $connection2, '/modules/Planner/curriculum_viewByStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Curriculum').'</div>';
    echo '</div>';

    $gibbonPersonIDStudent = (isset($_POST['gibbonPersonIDStudent']))? $_POST['gibbonPersonIDStudent'] : null;

    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonFamilyAdult
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date)
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('The selected record does not exist, or you do not have access to it.');
        echo '</div>';
        return;
    } else {
        echo '<h2>';
        echo __('Choose');
        echo '</h2>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/curriculum_viewByStudent.php');
        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $children = array();
        while ($row = $result->fetch()) {
            $children[$row['gibbonPersonID']] = formatName('', ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDStudent', __('Search For'));
            $row->addSelect('gibbonPersonIDStudent')->fromArray($children)->selected($gibbonPersonIDStudent);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();

        if (!empty($gibbonPersonIDStudent)) {
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                $sql = "SELECT gibbonUnit.gibbonUnitID, gibbonCourseClass.gibbonCourseClassID FROM gibbonUnit
                        JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonUnitID=gibbonUnit.gibbonUnitID)
                        JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                        JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
                        WHERE gibbonUnitClass.running='Y'
                        AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                        AND gibbonCourseClassPerson.role NOT LIKE '%- Left'
                        AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                        AND gibbonFamilyAdult.childDataAccess='Y'
                        GROUP BY gibbonUnit.gibbonUnitID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() == 0) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo '<table class="fullWidth colorOddEven" cellspacing="0">';

                echo '<tr class="head">';
                    echo '<th>';
                        echo __('Course');
                    echo '</th>';
                    echo '<th>';
                        echo __('Name');
                    echo '</th>';
                    echo '<th>';
                        echo __('Enrolment Group');
                    echo '</th>';
                    echo '<th>';
                        echo __('Priority');
                    echo '</th>';
                    echo '<th>';
                        echo __('Tags');
                    echo '</th>';
                    echo '<th style="width: 80px;">';
                        echo __('Actions');
                    echo '</th>';
                echo '</tr>';

                while ($unit = $result->fetch()) {
                    echo '<tr>';
                        echo '<td>'.$unit['nameShort'].'</td>';
                        echo '<td>'.$unit['name'].'</td>';
                        echo '<td>'.$unit['enrolmentGroup'].'</td>';
                        echo '<td>'.$unit['timetablePriority'].'</td>';
                        echo '<td>'.$unit['tags'].'</td>';
                        echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/meta_manage_addEdit.php&gibbonSchoolYearID=".$gibbonSchoolYearID."&courseSelectionunitID=".$unit['courseSelectionunitID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> &nbsp;";

                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL']."/fullscreen.php?q=/modules/".$_SESSION[$guid]['module']."/meta_manage_delete.php&courseSelectionunitID=".$unit['courseSelectionunitID']."&width=650&height=200'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                        echo '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            }
        }
    }




}
?>
