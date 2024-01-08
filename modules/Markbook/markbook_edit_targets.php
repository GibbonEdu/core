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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_targets.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if gibbonCourseClassID specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        if ($gibbonCourseClassID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList, gibbonScaleIDTarget FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    LEFT JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID)
                    LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID)
                    WHERE ((gibbonCourseClassPerson.gibbonCourseClassPersonID IS NOT NULL AND gibbonCourseClassPerson.role='Teacher')
                        OR (gibbonDepartmentStaff.gibbonDepartmentStaffID IS NOT NULL AND (gibbonDepartmentStaff.role = 'Coordinator' OR gibbonDepartmentStaff.role = 'Assistant Coordinator' OR gibbonDepartmentStaff.role= 'Teacher (Curriculum)'))
                        )
                    AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList, gibbonScaleIDTarget FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Let's go!
                $course = $result->fetch();

                $page->breadcrumbs
                    ->add(
                        __('View {courseClass} Markbook', [
                            'courseClass' => Format::courseClassName($course['course'], $course['class']),
                        ]),
                        'markbook_view.php',
                        [
                            'gibbonCourseClassID' => $gibbonCourseClassID,
                        ]
                    )
                    ->add(__('Set Personalised Attainment Targets'));

                $form = Form::create('markbookTargets', $session->get('absoluteURL').'/modules/'.$session->get('module').'/markbook_edit_targetsProcess.php?gibbonCourseClassID='.$gibbonCourseClassID);
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->addHiddenValue('address', $session->get('address'));

                $selectedGradeScale = !empty($course['gibbonScaleIDTarget'])? $course['gibbonScaleIDTarget'] : $session->get('defaultAssessmentScale');
                $row = $form->addRow();
                    $row->addLabel('gibbonScaleIDTarget', __('Target Scale'));
                    $row->addSelectGradeScale('gibbonScaleIDTarget')->selected($selectedGradeScale);

                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding');

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
                    $header->addContent(__('Attainment Target'))->setClass('w-64');

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
                        $row->addWebLink(Format::name('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Internal Assessment')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($count.') ');

                        $row->addSelect($count.'-gibbonScaleGradeID')
                            ->fromArray($gradesOptions)
                            ->chainedTo('gibbonScaleIDTarget', $gradesChained)
                            ->setClass('standardWidth')
                            ->selected($student['currentTarget'])
                            ->placeholder();

                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
                    }

                    $form->addHiddenValue('count', $count);
                } else {
                    $table->addRow()->addAlert(__('There are no records to display.'), 'error');
                }

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'markbook_edit_targets.php'));
}
