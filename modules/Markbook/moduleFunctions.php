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

function classChooser($guid, $connection2, $gibbonCourseClassID)
{
    //Set timezone from session variable
    date_default_timezone_set($_SESSION[$guid]['timezone']);

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
    
    $output .= "<span>".__($guid, 'Term').": </span>";
    $output .= "<select name='gibbonSchoolYearTermID' id='gibbonSchoolYearTermID' style='width:193px; float: none;'>";
    $output .= "<option value=''>".__($guid, 'All Terms')."</option>";
    try {
        $data=array("gibbonSchoolYearID"=>$_SESSION[$guid]['gibbonSchoolYearID']);
        $sql="SELECT gibbonSchoolYearTermID, name, UNIX_TIMESTAMP(firstDay) AS firstTime, UNIX_TIMESTAMP(lastDay) AS lastTime FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
        $resultTerms=$connection2->prepare($sql);
        $resultTerms->execute($data);
    }
    catch(PDOException $e) { }

    $selectTerm = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : 0;

    while ($rowTerm = $resultTerms->fetch()) {

        $selected = ( time() >= $rowTerm['firstTime'] && time() < $rowTerm['lastTime'] )? 'selected' : '';
        $selected = ( !empty($selectTerm) && $selectTerm == $rowTerm['gibbonSchoolYearTermID'])? 'selected' : '';
        $output .= "<option $selected value='".$rowTerm['gibbonSchoolYearTermID']."'>".htmlPrep($rowTerm['name']).'</option>';
    }
    $output .= '</select>';

    $selectFilter = (isset($_GET['columnFilter']))? $_GET['columnFilter'] : 0;

    $output .= "&nbsp;&nbsp;&nbsp;<span>".__($guid, 'Show').": </span>";
    $output .= "<select name='columnFilter' id='columnFilter' style='width:193px; float: none;'>";
    $output .= "<option value=''>".__($guid, 'All Columns')."</option>";
    $output .= "<option value='marked' ".(($selectFilter == 'marked')? 'selected' : '')." >".__($guid, 'Marked')."</option>";
    $output .= "<option value='unmarked' ".(($selectFilter == 'unmarked')? 'selected' : '')." >".__($guid, 'Unmarked')."</option>";
    $output .= "<option value='week' ".(($selectFilter == 'week')? 'selected' : '').">".__($guid, 'This Week')."</option>";
    $output .= "<option value='month' ".(($selectFilter == 'month')? 'selected' : '').">".__($guid, 'This Month')."</option>";
    $output .= '</select>';


    $output .= "&nbsp;&nbsp;&nbsp;<span>".__($guid, 'Class').": </span>";
    $output .= "<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:193px; float: none;'>";
    $output .= "<option value=''></option>";
    try {
        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class';
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
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
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
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
