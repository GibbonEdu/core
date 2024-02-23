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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_copy.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if gibbonCourseClassID and gibbonMarkbookCopyClassID specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        $gibbonMarkbookCopyClassID = (isset($_POST['gibbonMarkbookCopyClassID']))? $_POST['gibbonMarkbookCopyClassID'] : null;

        if ( empty($gibbonCourseClassID) or empty($gibbonMarkbookCopyClassID) ) {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {

        	$highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);

            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Copy Columns');
                echo '</h1>';
                $page->addError(('The selected record does not exist, or you do not have access to it.'));
            } else {
                $course = $result->fetch();

                //Get teacher list
                $teacherList = getTeacherList($pdo, $gibbonCourseClassID);
                $teaching = isset($teacherList[$session->get('gibbonPersonID')]);
                $isCoordinator = isDepartmentCoordinator($pdo, $session->get('gibbonPersonID'));

                $canEditThisClass = ($teaching == true || $isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything');

                if ($canEditThisClass == false) {
                    //Acess denied
                    $page->addError(__('You do not have access to this action.'));
                } else {
                    $page->breadcrumbs
                        ->add(
                            __('Edit {courseClass} Markbook', [
                                'courseClass' => Format::courseClassName($course['course'], $course['class']),
                            ]),
                            'markbook_edit.php',
                            [
                                'gibbonCourseClassID' => $gibbonCourseClassID,
                            ]
                        )
                        ->add(__('Copy Columns'));


			            $data = array('gibbonCourseClassID' => $gibbonMarkbookCopyClassID);
			            $sql = "SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID";
			            $result = $connection2->prepare($sql);
			            $result->execute($data);

			        if ($result->rowCount() < 1) {
	                    echo $page->getBlankSlate();
	                } else {

		                    $data2 = array('gibbonCourseClassID' => $gibbonMarkbookCopyClassID);
		                    $sql2 = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
		                    $result2 = $connection2->prepare($sql2);
		                    $result2->execute($data2);

		                $courseFrom = $result2->fetch();

	                	echo '<p>';
	                	printf( __('This action will copy the following columns from %s.%s to the current class %s.%s '), $courseFrom['course'], $courseFrom['class'], $course['course'], $course['class'] );
                        echo '</p>';

                        echo '<fieldset>';

                        $form = Form::create('action', $session->get('absoluteURL').'/modules/Markbook/markbook_edit_copyProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonMarkbookCopyClassID='.$gibbonMarkbookCopyClassID);
                        $form->setClass('fullWidth');

                        $form->addHiddenValue('address', $session->get('address'));

                        $table = $form->addRow()->addTable()->setClass('fullWidth colorOddEven noMargin noPadding noBorder');

                        $header = $table->addHeaderRow();
                            $header->addCheckAll()->checked(true);
                            $header->addContent(__('Name'));
                            $header->addContent(__('Type'));
                            $header->addContent(__('Description'));
                            $header->addContent(__('Date Added'));

                        while ($column = $result->fetch()) {
                            $row = $table->addRow();
                                $row->addCheckbox('copyColumnID['.$column['gibbonMarkbookColumnID'].']')->setClass('textCenter')->checked(true);
                                $row->addContent($column['name'])->wrap('<strong>', '</strong>');
                                $row->addContent($column['type']);
                                $row->addContent($column['description']);
                                $row->addContent(!empty($column['date'])? Format::date($column['date']) : '');
                        }

                        $row = $form->addRow();
                            $row->addSubmit();

                        echo $form->getOutput();

                        echo '</fieldset>';
	                }
	            }
		    }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'markbook_edit.php'));
}
