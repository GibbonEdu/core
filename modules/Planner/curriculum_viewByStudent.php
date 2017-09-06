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

$highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);

if (isActionAccessible($guid, $connection2, '/modules/Planner/curriculum_viewByStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else if ($highestAction == false) {
    echo "<div class='error'>";
    echo __($guid, 'The highest grouped action cannot be determined.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Learning').'</div>';
    echo '</div>';

    $search = (isset($_GET['search']))? $_GET['search'] : null;
    $gibbonPersonID = (isset($_POST['gibbonPersonID']))? $_POST['gibbonPersonID'] : $search;

    if ($highestAction == 'Student Learning_myChildren') {
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
    } else if ($highestAction == 'Student Learning_all') {
        try {
            $data = array('date' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonRollGroup.nameShort as rollGroupName
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
                WHERE status='Full'
                AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date)
                AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
                ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
    }

    if (!$result || $result->rowCount() == 0) {
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

        $students = array();
        while ($row = $result->fetch()) {
            $students[$row['gibbonPersonID']] = formatName('', ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true);

            if (!empty($row['rollGroupName'])) {
                $students[$row['gibbonPersonID']] .= ' ('.$row['rollGroupName'].')';
            }
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Search For'));
            $row->addSelect('gibbonPersonID')->fromArray($students)->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();

        if (!empty($gibbonPersonID)) {
            if ($highestAction == 'Student Learning_myChildren') {
                try {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDStudent' => $gibbonPersonID);
                    $sql = "SELECT gibbonCourse.name, gibbonUnit.*, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonSchoolYearID
                            FROM gibbonUnit
                            JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonUnitID=gibbonUnit.gibbonUnitID)
                            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonUnit.gibbonCourseID)
                            JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
                            WHERE gibbonUnit.active='Y' AND gibbonUnitClass.running='Y'
                            AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                            AND gibbonCourseClassPerson.role NOT LIKE '%- Left'
                            AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                            AND gibbonFamilyAdult.childDataAccess='Y'
                            GROUP BY gibbonUnit.gibbonUnitID
                            ORDER BY gibbonCourse.orderBy, gibbonCourse.name, gibbonUnit.ordering, gibbonUnit.name
                            ";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
            } else if ($highestAction == 'Student Learning_all') {
                try {
                    $data = array('gibbonPersonIDStudent' => $gibbonPersonID);
                    $sql = "SELECT gibbonCourse.name, gibbonUnit.*, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonSchoolYearID
                            FROM gibbonUnit
                            JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonUnitID=gibbonUnit.gibbonUnitID)
                            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonUnit.gibbonCourseID)
                            JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                            AND gibbonUnitClass.running='Y'
                            AND gibbonUnit.active='Y'
                            GROUP BY gibbonUnit.gibbonUnitID
                            ORDER BY gibbonCourse.orderBy, gibbonCourse.name, gibbonUnit.ordering, gibbonUnit.name
                            ";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
            }

            if (!$result || $result->rowCount() == 0) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo '<br/>';
                echo '<p>';
                echo __('Here you can see an overview of units for a student. This currently includes units that have been added to Gibbon and is not always a comprehensive list. Click view to see more information about the unit outline.');
                echo '</p>';

                $unitsByCourse = $result->fetchAll(\PDO::FETCH_GROUP);

                foreach ($unitsByCourse as $courseName => $units) {
                    echo '<h5>';
                    echo $courseName;
                    echo '</h5>';

                    echo '<table class="fullWidth colorOddEven" cellspacing="0">';

                    foreach ($units as $unit) {
                        echo '<tr>';
                            echo '<td style="width: 27%;">'.$unit['name'].'</td>';
                            echo '<td style="width: 65%;">'.$unit['description'].'</td>';
                            echo '<td style="width: 8%;">';
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner_unitOverview.php&viewBy=unit&subView=&gibbonUnitID='.$unit['gibbonUnitID'].'&gibbonCourseClassID='.$unit['gibbonCourseClassID']."&search=".$gibbonPersonID."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                            echo '</td>';
                        echo '</tr>';
                    }

                    echo '</table>';
                    echo '<br/>';
                }
            }
        }
    }




}
?>
