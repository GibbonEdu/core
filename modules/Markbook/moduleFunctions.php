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

function getReportGrade($pdo, $reportName, $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID) {

    // read criteria for this subject
    $data = array(
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
        'reportName' => $reportName,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'today' => date('Y-m-d'),
    );
    $sql = "SELECT arrReportGrade.gradeID
        FROM arrCriteria
        JOIN arrReport ON (arrCriteria.reportID=arrReport.reportID)
        JOIN arrReportGrade ON (arrReportGrade.criteriaID=arrCriteria.criteriaID)
        JOIN gibbonCourseClass ON (arrCriteria.subjectID=gibbonCourseClass.gibbonCourseID)
        WHERE arrReport.reportName=:reportName
        AND arrReport.schoolYearID=:gibbonSchoolYearID
        AND arrReport.endDate<=:today
        AND arrCriteria.criteriaType = 2
        AND arrReportGrade.studentID=:gibbonPersonIDStudent
        AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID";
    $rs = $pdo->executeQuery($data, $sql);

    return ($rs && $rs->rowCount() >= 1)? $rs->fetchColumn(0) : false;
}

function getCriteriaGrade($pdo, $criteriaType, $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID) {

    // read criteria for this subject
    $data = array(
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
        'criteriaType' => $criteriaType,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'today' => date('Y-m-d'),
    );
    $sql = "SELECT arrReportGrade.gradeID
        FROM arrCriteria
        JOIN arrReport ON (arrCriteria.reportID=arrReport.reportID)
        JOIN arrReportGrade ON (arrReportGrade.criteriaID=arrCriteria.criteriaID)
        JOIN gibbonCourseClass ON (arrCriteria.subjectID=gibbonCourseClass.gibbonCourseID)
        WHERE arrCriteria.criteriaType =:criteriaType
        AND arrReportGrade.studentID=:gibbonPersonIDStudent
        AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
        AND arrReport.schoolYearID=:gibbonSchoolYearID
        AND arrReport.endDate<=:today
        ORDER BY arrCriteria.reportID DESC LIMIT 1";
    $rs = $pdo->executeQuery($data, $sql);

    return ($rs->rowCount() >= 1)? $rs->fetchColumn(0) : false;
}

function getLegacyGrade($pdo, $reportName, $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID) {
    // read criteria for this subject
    $data = array(
        'reportName' => $reportName,
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'gibbonPersonID' => $gibbonPersonIDStudent,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
    );
    $sql = "SELECT grade
        FROM arrLegacyGrade
        WHERE arrLegacyGrade.reportTerm=:reportName
        AND arrLegacyGrade.gibbonSchoolYearID=:gibbonSchoolYearID
        AND arrLegacyGrade.gibbonPersonID=:gibbonPersonID
        AND arrLegacyGrade.gibbonCourseClassID=:gibbonCourseClassID";
    $rs = $pdo->executeQuery($data, $sql);

    return ($rs && $rs->rowCount() >= 1)? $rs->fetchColumn(0) : false;
}

function renderStudentGPA( $pdo, $guid, $gibbonPersonIDStudent ) {

    $data = array(
        'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
        'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],
        'today' => date('Y-m-d'),
    );
    $sql = "SELECT arrReportGPA.GPA, arrReport.reportName
        FROM arrReportGPA
        JOIN arrReport ON (arrReportGPA.reportID=arrReport.reportID)
        WHERE arrReport.schoolYearID=:gibbonSchoolYearID
        AND arrReport.endDate<=:today
        AND arrReportGPA.studentID=:gibbonPersonIDStudent
        ORDER BY arrReport.reportID ASC";
    $rs = $pdo->executeQuery($data, $sql);

    if ($rs->rowCount() == 0) return;

    $marks = $rs->fetchAll();

    echo '<h4>Current GPA</h4>';

    echo '<table class="mini fullWidth" cellspacing="0">';
        echo '<tr class="head">';

        foreach ($marks as $row) {
            if (empty($row['GPA'])) continue;
            echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 65px;font-size: 11px;">'.$row['reportName'].'</td>';
        }

        echo '<td rowspan="2" style="padding: 10px 30px !important; border: 0; border-left: 1px solid #dfdfdf;">';
            echo '<span class="small emphasis">A student\'s GPA is the weighted average of course marks, taking into account the credit value of each course. The GPA\'s listed here are from  posted report card marks.</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr>';

        foreach ($marks as $row) {
            if (empty($row['GPA'])) continue;
            echo '<td style="padding: 10px !important; text-align: center;">'.round( $row['GPA'], 1 ).'%</td>';
        }
    echo '</tr>';

    echo '</table>';
}

function renderStudentCourseAverage($pdo, $guid, $gibbonPersonIDStudent)
{
    global $gibbon;
    require_once './modules/Markbook/src/markbookView.php';

    $gibbonSchoolYearID = (!empty($gibbonSchoolYearID))? $gibbonSchoolYearID : $_SESSION[$guid]['gibbonSchoolYearID'];

    $data = array(
        'gibbonPersonID' => $gibbonPersonIDStudent,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
    );
    $sql = "SELECT gibbonCourseClassPerson.gibbonCourseClassID, gibbonCourse.weight as courseWeight, (CASE WHEN gibbonCourse.orderBy > 0 THEN gibbonCourse.orderBy ELSE 80 end) as courseOrder
            FROM gibbonCourseClassPerson
            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID)
            JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            WHERE gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID
            AND gibbonCourseClassPerson.role = 'Student'
            AND gibbonCourseClass.reportable = 'Y'
            AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
            GROUP BY gibbonCourseClass.gibbonCourseClassID
            ORDER BY courseOrder";

    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() == 0) return;

    $total = 0;
    $cumulative = 0;

    while ($course = $result->fetch())
    {
        // Build the markbook object for this class & student
        $markbook = new Module\Markbook\markbookView($gibbon, $pdo, $course['gibbonCourseClassID'] );
        $markbook->cacheWeightings($gibbonPersonIDStudent);
        
        // Grab the course weight and grade
        $weight = $course['courseWeight'];
        $grade = $markbook->getCumulativeAverage($gibbonPersonIDStudent);
        
        // Skip any empty or incomplete marks
        if ($grade == '' || $grade == '-' || $grade == 'INC') continue;

        // Sum the cumulative weight & grades
        $total += $weight;
        $cumulative += ($grade * $weight);
    }

    if (empty($total) || empty($cumulative) ) return;
    
    // Calculate the GPA
    $gpa = ( $cumulative / $total );
    $gpa = round( min(100.0, max(0.0, $gpa)), 2);

    if ($gpa >= 95.0) {
        $status = 'Scholars';
    } else if ($gpa >= 90.0) {
        $status = 'Distinction';
    } else if ($gpa >= 80.0) {
        $status = 'Honours';
    } else if ($gpa >= 60.0) {
        $status = 'Good Standing';
    } else {
        $status = 'At Risk';
    }
    
    echo '<h4>Current Cumulative Average</h4>';
    
    echo '<table class="mini fullWidth" cellspacing="0">';
        echo '<tr class="head">';

        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 85px;font-size: 11px;">'.__('Average').'</td>';
        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 85px;font-size: 11px;">'.__('Status').'</td>';

        echo '<td rowspan="2" style="padding: 10px 30px !important; border: 0; border-left: 1px solid #dfdfdf;">';
            echo '<span class="small emphasis">The current average is weighted per course and calculated from ongoing course work. All markbook grades are subject to change. The average listed here is not a posted grade and may differ from the final GPA for this term. <b>Only visible to teachers and staff at this time.</b></span>';
        echo '</td>';
    echo '</tr>';
    echo '<tr>';
        echo '<td style="padding: 10px !important; text-align: center;">'.round( $gpa, 1 ).'%</td>';
        echo '<td style="padding: 10px !important; text-align: center;">'.$status.'</td>';
    echo '</tr>';
    echo '</table>';
}

function renderStudentCumulativeMarks($gibbon, $pdo, $gibbonPersonIDStudent, $gibbonCourseClassID, $gibbonSchoolYearID = '') {

    $guid = $gibbon->guid();
    $gibbonSchoolYearID = (!empty($gibbonSchoolYearID))? $gibbonSchoolYearID : $_SESSION[$guid]['gibbonSchoolYearID'];

    if (intval($gibbonSchoolYearID) < 11) {
        // LEGACY GRADES
        $sem1Mid = getLegacyGrade($pdo, 'Sem1-Mid', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem1End = getLegacyGrade($pdo, 'Sem1-End', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem2Mid = getLegacyGrade($pdo, 'Sem2-Mid', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem2End = getLegacyGrade($pdo, 'Sem2-End', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $finalMark = getLegacyGrade($pdo, 'Final', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);

        $message = '<b>Course complete</b>: Final marks listed are from report card grades.';
    } else {
        // Gibbon Reporting Grades
        $sem1Mid = getReportGrade($pdo, 'Sem1-Mid', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem1End = getReportGrade($pdo, 'Sem1-End', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem2Mid = getReportGrade($pdo, 'Sem2-Mid', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        $sem2End = getReportGrade($pdo, 'Sem2-End', $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);

        $finalMark = getCriteriaGrade($pdo, 4, $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);

        if (!empty($finalMark)) {

            $message = '<b>Course complete</b>: Final marks listed are from report card grades.';

            $courseMark = '';
            $examMark = getCriteriaGrade($pdo, 1, $gibbonSchoolYearID, $gibbonPersonIDStudent, $gibbonCourseClassID);
        } else {

            $enableColumnWeighting = getSettingByScope($pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
            if ($enableColumnWeighting != 'Y') return;

            require_once './modules/Markbook/src/markbookView.php';

            // Build the markbook object for this class & student
            $markbook = new Module\Markbook\markbookView($gibbon, $pdo, $gibbonCourseClassID );
            $markbook->cacheWeightings( $gibbonPersonIDStudent );

            $message = '<b>Current course</b>: Overall mark is a cumulative grade from ongoing course work.';

            $courseMark = round( $markbook->getCumulativeAverage( $gibbonPersonIDStudent ) );
            $examMark = ''; //round( $markbook->getTermAverage($gibbonPersonIDStudent, 'final') );
            $finalMark = ''; //round( $markbook->getFinalGradeAverage( $gibbonPersonIDStudent ) );
        }
    }

    // Only display if there are marks
    if (!empty($courseMark) || !empty($examMark) || !empty($finalMark) ) {
        echo '<tr>';

        echo '<td colspan=7 style="padding:0;">';
        echo '<table class="mini fullWidth" style="margin: 0; border: 0;" cellspacing="0">';
        echo '<tr class="head">';

        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 64px;font-size: 11px;">'.__($guid, 'Sem1-Mid').'</td>';
        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 64px;font-size: 11px;">'.__($guid, 'Sem1-End').'</td>';
        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 64px;font-size: 11px;">'.__($guid, 'Sem2-Mid').'</td>';
        echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 64px;font-size: 11px;">'.__($guid, 'Sem2-End').'</td>';

        echo '<td rowspan="2" style="padding: 10px 30px !important;">';
            echo '<span class="small emphasis">'.$message.'</span>';
        echo '</td>';

        if (!empty($courseMark)) {
            echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 65px;">'.__($guid, 'Course').'</td>';
        }
        if (!empty($examMark)) {
            echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 65px;">'.__($guid, 'Exam').'</td>';
        }
        if (!empty($finalMark)) {
            echo '<th class="columnLabel" style="border: 0; padding: 10px !important;text-align: center; width: 65px;">'.__($guid, 'Final').'</td>';
        }
        echo '</tr>';

        echo '<tr>';

        echo '<td style="padding: 10px !important; text-align: center;">'.( !empty($sem1Mid)? round( $sem1Mid ).'%' : '' ) .'</td>';

        echo '<td style="padding: 10px !important; text-align: center;">'.( !empty($sem1End)? round( $sem1End ).'%' : '' ) .'</td>';

        echo '<td style="padding: 10px !important; text-align: center;">'.( !empty($sem2Mid)? round( $sem2Mid ).'%' : '' ) .'</td>';

        echo '<td style="padding: 10px !important; text-align: center;">'.( !empty($sem2End)? round( $sem2End ).'%' : '' ) .'</td>';

        // Display the cumulative average
        if (!empty($courseMark)) {
            echo '<td style="background: -moz-linear-gradient(top, #f2f2f2, #f0f0f0); padding: 10px !important; text-align: center;">';
            echo round( $courseMark ).'%' .'</td>';
        }

        // Display final exam mark
        if (!empty($examMark)) {
            echo '<td style="background: -moz-linear-gradient(top, #f2f2f2, #f0f0f0); padding: 10px !important; text-align: center;">';
            echo round( $examMark ).'%' .'</td>';
        }

        // Display final course mark
        if (!empty($finalMark)) {
            echo '<td style="background: -moz-linear-gradient(top, #f2f2f2, #f0f0f0); padding: 10px !important; text-align: center;">';
            echo round( $finalMark ).'%' .'</td>';
        }

        echo '</tr></table>';
        echo '</td>';
        echo '</tr>';

        return true;
    } else {
        return false;
    }
}
