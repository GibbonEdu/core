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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_targets.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        if ($gibbonCourseClassID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList, gibbonScaleIDTarget FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList, gibbonScaleIDTarget FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Let's go!
                $course = $result->fetch();

                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$course['course'].'.'.$course['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Set Personalised Attainment Targets').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                $form = Form::create('markbookTargets', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/markbook_edit_targetsProcess.php?gibbonCourseClassID='.$gibbonCourseClassID);

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $selectGradeScale = !empty($course['gibbonScaleIDTarget'])? $course['gibbonScaleIDTarget'] : $_SESSION[$guid]['defaultAssessmentScale'];
                $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE (active='Y') ORDER BY name";
                $row = $form->addRow();
                    $row->addLabel('gibbonScaleIDTarget', __('Target Scale'));
                    $row->addSelect('gibbonScaleIDTarget')->fromQuery($pdo, $sql)->selected($selectGradeScale)->placeholder();

                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding noBorder');

                $header = $table->addHeaderRow();
                $header->addContent(__('Student'));

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'today' => date('Y-m-d'));
                $sql = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart, gibbonMarkbookTarget.gibbonScaleGradeID as currentTarget
                        FROM gibbonCourseClassPerson 
                        JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                        LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID 
                            AND gibbonMarkbookTarget.gibbonPersonIDStudent=gibbonCourseClassPerson.gibbonPersonID)
                        WHERE role='Student' AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                        AND status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) 
                        ORDER BY surname, preferredName";
                $result = $pdo->executeQuery($data, $sql);

                if ($result->rowCount() > 0) {
                    $header->addContent(__('Attainment Target'))->setClass('standardWidth');

                    $sql = "SELECT gibbonScale.gibbonScaleID, gibbonScaleGradeID as value, gibbonScaleGrade.value as name 
                            FROM gibbonScaleGrade 
                            JOIN gibbonScale ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) 
                            WHERE gibbonScale.active='Y' 
                            ORDER BY gibbonScale.gibbonScaleID, sequenceNumber";
                    $resultGrades = $pdo->executeQuery(array(), $sql);

                    $grades = ($resultGrades->rowCount() > 0)? $resultGrades->fetchAll() : array();
                    $gradesChained = array_combine(array_column($grades, 'value'), array_column($grades, 'gibbonScaleID'));
                    $gradesOptions = array_combine(array_column($grades, 'value'), array_column($grades, 'name'));

                    $count = 0;
                    while ($student = $result->fetch()) {
                        $count++;

                        $row = $table->addRow();
                        $row->addWebLink(formatName('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Internal Assessment')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($count.') ');
                        
                        $row->addSelect($count.'-gibbonScaleGradeID')
                            ->fromArray($gradesOptions)
                            ->chainedTo('gibbonScaleIDTarget', $gradesChained)
                            ->setClass('mediumWidth')
                            ->selected($student['currentTarget'])
                            ->placeholder();

                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
                    }

                    $form->addHiddenValue('count', $count);
                } else {
                    $table->addRow()->addAlert(__($guid, 'There are no records to display.'), 'error');
                }

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'markbook_edit_targets.php');
}
?>
