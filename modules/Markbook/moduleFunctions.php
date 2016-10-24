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

function classChooser($guid, $pdo, $gibbonCourseClassID)
{
    //Set timezone from session variable
    date_default_timezone_set($_SESSION[$guid]['timezone']);

    $enableColumnWeighting = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
    $enableGroupByTerm = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableGroupByTerm');
    $enableRawAttainment = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableRawAttainment');

    $output = '';

    $output .= "<h3 style='margin-top: 0px'>";
    $output .= __($guid, 'Choose Class');
    $output .= '</h3>';
    $output .= "<table cellspacing='0' class='noIntBorder' style='width: 100%; margin: 10px 0 10px 0'>";
    $output .= '<tr>';
    $output .= "<td style='vertical-align: top'>";

    $output .= '</td>';
    $output .= "<td style='vertical-align: top; text-align: right'>";
    $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
    $output .= "<input name='q' id='q' type='hidden' value='/modules/Markbook/markbook_view.php'>";

    if ($enableGroupByTerm == 'Y' ) {

        $output .= "<span>".__($guid, 'Term').": </span>";
        $output .= "<select name='gibbonSchoolYearTermID' id='gibbonSchoolYearTermID' style='width:140px; float: none;'>";
        $output .= "<option value='-1'>".__($guid, 'All Terms')."</option>";
        try {
            $data=array("gibbonSchoolYearID"=>$_SESSION[$guid]['gibbonSchoolYearID']);
            $sql="SELECT gibbonSchoolYearTermID, name, UNIX_TIMESTAMP(firstDay) AS firstTime, UNIX_TIMESTAMP(lastDay) AS lastTime FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
            $resultTerms=$pdo->executeQuery($data, $sql);
        }
        catch(PDOException $e) { }

        $selectTerm = (isset($_SESSION[$guid]['markbookTerm']))? $_SESSION[$guid]['markbookTerm'] : 0;
        $selectTerm = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : $selectTerm;
        $selectTermName = __($guid, 'All Terms');

        while ($rowTerm = $resultTerms->fetch()) {

            $selected = '';
            if ($selectTerm != 0) {
                if ($selectTerm == $rowTerm['gibbonSchoolYearTermID']) {
                    $selected = 'selected';
                    $selectTermName = $rowTerm['name'];
                }
            } else if (time() >= $rowTerm['firstTime'] && time() < $rowTerm['lastTime']) {
                $selected = 'selected';
                $selectTerm = $rowTerm['gibbonSchoolYearTermID'];
                $selectTermName = $rowTerm['name'];
            }

            $output .= "<option $selected value='".$rowTerm['gibbonSchoolYearTermID']."'>".htmlPrep($rowTerm['name']).'</option>';
        }
        $output .= '</select>';

        if ($selectTerm != 0) {
            $_SESSION[$guid]['markbookTerm'] = $selectTerm;
            $_SESSION[$guid]['markbookTermName'] = $selectTermName;
        }
    } else {
        $_SESSION[$guid]['markbookTerm'] = 0;
        $_SESSION[$guid]['markbookTermName'] = __($guid, 'All Columns');
    }

    $selectFilter = (isset($_SESSION[$guid]['markbookFilter']))? $_SESSION[$guid]['markbookFilter'] : '';
    $selectFilter = (isset($_GET['markbookFilter']))? $_GET['markbookFilter'] : $selectFilter;

    $_SESSION[$guid]['markbookFilter'] = $selectFilter;

    $output .= "&nbsp;&nbsp;&nbsp;<span>".__($guid, 'Show').": </span>";
    $output .= "<select name='markbookFilter' id='markbookFilter' style='width:140px; float: none;'>";
    $output .= "<option value='' ".(($selectFilter === '')? 'selected' : '').">".__($guid, 'All Columns')."</option>";

    if ($enableColumnWeighting == 'Y' ) {
        $output .= "<option value='averages' ".(($selectFilter == 'averages')? 'selected' : '')." >".__($guid, 'Overall Grades')."</option>";
    }

    if ($enableRawAttainment == 'Y' ) {
        $output .= "<option value='raw' ".(($selectFilter == 'raw')? 'selected' : '')." >".__($guid, 'Raw Marks')."</option>";
    }

    $output .= "<option value='marked' ".(($selectFilter == 'marked')? 'selected' : '')." >".__($guid, 'Marked')."</option>";
    $output .= "<option value='unmarked' ".(($selectFilter == 'unmarked')? 'selected' : '')." >".__($guid, 'Unmarked')."</option>";

    // $output .= "<option value='week' ".(($selectFilter == 'week')? 'selected' : '').">".__($guid, 'This Week')."</option>";
    // $output .= "<option value='month' ".(($selectFilter == 'month')? 'selected' : '').">".__($guid, 'This Month')."</option>";
    $output .= '</select>';


    try {
        $dataRollOrder = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID'=>$_SESSION[$guid]['gibbonSchoolYearID'] );
        $sqlRollOrder = "SELECT COUNT(DISTINCT rollOrder) FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID";
        $resultSelect = $pdo->executeQuery($dataRollOrder, $sqlRollOrder);
    } catch (PDOException $e) {}

    // More than one rollOrder means there are orders assigned to each student, otherwise skip the sort filter
    if ( $resultSelect->rowCount() > 0) {
        if ($resultSelect->fetchColumn(0) > 0) {

            $selectOrderBy = (isset($_SESSION[$guid]['markbookOrderBy']))? $_SESSION[$guid]['markbookOrderBy'] : 'surname';
            $selectOrderBy = (isset($_GET['markbookOrderBy']))? $_GET['markbookOrderBy'] : $selectOrderBy;

            $output .= "&nbsp;&nbsp;&nbsp;<span>".__($guid, 'Sort By').": </span>";
            $output .= "<select name='markbookOrderBy' id='markbookOrderBy' style='width:140px; float: none;'>";
            $output .= "<option value='rollOrder' ".(($selectOrderBy == 'rollOrder')? 'selected' : '')." >".__($guid, 'Roll Order')."</option>";
            $output .= "<option value='surname' ".(($selectOrderBy == 'surname')? 'selected' : '')." >".__($guid, 'Surname')."</option>";
            $output .= "<option value='preferredName' ".(($selectOrderBy == 'preferredName')? 'selected' : '')." >".__($guid, 'Preferred Name')."</option>";
            $output .= '</select>';

            $_SESSION[$guid]['markbookOrderBy'] = $selectOrderBy;
        }
    }


    $output .= "&nbsp;&nbsp;&nbsp;<span>".__($guid, 'Class').": </span>";
    $output .= "<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:193px; float: none;'>";
    $output .= "<option value=''></option>";
    try {
        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class';
        $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);
    } catch (PDOException $e) {
    }
    $selectCount = 0;

    $output .= "<optgroup label='--".__($guid, 'My Classes')."--'>";
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID) {
            $selected = 'selected';
            ++$selectCount;
        }
        $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
    }
    $output .= '</optgroup>';
    try {
        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
        $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);
    } catch (PDOException $e) {
    }
    $output .= "<optgroup label='--".__($guid, 'All Classes')."--'>";
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
            $selected = 'selected';
            ++$selectCount;
        }
        $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
    }
    $output .= '</optgroup>';
    $output .= '</select>';
    $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
    $output .= '</form>';
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';

    return $output;
}

function isDepartmentCoordinator( $pdo, $gibbonPersonID ) {
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID );
        $sql = "SELECT count(*) FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')";
        $result = $pdo->executeQuery($data, $sql);

    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    return ($result->rowCount() > 0)? ($result->fetchColumn() >= 1) : false;
}

function getAnyTaughtClass( $pdo, $gibbonPersonID, $gibbonSchoolYearID ) {
    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class LIMIT 1';
        $result = $pdo->executeQuery($data, $sql);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    return ($result->rowCount() > 0)? $result->fetch() : NULL;
}

function getClass( $pdo, $gibbonPersonID, $gibbonCourseClassID, $highestAction = '' ) {
    try {
        if ($highestAction == 'View Markbook_allClassesAllData') {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
        } else {
            $data = array( 'gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonYearGroupIDList, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
        }
        $result = $pdo->executeQuery($data, $sql);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    return ($result->rowCount() > 0)? $result->fetch() : NULL;
}

function getTeacherList( $pdo, $gibbonCourseClassID ) {
    try {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
        $result = $pdo->executeQuery($data, $sql);

    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $teacherList = array();
    if ($result->rowCount() > 0) {
        foreach ($result->fetchAll() as $teacher) {
            $teacherList[ $teacher['gibbonPersonID'] ] = formatName($teacher['title'], $teacher['preferredName'], $teacher['surname'], 'Staff', false, true);
        }
    }

    return $teacherList;
}

function getAlertStyle( $alert, $concern ) {

    if ($concern == 'Y') {
        return "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG'].";margin:0 auto;'";
    } else if ($concern == 'P') {
        return "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC;margin:0 auto;'";
    } else {
        return '';
    }
}
