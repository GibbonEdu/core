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
use Gibbon\Forms\DatabaseFormFactory;

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/conceptExplorer.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Concept Explorer').'</div>';
    echo '</div>';

    //Get all concepts in current year and convert to ordered array
    $tagsAll = getTagList($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);

    //Deal with paramaters
    $tags = array();
    if (isset($_GET['tags'])) {
        $tags = $_GET['tags'];
    }
    else if (isset($_GET['tag'])) {
        $tags[0] = $_GET['tag'];
    }
    $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';

    //Display concept cloud
    if (count($tags) == 0) {
        echo '<h2>';
        echo __($guid, 'Concept Cloud');
        echo '</h2>';
        echo getTagCloud($guid, $connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
    }

    //Allow tag selection
    echo '<h2>';
    echo __($guid, 'Choose Concept');
    echo '</h2>';

    $form = Form::create('conceptExplorer', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/conceptExplorer.php');

    $row = $form->addRow();
        $row->addLabel('tags', __('Concepts & Keywords'));
        $row->addSelect('tags')->fromArray(array_column($tagsAll, 1))->selectMultiple()->isRequired()->selected($tags);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    if (count($tags) > 0) {
        //Set up for edit access
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units.php', $connection2);
        $departments = array();
        if ($highestAction == 'Unit Planner_learningAreas') {
            $departmentCount = 1 ;
            try {
                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlSelect = "SELECT gibbonDepartment.gibbonDepartmentID FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonDepartment.name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { echo $e->getMessage(); }
            while ($rowSelect = $resultSelect->fetch()) {
                $departments[$departmentCount] = $rowSelect['gibbonDepartmentID'];
                $departmentCount ++;
            }
        }

        //Search for units with these tags
        try {
            $data = array() ;

            //Tag filter
            $sqlWhere = ' AND (';
            $count = 0;
            foreach ($tags as $tag) {
                $data["tag$count"] = "%,$tag,%";
                $sqlWhere .= "concat(',',tags,',') LIKE :"."tag$count"." OR ";
                $count ++;
            }
            if ($sqlWhere == ' AND (')
                $sqlWhere = '';
            else
                $sqlWhere = substr($sqlWhere, 0, -3).')';

            //Year group Filters
            if ($gibbonYearGroupID != '') {
                $data['gibbonYearGroupID'] = '%'.$gibbonYearGroupID.'%';
                $sqlWhere .= ' AND gibbonYearGroupIDList LIKE :gibbonYearGroupID ';
            }


            $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
            $sql = "SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment, tags, gibbonCourse.name AS course, gibbonDepartmentID, gibbonCourse.gibbonCourseID, gibbonSchoolYearID FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonUnit.map='Y' AND gibbonCourse.map='Y' $sqlWhere ORDER BY gibbonUnit.name";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }


        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        }
        else {
            echo '<h2 class=\'bigTop\'>';
            echo __($guid, 'Results');
            echo '</h2>';

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th style=\'width: 23%\'>';
            echo __($guid, 'Unit');
            echo "<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Course').'</span>';
            echo '</th>';
            echo '<th style=\'width: 37%\'>';
            echo __($guid, 'Description');
            echo '</th>';
            echo "<th style=\'width: 30%\'>";
            echo __($guid, 'Concepts & Keywords');
            echo '</th>';
            echo "<th style='width: 10%'>";
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';


            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                //Can this unit be edited?
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

                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $row['name'].'<br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".$row['course'].'</span>';
                echo '</td>';
                echo '<td>';
                echo $row['description'].'<br/>';
                if ($row['attachment'] != '') {
                    echo "<br/><br/><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['attachment']."'>".__($guid, 'Download Unit Outline').'</a></li>';
                }
                echo '</td>';
                echo '<td>';
                $tagsUnit = explode(',', $row['tags']);
                $tagsOutput = '' ;
                foreach ($tagsUnit as $tag) {
                    $style = '';
                    foreach ($tags AS $tagInner) {
                        if ($tagInner == $tag) {
                            $style = 'style=\'color: #000; font-weight: bold\'';
                        }
                    }
                    $tagsOutput .= "<a $style href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/conceptExplorer.php&tag=$tag'>".$tag.'</a>, ';
                }
                if ($tagsOutput != '')
                    $tagsOutput = substr($tagsOutput, 0, -2);
                echo $tagsOutput;
                echo '</td>';
                echo '<td>';
                    if ($canEdit) {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonCourseID=".$row['gibbonCourseID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_dump.php&gibbonCourseID=".$row['gibbonCourseID']."&gibbonUnitID=".$row['gibbonUnitID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."&sidebar=false'><img title='".__($guid, 'Export')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                    }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
