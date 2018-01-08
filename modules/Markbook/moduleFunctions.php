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

function sidebarExtra($guid, $pdo, $gibbonPersonID, $gibbonCourseClassID = '', $basePage = '')
{
    $output = '';

    if (empty($basePage)) $basePage = 'markbook_view.php';

    //Show class picker in sidebar
    $output .= '<h2>';
    $output .= __($guid, 'Choose A Class');
    $output .= '</h2>';

    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('q', '/modules/Markbook/'.$basePage);
    
    $row = $form->addRow();
        $row->addSelectClass('gibbonCourseClassID', $_SESSION[$guid]['gibbonSchoolYearID'], $gibbonPersonID)
            ->selected($gibbonCourseClassID)
            ->placeholder()
            ->setClass('fullWidth');
        $row->addSubmit(__('Go'));
    
    $output .= $form->getOutput();

    return $output;
}

function classChooser($guid, $pdo, $gibbonCourseClassID)
{
    $enableColumnWeighting = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
    $enableGroupByTerm = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableGroupByTerm');
    $enableRawAttainment = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableRawAttainment');

    $output = '';

    $output .= "<h3 style='margin-top: 0px'>";
    $output .= __($guid, 'Choose Class');
    $output .= '</h3>';

    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/markbook_view.php');

    $col = $form->addRow()->addColumn()->addClass('inline right');

    // TERM
    if ($enableGroupByTerm == 'Y' ) {
        $selectTerm = (isset($_SESSION[$guid]['markbookTerm']))? $_SESSION[$guid]['markbookTerm'] : 0;
        $selectTerm = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : $selectTerm;

        $data = array("gibbonSchoolYearID"=>$_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonSchoolYearTermID as value, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $result = $pdo->executeQuery($data, $sql);
        $terms = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        $col->addContent(__('Term').':');
        $col->addSelect('gibbonSchoolYearTermID')
            ->fromArray(array('-1' => __('All Terms')))
            ->fromArray($terms)
            ->selected($selectTerm)
            ->setClass('shortWidth');

        $_SESSION[$guid]['markbookTermName'] = isset($terms[$selectTerm])? $terms[$selectTerm] : $selectTerm;
        $_SESSION[$guid]['markbookTerm'] = $selectTerm;
    } else {
        $_SESSION[$guid]['markbookTerm'] = 0;
        $_SESSION[$guid]['markbookTermName'] = __($guid, 'All Columns');
    }

    // SORT BY
    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID'=>$_SESSION[$guid]['gibbonSchoolYearID'] );
    $sql = "SELECT COUNT(DISTINCT rollOrder) FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID";
    $result = $pdo->executeQuery($data, $sql);
    $rollOrderCount = ($result->rowCount() > 0)? $result->fetchColumn(0) : 0;
    if ($rollOrderCount > 0) {
        $selectOrderBy = (isset($_SESSION[$guid]['markbookOrderBy']))? $_SESSION[$guid]['markbookOrderBy'] : 'surname';
        $selectOrderBy = (isset($_GET['markbookOrderBy']))? $_GET['markbookOrderBy'] : $selectOrderBy;

        $orderBy = array(
            'rollOrder'     => __('Roll Order'),
            'surname'       => __('Surname'),
            'preferredName' => __('Preferred Name'),
        );
        $col->addContent(__('Sort By').':')->prepend('&nbsp;&nbsp;');
        $col->addSelect('markbookOrderBy')->fromArray($orderBy)->selected($selectOrderBy)->setClass('shortWidth');

        $_SESSION[$guid]['markbookOrderBy'] = $selectOrderBy;
    }

    // SHOW
    $selectFilter = (isset($_SESSION[$guid]['markbookFilter']))? $_SESSION[$guid]['markbookFilter'] : '';
    $selectFilter = (isset($_GET['markbookFilter']))? $_GET['markbookFilter'] : $selectFilter;

    $_SESSION[$guid]['markbookFilter'] = $selectFilter;

    $filters = array('' => __('All Columns'));
    if ($enableColumnWeighting == 'Y') $filters['averages'] = __('Overall Grades');
    if ($enableRawAttainment == 'Y') $filters['raw'] = __('Raw Marks');
    $filters['marked'] = __('Marked');
    $filters['unmarked'] = __('Unmarked');
    
    $col->addContent(__('Show').':')->prepend('&nbsp;&nbsp;');
    $col->addSelect('markbookFilter')
        ->fromArray($filters)
        ->selected($selectFilter)
        ->setClass('shortWidth');

    // CLASS
    $col->addContent(__('Class').':')->prepend('&nbsp;&nbsp;');
    $col->addSelectClass('gibbonCourseClassID', $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'])
        ->setClass('mediumWidth')
        ->selected($gibbonCourseClassID);

    $col->addSubmit(__('Go'));

    $output .= $form->getOutput();

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

function getHookedUnits($pdo, $gibbonCourseClassID)
{
    $units = array();

    $dataHooks = array();
    $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name";
    $resultHooks = $pdo->executeQuery($dataHooks, $sqlHooks);

    while ($rowHooks = $resultHooks->fetch()) {
        $hookOptions = unserialize($rowHooks['options']);
        $requiredFields = array('unitTable', 'unitIDField', 'unitCourseIDField', 'unitNameField', 'unitDescriptionField', 'classLinkTable', 'classLinkJoinFieldUnit', 'classLinkJoinFieldClass', 'classLinkIDField');

        if (!array_diff_key(array_flip($requiredFields), $hookOptions)) {
            $dataHookUnits = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') WHERE '.$hookOptions['classLinkJoinFieldClass'].'=:gibbonCourseClassID ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
            $resultHookUnits = $pdo->executeQuery($dataHookUnits, $sqlHookUnits);

            while ($rowHookUnits = $resultHookUnits->fetch()) {
                $groupBy = $rowHooks['name'];
                $gibbonUnitID = $rowHookUnits[$hookOptions['unitIDField']];
                $gibbonHookID = $rowHooks['gibbonHookID'];
                $units[$groupBy][$gibbonUnitID.'-'.$gibbonHookID] = htmlPrep($rowHookUnits[$hookOptions['unitNameField']]);
            }
        }
    }

    return $units;
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

function renderStudentCumulativeMarks($gibbon, $pdo, $gibbonPersonID, $gibbonCourseClassID) {

    require_once $gibbon->session->get('absolutePath').'/modules/Markbook/src/markbookView.php';

    // Build the markbook object for this class & student
    $markbook = new Module\Markbook\markbookView($gibbon, $pdo, $gibbonCourseClassID);
    $assessmentScale = $markbook->getDefaultAssessmentScale();

    // Cancel our now if this isnt a percent-based mark
    if (empty($assessmentScale) || (stripos($assessmentScale['name'], 'percent') === false && $assessmentScale['nameShort'] !== '%')) {
        return;
    }

    // Calculate & get the cumulative average
    $markbook->cacheWeightings($gibbonPersonID);
    $cumulativeMark = round($markbook->getCumulativeAverage($gibbonPersonID));

    // Only display if there are marks
    if (!empty($cumulativeMark)) {
        // Divider
        echo '<tr class="break">';
            echo '<th colspan="7" style="height: 4px; padding: 0px;"></th>';
        echo '</tr>';

        // Display the cumulative average
        echo '<tr>';
            echo '<td style="width:120px;">';
                echo '<b>'.__('Cumulative Average').'</b>';
            echo '</td>';
            echo '<td style="padding: 10px !important; text-align: center;">';
                echo round( $cumulativeMark ).'%';
            echo '</td>';
            echo '<td colspan="3" class="dull"></td>';
         echo '</tr>';
    }
}
