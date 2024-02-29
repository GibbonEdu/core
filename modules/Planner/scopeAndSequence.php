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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Scope And Sequence'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/scopeAndSequence.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    echo '<h2>';
    echo __('Choose Course');
    echo '</h2>';

    $gibbonCourseIDs = array();
    if (isset($_POST['gibbonCourseID'])) {
        $gibbonCourseIDs = $_POST['gibbonCourseID'] ?? '';
    }
    $gibbonYearGroupID = '';
    if (isset($_POST['gibbonYearGroupID'])) {
        $gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
    }

    $form = Form::create('action', $session->get('absoluteURL')."/index.php?q=/modules/".$session->get('module')."/scopeAndSequence.php");

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $options = array();
    
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' AND map='Y' ORDER BY department, gibbonCourse.nameShort";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    while ($row = $result->fetch()) {
        $options[$row["department"]][$row["gibbonCourseID"]] = $row["name"];
    }

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromArray($options)->selectMultiple()->selected($gibbonCourseIDs);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if (count($gibbonCourseIDs) > 0) {
        //Set up for edit access
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units.php', $connection2);
        $departments = array();
        if ($highestAction == 'Unit Planner_learningAreas') {
            $departmentCount = 1 ;
            try {
                $dataSelect = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlSelect = "SELECT gibbonDepartment.gibbonDepartmentID FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonDepartment.name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { }
            while ($rowSelect = $resultSelect->fetch()) {
                $departments[$departmentCount] = $rowSelect['gibbonDepartmentID'];
                $departmentCount ++;
            }
        }

        //Set up stats variables
        $countCourses = 0 ;
        $countCoursesNoUnits = 0 ;
        $coursesNoUnits = '';
        $countUnits = 0;
        $countUnitsNoKeywords = 0 ;
        $unitsNoKeywords = '';

        //Cycle through courses
        foreach ($gibbonCourseIDs as $gibbonCourseID) {
            //Check course exists
            try {
                $data = array();
                $sqlWhere = '';
                if ($gibbonYearGroupID != '') {
                    $data['gibbonYearGroupID'] = '%'.$gibbonYearGroupID.'%';
                    $sqlWhere = ' AND gibbonYearGroupIDList LIKE :gibbonYearGroupID ';
                }
                $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
                $data['gibbonCourseID'] = $gibbonCourseID;
                $sql = "SELECT gibbonCourse.*, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' AND gibbonCourseID=:gibbonCourseID AND map='Y' $sqlWhere ORDER BY department, nameShort";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() == 1) {
                $countCourses ++ ;

                $row = $result->fetch();

                //Can this course's units be edited?
                $canEdit = false ;
                if ($highestAction == 'Unit Planner_all') {
                    $canEdit = true ;
                }
                else if ($highestAction == 'Unit Planner_learningAreas') {
                    foreach ($departments AS $department) {
                        if ($department == $row['gibbonDepartmentID']) {
                            $canEdit = true ;
                        }
                    }
                }

                echo '<h2 class=\'bigTop\'>';
                echo $row['name'].' - '.$row['nameShort'];
                echo '</h2>';

                
                    $dataUnit = array('gibbonCourseID' => $gibbonCourseID);
                    $sqlUnit = 'SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment, tags FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnit.gibbonCourseID=:gibbonCourseID AND active=\'Y\' AND gibbonCourse.map=\'Y\' AND gibbonUnit.map=\'Y\' ORDER BY ordering, name';
                    $resultUnit = $connection2->prepare($sqlUnit);
                    $resultUnit->execute($dataUnit);

                if ($resultUnit->rowCount() < 1) {
                    echo $page->getBlankSlate();
                    $countCoursesNoUnits ++;
                    $coursesNoUnits .= $row['nameShort'].', ';
                }
                else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th style=\'width: 15%\'>';
                    echo __('Unit');
                    echo '</th>';
                    echo '<th style=\'width: 45%\'>';
                    echo __('Description');
                    echo '</th>';
                    echo "<th style=\'width: 30%\'>";
                    echo __('Concepts & Keywords');
                    echo '</th>';
                    echo "<th style='width: 10%'>";
                    echo __('Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowUnit = $resultUnit->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;
                        $countUnits ++;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $rowUnit['name'].'<br/>';
                        echo '</td>';
                        echo '<td>';
                        echo $rowUnit['description'].'<br/>';
                        if ($rowUnit['attachment'] != '') {
                            echo "<br/><br/><a href='".$session->get('absoluteURL').'/'.$rowUnit['attachment']."'>".__('Download Unit Outline').'</a></li>';
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($rowUnit['tags'] == '') {
                            $countUnitsNoKeywords ++;
                            $unitsNoKeywords .= $row['nameShort'].' ('.$rowUnit['name'].'), ';
                        }
                        else {
                            $tags = explode(',', $rowUnit['tags']);
                            $tagsOutput = '' ;
                            foreach ($tags as $tag) {
                                $tagsOutput .= "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/conceptExplorer.php&tag=$tag'>".$tag.'</a>, ';
                            }
                            if ($tagsOutput != '')
                                $tagsOutput = substr($tagsOutput, 0, -2);
                            echo $tagsOutput;
                        }
                        echo '</td>';
                        echo '<td>';
                            if ($canEdit) {
                                echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/units_edit.php&gibbonUnitID=".$rowUnit['gibbonUnitID']."&gibbonCourseID=".$row['gibbonCourseID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                            }
                            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/units_dump.php&gibbonCourseID=".$row['gibbonCourseID']."&gibbonUnitID=".$rowUnit['gibbonUnitID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__('View')."' src='./themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }
        }

        echo "<div class='success'>";
            echo '<b>'.__('Total Courses').'</b>: '.$countCourses.'<br/>';
            echo '<b>'.__('Courses Without Units').'</b>: '.$countCoursesNoUnits.'<br/>';
            if ($coursesNoUnits != '') {
                print '<i>'.substr($coursesNoUnits, 0, -2).'</i><br/>';
            }
            echo '<b>'.__('Total Units').'</b>: '.$countUnits.'<br/>';
            echo '<b>'.__('Units Without Concepts & Keywords').'</b>: '.$countUnitsNoKeywords.'<br/>';
            if ($unitsNoKeywords != '') {
                print '<i>'.substr($unitsNoKeywords, 0, -2).'</i><br/>';
            }
        echo "</div>";
    }
}
