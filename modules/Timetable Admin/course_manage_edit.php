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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/course_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Courses & Classes')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Course & Classes').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'success0') {
            $deleteReturnMessage = __($guid, 'Your request was completed successfully.');
            $class = 'success';
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonCourseID = $_GET['gibbonCourseID'];
    if ($gibbonCourseID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonCourseID, gibbonDepartmentID, gibbonCourse.name AS name, gibbonCourse.nameShort as nameShort, orderBy, gibbonCourse.description, gibbonCourse.map, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList, credits, weight, gibbonCourseIDParent FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/course_manage_editProcess.php?gibbonCourseID='.$gibbonCourseID);
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);

			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->isRequired()->readonly()->setValue($values['yearName']);

			$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonDepartmentID', __('Learning Area'));
				$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql)->placeholder();

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
				$row->addTextField('name')->isRequired()->maxLength(60);
			
			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'));
				$row->addTextField('nameShort')->isRequired()->maxLength(12);
            
            $row = $form->addRow();
				$row->addLabel('credits', __('Credits'))->description(__('For GPA calculations and transcripts.'));
				$row->addNumber('credits')->maxLength(5)->decimalPlaces(2);

			$row = $form->addRow();
				$row->addLabel('weight', __('Weight'))->description(__('For GPA calculations and transcripts.'));
                $row->addNumber('weight')->maxLength(5)->decimalPlaces(2);
                
			$row = $form->addRow();
				$row->addLabel('orderBy', __('Order'))->description(__('May be used to adjust arrangement of courses in reports.'));
				$row->addNumber('orderBy')->maxLength(6);
            
            $data = array('gibbonSchoolYearID' => $values['gibbonSchoolYearID'], 'gibbonCourseID' => $gibbonCourseID);
            $sql = "SELECT gibbonCourseID as value, CONCAT(nameShort, ' - ', name) as name FROM gibbonCourse WHERE gibbonCourseID<>:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseIDParent IS NULL ORDER BY nameShort";
            $row = $form->addRow();
                $row->addLabel('gibbonCourseIDParent', __('Parent Course'))->description(__('Is this course a module or sub-set of another course?'));
                $row->addSelect('gibbonCourseIDParent')->fromQuery($pdo, $sql, $data)->placeholder();

			$row = $form->addRow();
				$column = $row->addColumn('blurb');
				$column->addLabel('description', __('Blurb'));
				$column->addEditor('description', $guid)->setRows(20);

			$row = $form->addRow();
				$row->addLabel('map', __('Include In Curriculum Map'));
                $row->addYesNo('map')->isRequired();

			$row = $form->addRow();
				$row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Enrolable year groups.'));
				$row->addCheckboxYearGroup('gibbonYearGroupIDList')->loadFromCSV($values);

			$row = $form->addRow();
				$row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __($guid, 'Edit Classes');
            echo '</h2>';

            //Set pagination variable
            try {
                $data = array('gibbonCourseID' => $gibbonCourseID);
                $sql = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/course_manage_class_add.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&gibbonCourseID=$gibbonCourseID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Short Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Participants');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Reportable');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo $row['name'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['nameShort'];
                    echo '</td>';
                    echo '<td>';
                    try {
                        $dataClasses = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                        $sqlClasses = 'SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status=\'Full\' AND gibbonCourseClassID=:gibbonCourseClassID AND NOT role LIKE \'% - Left\'';
                        $resultClasses = $connection2->prepare($sqlClasses);
                        $resultClasses->execute($dataClasses);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultClasses->rowCount() >= 0) {
                        echo $resultClasses->rowCount();
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['reportable'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/course_manage_class_edit.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/course_manage_class_delete.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_class_edit.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'><img title='".__($guid, 'Enrolment')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }
        }
    }
}
?>
