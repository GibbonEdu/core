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

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_copy.php') == false) {
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
        $gibbonMarkbookCopyClassID = (isset($_POST['gibbonMarkbookCopyClassID']))? $_POST['gibbonMarkbookCopyClassID'] : null;

        if ( empty($gibbonCourseClassID) or empty($gibbonMarkbookCopyClassID) ) {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {

        	$highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);

            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __($guid, 'Copy Columns');
                echo '</h1>';
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();

	        	//Get teacher list
	            $teacherList = getTeacherList( $pdo, $gibbonCourseClassID );
	            $teaching = (isset($teacherList[ $_SESSION[$guid]['gibbonPersonID'] ]) );
	            $isCoordinator = isDepartmentCoordinator( $pdo, $_SESSION[$guid]['gibbonPersonID'] );

	            $canEditThisClass = ($teaching == true || $isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything');

	            if ($canEditThisClass == false) {
	            	//Acess denied
				    echo "<div class='error'>";
				    echo __($guid, 'You do not have access to this action.');
				    echo '</div>';
	            } else {

	            	echo "<div class='trail'>";
	            	echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_edit.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Edit').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Copy Columns').'</div>';
                    echo '</div>';
	            
	            	//Print mark
		            // echo '<h3>';
		            // echo __($guid, 'Copy Columns');
		            // echo '</h3>';

                    


		            try {
			            $data = array('gibbonCourseClassID' => $gibbonMarkbookCopyClassID);
			            $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID';
			            $result = $connection2->prepare($sql);
			            $result->execute($data);
			        } catch (PDOException $e) {
			            echo "<div class='error'>".$e->getMessage().'</div>';
			        }



			        if ($result->rowCount() < 1) {
	                    echo "<div class='error'>";
	                    echo __($guid, 'There are no records to display.');
	                    echo '</div>';
	                } else {

	                	try {
		                    $data2 = array('gibbonCourseClassID' => $gibbonMarkbookCopyClassID);
		                    $sql2 = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
		                    $result2 = $connection2->prepare($sql2);
		                    $result2->execute($data2);
		                } catch (PDOException $e) {
		                    echo "<div class='error'>".$e->getMessage().'</div>';
		                }

		                $row2 = $result2->fetch();

	                	echo '<p>';
	                	printf( __($guid, 'This action will copy the following columns from %s.%s to the current class %s.%s '), $row2['course'], $row2['class'], $row['course'], $row['class'] );
	                	echo '</p>';

	                	echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/modules/Markbook/markbook_edit_copyProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookCopyClassID=$gibbonMarkbookCopyClassID'>";

	                    echo "<table cellspacing='0' style='width: 100%' class='fullwidth colorOddEven'>";
	                    echo "<tr class='head'>";
	                    echo '<th style="width:40px">';
	  
	                    echo '</th>';
	                    echo '<th>';
	                    echo __($guid, 'Name');
	                    echo '</th>';
	                    echo '<th>';
	                    echo __($guid, 'Type');
	                    echo '</th>';
	                    echo '<th>';
	                    echo __($guid, 'Description');
	                    echo '</th>';
	                    echo '<th>';
	                    echo __($guid, 'Date<br/>Added');
	                    echo '</th>';
	                    echo '</tr>';

	                    $count = 0;
	                    while ($row = $result->fetch()) {
	                      
	                        //COLOR ROW BY STATUS!
	                        echo "<tr>";
	                        echo '<td>';
	                        echo '<input type="checkbox" value="1" name="copyColumnID['.$row['gibbonMarkbookColumnID'].']" checked>';
	                        echo '</td>';
	                        echo '<td>';
	                        echo '<b>'.$row['name'].'</b>';
	                        echo '</td>';
	                        echo '<td>';
	                        echo $row['type'];
	                        echo '</td>';
	                        echo '<td>';
	                        echo $row['description'];
	                        echo '</td>';
	                        echo '<td>';
	                        if (!empty($row['date']) && $row['date'] != '0000-00-00') {
	                            echo dateConvertBack($guid, $row['date']);
	                        }
	                        echo '</td>';
	                        
	                        echo '</tr>';

	                        

							$count++;
	                    }
	                    echo '<tr>';
							echo '<td colspan="7" class="right">';
							echo '<input type="submit" value="'.__($guid, 'Submit').'">';
							echo '</td>';
						echo '</tr>';

	                    echo '</table>';
	                    echo '</form>';
	                }

	            }


		    }
                

            
        }
    }
}
?>
