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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $importReturn = $_GET['importReturn'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Timetables'), 'tt.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Import Timetable Data'));

    $importReturnMessage = '';
    $class = 'error';
    if (!($importReturn == '')) {
        if ($importReturn == 'fail0') {
            $importReturnMessage = __('Your request failed because you do not have access to this action.');
        } elseif ($importReturn == 'fail1') {
            $importReturnMessage = __('Your request failed because your inputs were invalid.');
        } elseif ($importReturn == 'fail2') {
            $importReturnMessage = __('Your request failed due to a database error.');
        } elseif ($importReturn == 'fail3') {
            $importReturnMessage = __('Your request failed because your inputs were invalid.');
        }
        echo "<div class='$class'>";
        echo $importReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonTTID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if (isset($_GET['step'])) {
                $step = $_GET['step'];
            } else {
                $step = 1;
            }
            if (($step != 1) and ($step != 2) and ($step != 3)) {
                $step = 1;
            }

            //STEP 1, SELECT TERM
            if ($step == 1) {
                echo '<h2>';
					echo __('Step 1 - Select CSV Files');
				echo '</h2>';
				echo '<p>';
					echo __('This page allows you to import timetable data from a CSV file. The import includes all classes and their teachers. There is no support for importing students: these need to be entered manually into the relavent classes. The system will do its best to keep existing data in tact, whilst updating what is necessary (note: you will lose student exceptions from timetabled classes). Select the CSV files you wish to use for the synchronise operation.')."<br/>";
				echo '</p>';

                $form = Form::create('importTimetable', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/tt_import.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&step=2");

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $row = $form->addRow();
                    $row->addLabel('file', __('CSV File'))->description(__('See Notes below for specification.'));
                    $row->addFileUpload('file')->isRequired();

                $row = $form->addRow();
                    $row->addLabel('fieldDelimiter', __('Field Delimiter'));
                    $row->addTextField('fieldDelimiter')->isRequired()->maxLength(1)->setValue(',');

                $row = $form->addRow();
                    $row->addLabel('stringEnclosure', __('String Enclosure'));
                    $row->addTextField('stringEnclosure')->isRequired()->maxLength(1)->setValue('"');

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();

                echo '<h4>';
				echo __('Notes');
				echo '</h4>';
				echo '<ol>';
					echo '<li>'.__('You may only submit CSV files.').'</li>';
					echo '<li>'.__('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).').'</li>';
					echo '<li>'.__('The import includes course, class, period, teacher and room information: the structure of the target timetable must already be in place.').'</li>';
					echo '<li>'.__('The import does not include student lists.').'</li>';
					echo '<li>'.__('The submitted file must have the following fields in the following order:').'</li>';
						echo '<ol>';
							echo '<li><b>'.__('Course Short Name</b> - e.g. DR10 for Year 10 Drama').'</li>';
							echo '<li><b>'.__('Class Short Name</b> - e.g 1 for DR10.1').'</li>';
							echo '<li><b>'.__('Day Name</b> - as used in the target timetable').'</li>';
							echo '<li><b>'.__('Row Long Name</b> - as used in the target timetable').'</li>';
							echo '<li><b>'.__('Teacher Username</b> - comma-separated list of Gibbon usernames for teacher(s) of the lesson. Alternatively, give each teacher their own row.').'</li>';
							echo '<li><b>'.__('Space Name</b> - the Gibbon name for the room the lesson takes place in.').'</li>';
						echo '</ol>';
					echo '</li>';
					echo '<li>'.__('Do not include a header row in the CSV files.').'</li>';
				echo '</ol>';
            } elseif ($step == 2) {
                echo '<h2>';
					echo __('Step 2 - Data Check & Confirm');
				echo '</h2>';

                //Check file type
                if (($_FILES['file']['type'] != 'text/csv') and ($_FILES['file']['type'] != 'text/comma-separated-values') and ($_FILES['file']['type'] != 'text/x-comma-separated-values') and ($_FILES['file']['type'] != 'application/vnd.ms-excel') and ($_FILES['file']['type'] != 'application/csv')) {
                    ?>
					<div class='error'>
						<?php echo sprintf(__('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
					</div>
					<?php

                } elseif (($_POST['fieldDelimiter'] == '') or ($_POST['stringEnclosure'] == '')) {
                    ?>
					<div class='error'>
						<?php echo __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
					</div>
					<?php

                } else {
                    $proceed = true;

                    //PREPARE TABLES
                    echo '<h4>';
                    echo __('Prepare Database Tables');
                    echo '</h4>';
                    //Lock tables
                    $lockFail = false;
                    try {
                        $sql = 'LOCK TABLES gibbonTTImport WRITE,
						gibbonPerson WRITE,
						gibbonSpace WRITE,
						gibbonTTDay WRITE,
						gibbonTT WRITE,
						gibbonTTColumn WRITE,
						gibbonTTColumnRow WRITE,
						gibbonTTDayRowClass WRITE,
						gibbonTTDayRowClassException WRITE,
						gibbonCourse WRITE,
						gibbonCourseClass WRITE,
						gibbonCourseClassPerson WRITE';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $lockFail = true;
                        $proceed = false;
                    }
                    if ($lockFail == true) {
                        echo "<div class='error'>";
                        echo __('The database could not be locked for use.');
                        echo '</div>';
                    } elseif ($lockFail == false) {
                        echo "<div class='success'>";
                        echo __('The database was successfully locked.');
                        echo '</div>';
                    }
                    //Empty table gibbonTTImport
                    $emptyFail = false;
                    try {
                        $sql = 'DELETE FROM gibbonTTImport ';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $emptyFail = true;
                        $proceed = false;
                    }
                    if ($emptyFail == true) {
                        echo "<div class='error'>";
                        echo __('The database tables could not be emptied.');
                        echo '</div>';
                    } elseif ($emptyFail == false) {
                        echo "<div class='success'>";
                        echo __('The database tables were successfully emptied.');
                        echo '</div>';
                    }

                    //TURN IMPORT FILE INTO gibbonTTImport
                    if ($proceed == true) {
                        echo '<h4>';
                        echo __('File Import');
                        echo '</h4>';
                        $importFail = false;
                        $csvFile = $_FILES['file']['tmp_name'];
                        $handle = fopen($csvFile, 'r');
                        while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                            try {
                                $data = array('courseNameShort' => $data[0], 'classNameShort' => $data[1], 'dayName' => $data[2], 'rowName' => $data[3], 'teacherUsernameList' => $data[4], 'spaceName' => $data[5]);
                                $sql = 'INSERT INTO gibbonTTImport SET courseNameShort=:courseNameShort, classNameShort=:classNameShort, dayName=:dayName, rowName=:rowName, teacherUsernameList=:teacherUsernameList, spaceName=:spaceName';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                                $importFail = true;
                                $proceed = false;
                            }
                        }
                        fclose($handle);
                        if ($importFail == true) {
                            echo "<div class='error'>";
                            echo __('The import file could not be temporarily stored in the database for analysis.');
                            echo '</div>';
                        } elseif ($importFail == false) {
                            echo "<div class='success'>";
                            echo __('The import file was successfully stored in the database for analysis.');
                            echo '</div>';
                        }
                    }

                    //STAFF CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Staff Check';
                        echo '</h4>';
                        $staffCheckFail = false;
                        //Get list of staff from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT teacherUsernameList FROM gibbonTTImport ORDER BY teacherUsernameList';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $staffCheckFail = true;
                            $proceed = false;
                        }
                        //Check each member of staff from import file against Gibbon
                        if ($staffCheckFail == false) {
                            $staffs = array();
                            $count = 0;
                            while ($row = $result->fetch()) {
                                $staffTemps = explode(',', $row['teacherUsernameList']);
                                foreach ($staffTemps as $staffTemp) {
                                    $staffs[$count] = trim($staffTemp);
                                    ++$count;
                                }
                            }

                            sort($staffs);
                            $staffs = array_unique($staffs);
                            $errorList = '';
                            foreach ($staffs as $staff) {
                                try {
                                    $data = array('username' => $staff);
                                    $sql = 'SELECT * FROM gibbonPerson WHERE username=:username';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                }

                                if ($result->rowCount() != 1) {
                                    $staffCheckFail = true;
                                    $proceed = false;
                                    $errorList .= "$staff, ";
                                }
                            }
                        }
                        if ($staffCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Staff check failed. The following staff were in the import file but could not be found in Gibbon: %1$s. Add the staff into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($staffCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The staff check was successfully completed: all staff in the import file were found in Gibbon.');
                            echo '</div>';
                        }
                    }

                    //SPACE CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Space Check';
                        echo '</h4>';
                        $spaceCheckFail = false;
                        //Get list of spaces from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT spaceName FROM gibbonTTImport ORDER BY spaceName';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $spaceCheckFail = true;
                            $proceed = false;
                        }
                        //Check each member of staff from import file against Gibbon
                        if ($spaceCheckFail == false) {
                            $errorList = '';
                            while ($row = $result->fetch()) {
                                try {
                                    $dataSpace = array('name' => $row['spaceName']);
                                    $sqlSpace = 'SELECT * FROM gibbonSpace WHERE name=:name';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                }

                                if ($resultSpace->rowCount() != 1) {
                                    $spaceCheckFail = true;
                                    $proceed = false;
                                    $errorList .= $row['spaceName'].', ';
                                }
                            }
                        }
                        if ($spaceCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Space check failed. The following spaces were in the import file but could not be found in Gibbon: %1$s. Add the spaces into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($spaceCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The space check was successfully completed: all spaces in the import file were found in Gibbon.');
                            echo '</div>';
                        }
                    }

                    //DAY CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Day Check';
                        echo '</h4>';
                        $dayCheckFail = false;
                        //Get list of spaces from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT dayName FROM gibbonTTImport ORDER BY dayName';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $dayCheckFail = true;
                            $proceed = false;
                        }
                        //Check each member of staff from import file against Gibbon
                        if ($dayCheckFail == false) {
                            $errorList = '';
                            while ($row = $result->fetch()) {
                                try {
                                    $dataSpace = array('name' => $row['dayName'], 'gibbonTTID' => $gibbonTTID);
                                    $sqlSpace = 'SELECT * FROM gibbonTTDay WHERE name=:name AND gibbonTTID=:gibbonTTID';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                }

                                if ($resultSpace->rowCount() != 1) {
                                    $dayCheckFail = true;
                                    $proceed = false;
                                    $errorList .= $row['dayName'].', ';
                                }
                            }
                        }
                        if ($dayCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Day check failed. The following days were in the import file but could not be found in Gibbon: %1$s. Add the days into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($dayCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The day check was successfully completed: all days in the import file were found in Gibbon in the specified timetable.');
                            echo '</div>';
                        }
                    }

                    //ROW CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Row Check';
                        echo '</h4>';
                        $rowCheckFail = false;
                        //Get list of spaces from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT dayName, rowName FROM gibbonTTImport ORDER BY dayName, rowName';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $rowCheckFail = true;
                            $proceed = false;
                        }
                        //Check each member of staff from import file against Gibbon
                        if ($rowCheckFail == false) {
                            $errorList = '';
                            while ($row = $result->fetch()) {
                                try {
                                    $dataSpace = array('rowName' => $row['rowName'], 'dayName' => $row['dayName'], 'gibbonTTID' => $gibbonTTID);
                                    $sqlSpace = 'SELECT gibbonTTColumnRow.name, gibbonTTDay.name FROM gibbonTTDay JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTT.gibbonTTID=:gibbonTTID AND gibbonTTColumnRow.name=:rowName AND gibbonTTDay.name=:dayName';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                }

                                if ($resultSpace->rowCount() != 1) {
                                    $rowCheckFail = true;
                                    $proceed = false;
                                    $errorList .= $row['dayName'].' '.$row['rowName'].', ';
                                }
                            }
                        }
                        if ($rowCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Row check failed. The following rows were in the import file but could not be found in Gibbon: %1$s. Add the rows into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($rowCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The row check was successfully completed: all rows in the import file were found in Gibbon in the specified timetable on the specified days.');
                            echo '</div>';
                        }
                    }

                    //COURSE CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Course Check';
                        echo '</h4>';
                        $courseCheckFail = false;
                        //Get list of courses from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT courseNameShort FROM gibbonTTImport ORDER BY courseNameShort';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $courseCheckFail = true;
                            $proceed = false;
                        }
                        //Check each course from import file against Gibbon
                        if ($courseCheckFail == false) {
                            $errorList = '';
                            $makeList = '';
                            while ($row = $result->fetch()) {
                                $makeFail = false;
                                try {
                                    $dataSpace = array('nameShort' => $row['courseNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                    $sqlSpace = 'SELECT nameShort FROM gibbonCourse WHERE nameShort=:nameShort AND gibbonSchoolYearID=:gibbonSchoolYearID';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                }

                                if ($resultSpace->rowCount() != 1) {
                                    //Make the course
                                    try {
                                        $dataMake = array('name' => $row['courseNameShort'], 'nameShort' => $row['courseNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                        $sqlMake = 'INSERT INTO gibbonCourse SET name=:name, nameShort=:nameShort, gibbonSchoolYearID=:gibbonSchoolYearID';
                                        $resultMake = $connection2->prepare($sqlMake);
                                        $resultMake->execute($dataMake);
                                    } catch (PDOException $e) {
                                        $makeFail = true;
                                        $courseCheckFail = true;
                                        $proceed = false;
                                        $errorList .= $row['courseNameShort'].', ';
                                    }
                                    if ($makeFail == false) {
                                        $makeList .= $row['courseNameShort'].', ';
                                    }
                                }
                            }
                        }
                        if ($courseCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Course check failed. The following courses were in the import file but could not be found or made in Gibbon: %1$s. Add the courses into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($courseCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The course check was successfully completed: all courses in the import file were found in or added to Gibbon.');
                            if ($makeList != '') {
                                echo ' '.sprintf(__('The following courses were added to Gibbon: %1$s.'), substr($makeList, 0, -2));
                            }
                            echo '</div>';
                        }
                    }

                    //CLASS CHECK
                    if ($proceed == true) {
                        echo '<h4>';
                        echo 'Class Check';
                        echo '</h4>';
                        $classCheckFail = false;
                        //Get list of class from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT courseNameShort, classNameShort FROM gibbonTTImport ORDER BY courseNameShort, classNameShort';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $classCheckFail = true;
                            $proceed = false;
                        }
                        //Check each class from import file against Gibbon
                        if ($classCheckFail == false) {
                            $errorList = '';
                            $makeList = '';
                            while ($row = $result->fetch()) {
                                $makeFail = false;
                                try {
                                    $dataSpace = array('classNameShort' => $row['classNameShort'], 'courseNameShort' => $row['courseNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                    $sqlSpace = 'SELECT gibbonCourseClass.nameShort FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.nameShort=:courseNameShort AND gibbonCourseClass.nameShort=:classNameShort AND gibbonSchoolYearID=:gibbonSchoolYearID';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                }

                                if ($resultSpace->rowCount() != 1) {
                                    //Make the class
                                    try {
                                        $dataMake = array('name' => $row['classNameShort'], 'nameShort' => $row['classNameShort'], 'courseNameShort' => $row['courseNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                        $sqlMake = 'INSERT INTO gibbonCourseClass SET name=:name, nameShort=:nameShort, gibbonCourseID=(SELECT gibbonCourseID FROM gibbonCourse WHERE nameShort=:courseNameShort AND gibbonSchoolYearID=:gibbonSchoolYearID)';
                                        $resultMake = $connection2->prepare($sqlMake);
                                        $resultMake->execute($dataMake);
                                    } catch (PDOException $e) {
                                        $makeFail = true;
                                        $classCheckFail = true;
                                        $proceed = false;
                                        $errorList .= $row['courseNameShort'].'.'.$row['classNameShort'].', ';
                                    }
                                    if ($makeFail == false) {
                                        $makeList .= $row['courseNameShort'].'.'.$row['classNameShort'].', ';
                                    }
                                }
                            }
                        }
                        if ($classCheckFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Class check failed. The following classes were in the import file but could not be found or made in Gibbon: %1$s. Add the classes into Gibbon and then try the import again.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($classCheckFail == false) {
                            echo "<div class='success'>";
                            echo __('The class check was successfully completed: all classes in the import file were found in or added to Gibbon.');
                            if ($makeList != '') {
                                echo ' '.sprintf(__('The following classes were added to Gibbon: %1$s.'), substr($makeList, 0, -2));
                            }
                            echo '</div>';
                        }
                    }

                    //TEACHER SYNC
                    if ($proceed == true) {
                        echo '<h4>';
                        echo __('Teacher Sync');
                        echo '</h4>';
                        $teacherSyncFail = false;
                        //Get list of classes from import
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT courseNameShort, classNameShort FROM gibbonTTImport ORDER BY courseNameShort, classNameShort';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $teacherSyncFail = true;
                            $proceed = false;
                        }
                        //Check each class from import file against Gibbon
                        if ($teacherSyncFail == false) {
                            $errorList = '';
                            while ($row = $result->fetch()) {
                                //Get gibbonCourseClassID
                                $checkFail = false;
                                try {
                                    $dataCheck = array('classNameShort' => $row['classNameShort'], 'courseNameShort' => $row['courseNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                    $sqlCheck = 'SELECT gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.nameShort=:courseNameShort AND gibbonCourseClass.nameShort=:classNameShort AND gibbonSchoolYearID=:gibbonSchoolYearID';
                                    $resultCheck = $connection2->prepare($sqlCheck);
                                    $resultCheck->execute($dataCheck);
                                } catch (PDOException $e) {
                                    $checkFail = true;
                                }

                                if ($resultCheck->rowCount() != 1 or $checkFail == true) {
                                    $teacherSyncFail = true;
                                    $checkFail = true;
                                    $proceed = false;
                                    $errorList .= $row['courseNameShort'].'.'.$row['classNameShort'].', ';
                                } elseif ($resultCheck->rowCount() == 1 and $checkFail == false) {
                                    $rowCheck = $resultCheck->fetch();
                                    //Remove teachers
                                    $removeFail = false;
                                    try {
                                        $dataCheck = array('gibbonCourseClassID' => $rowCheck['gibbonCourseClassID']);
                                        $sqlCheck = "DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                                        $resultCheck = $connection2->prepare($sqlCheck);
                                        $resultCheck->execute($dataCheck);
                                    } catch (PDOException $e) {
                                        $teacherSyncFail = true;
                                        $removeFail = true;
                                        $proceed = false;
                                    }

                                    if ($removeFail == false) {
                                        //Get teachers from import
                                        $getFail = false;
                                        try {
                                            $dataGet = array('classNameShort' => $row['classNameShort'], 'courseNameShort' => $row['courseNameShort']);
                                            $sqlGet = 'SELECT DISTINCT teacherUsernameList FROM gibbonTTImport WHERE classNameShort=:classNameShort AND courseNameShort=:courseNameShort';
                                            $resultGet = $connection2->prepare($sqlGet);
                                            $resultGet->execute($dataGet);
                                        } catch (PDOException $e) {
                                            $teacherSyncFail = true;
                                            $getFail = true;
                                            $proceed = false;
                                            $errorList .= $row['courseNameShort'].'.'.$row['classNameShort'].', ';
                                        }

                                        if ($getFail == false) {
                                            //Sort teachers into array
                                            $staffs = array();
                                            $count = 0;
                                            while ($rowGet = $resultGet->fetch()) {
                                                $staffTemps = explode(',', $rowGet['teacherUsernameList']);
                                                foreach ($staffTemps as $staffTemp) {
                                                    $staffs[$count] = trim($staffTemp);
                                                    ++$count;
                                                }
                                            }
                                            sort($staffs);
                                            $staffs = array_unique($staffs);

                                            //Add teachers
                                            foreach ($staffs as $staff) {
                                                //Convert username into ID
                                                try {
                                                    $dataConvert = array('username' => $staff);
                                                    $sqlConvert = "SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username AND status='Full'";
                                                    $resultConvert = $connection2->prepare($sqlConvert);
                                                    $resultConvert->execute($dataConvert);
                                                } catch (PDOException $e) {
                                                    $teacherSyncFail = true;
                                                    $proceed = false;
                                                }

                                                if ($resultConvert->rowCount() != 1) {
                                                    $errorList .= $staff.', ';
                                                    $teacherSyncFail = true;
                                                    $proceed = false;
                                                } else {
                                                    $rowConvert = $resultConvert->fetch();

                                                    //Write ID to gibbonCourseClassPerson
                                                    try {
                                                        $dataMake = array('gibbonPersonID' => $rowConvert['gibbonPersonID'], 'gibbonCourseClassID' => $rowCheck['gibbonCourseClassID']);
                                                        $sqlMake = "INSERT INTO gibbonCourseClassPerson SET gibbonPersonID=:gibbonPersonID, gibbonCourseClassID=:gibbonCourseClassID, role='Teacher'";
                                                        $resultMake = $connection2->prepare($sqlMake);
                                                        $resultMake->execute($dataMake);
                                                    } catch (PDOException $e) {
                                                        $classCheckFail = true;
                                                        $proceed = false;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($teacherSyncFail == true) {
                            echo "<div class='error'>";
                            echo sprintf(__('Teacher sync failed. The following classes/teachers (and possibly some others) had problems: %1$s.'), substr($errorList, 0, -2));
                            echo '</div>';
                        } elseif ($teacherSyncFail == false) {
                            echo "<div class='success'>";
                            echo __('The teacher sync was successfully completed: all teachers in the import file were added to the relevant classes in Gibbon.');
                            echo '</div>';
                        }
                    }

                    //UNLOCK TABLES
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                    }

                    //SPIT OUT RESULT
                    echo '<h4>';
                    echo __('Final Decision');
                    echo '</h4>';
                    if ($proceed == false) {
                        echo "<div class='error'>";
                        echo '<b><u>'.__('You cannot proceed. Fix the issues listed above and try again.').'</u></b>';
                        echo '</div>';
                    } elseif ($proceed == true) {
                        echo "<div class='success'>";
                        echo '<b><u>'.sprintf(__('You are ready to go. %1$sClick here to import the timetable. Your old timetable will be obliterated%2$s.'), "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/tt_import.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&step=3'>", '</a>').'</u></b>';
                        echo '</div>';
                    }
                }
            } elseif ($step == 3) {
                ?>
				<h2>
					<?php echo __('Step 3 - Import') ?>
				</h2>
				<?php

                $proceed = true;

                //REMOVE OLD PERIODS
                $ttSyncRemoveFail = false;
                if ($proceed == true) {
                    echo '<h4>';
                    echo __('Remove Old Periods');
                    echo '</h4>';
                    try {
                        $dataDays = array('gibbonTTID' => $gibbonTTID);
                        $sqlDays = 'SELECT * FROM gibbonTTDay WHERE gibbonTTID=:gibbonTTID';
                        $resultDays = $connection2->prepare($sqlDays);
                        $resultDays->execute($dataDays);
                    } catch (PDOException $e) {
                        $ttSyncRemoveFail = true;
                        $proceed = false;
                    }

                    if ($resultDays->rowCount() < 1) {
                        $ttSyncRemoveFail = true;
                        $proceed = false;
                    } else {
                        while ($rowDays = $resultDays->fetch()) {
                            try {
                                $dataRemove = array();
                                $sqlRemove = 'SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID='.$rowDays['gibbonTTDayID'];
                                $resultRemove = $connection2->prepare($sqlRemove);
                                $resultRemove->execute($dataRemove);
                            } catch (PDOException $e) {
                                $ttSyncRemoveFail = true;
                                $proceed = false;
                            }

                            while ($rowRemove = $resultRemove->fetch()) {
                                try {
                                    $dataRemove2 = array();
                                    $sqlRemove2 = 'DELETE FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassID='.$rowRemove['gibbonTTDayRowClassID'];
                                    $resultRemove2 = $connection2->prepare($sqlRemove2);
                                    $resultRemove2->execute($dataRemove2);
                                } catch (PDOException $e) {
                                    $ttSyncRemoveFail = true;
                                    $proceed = false;
                                }
                            }

                            try {
                                $dataRemove3 = array();
                                $sqlRemove3 = 'DELETE FROM gibbonTTDayRowClass WHERE gibbonTTDayID='.$rowDays['gibbonTTDayID'];
                                $resultRemove3 = $connection2->prepare($sqlRemove3);
                                $resultRemove3->execute($dataRemove3);
                            } catch (PDOException $e) {
                                $ttSyncRemoveFail = true;
                                $proceed = false;
                            }
                        }
                    }

                    if ($ttSyncRemoveFail == true) {
                        echo "<div class='error'>";
                        echo __('Removal of old periods failed.');
                        echo '</div>';
                    } elseif ($ttSyncRemoveFail == false) {
                        echo "<div class='success'>";
                        echo __('Removal of old periods was successful.');
                        echo '</div>';
                    }
                }

                //ADD PERIODS
                if ($proceed == true) {
                    echo '<h4>';
                    echo __('Add Periods');
                    echo '</h4>';
                    if ($ttSyncRemoveFail == false) {
                        $ttSyncFail = false;
                        //Get all periods from gibbonTTImport
                        try {
                            $data = array();
                            $sql = 'SELECT DISTINCT courseNameShort, classNameShort, dayName, rowName, spaceName FROM gibbonTTImport ORDER BY courseNameShort, classNameShort, dayName, rowName';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $ttSyncFail = true;
                            $proceed = false;
                        }

                        if ($ttSyncFail == false) {
                            while ($row = $result->fetch()) {
                                //For each period, make a list of teachers
                                $getFail = false;
                                try {
                                    $dataGet = array('classNameShort' => $row['classNameShort'], 'courseNameShort' => $row['courseNameShort'], 'dayName' => $row['dayName'], 'rowName' => $row['rowName']);
                                    $sqlGet = 'SELECT DISTINCT teacherUsernameList FROM gibbonTTImport WHERE classNameShort=:classNameShort AND courseNameShort=:courseNameShort AND dayName=:dayName AND rowName=:rowName';
                                    $resultGet = $connection2->prepare($sqlGet);
                                    $resultGet->execute($dataGet);
                                } catch (PDOException $e) {
                                    $ttSyncFail = true;
                                    $getFail = true;
                                    $proceed = false;
                                }
                                if ($getFail == false) {
                                    $staffs = array();
                                    $count = 0;
                                    while ($rowGet = $resultGet->fetch()) {
                                        $staffTemps = explode(',', $rowGet['teacherUsernameList']);
                                        foreach ($staffTemps as $staffTemp) {
                                            $staffs[$count] = trim($staffTemp);
                                            ++$count;
                                        }
                                    }
                                    sort($staffs);
                                    $staffs = array_unique($staffs);
                                }

                                $addFail = false;
                                try {
                                    $dataRow = array('name1' => $row['dayName'], 'name2' => $row['rowName'], 'gibbonTTID' => $gibbonTTID);
                                    $sqlRow = '(SELECT gibbonTTColumnRowID FROM gibbonTTDay JOIN gibbonTTColumn ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTDay.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDay.name=:name1 AND gibbonTTColumnRow.name=:name2 AND gibbonTTDay.gibbonTTID=:gibbonTTID)';
                                    $resultRow = $connection2->prepare($sqlRow);
                                    $resultRow->execute($dataRow);

                                    $dataDay = array('name' => $row['dayName'], 'gibbonTTID' => $gibbonTTID);
                                    $sqlDay = '(SELECT gibbonTTDayID FROM gibbonTTDay WHERE name=:name AND gibbonTTID=:gibbonTTID)';
                                    $resultDay = $connection2->prepare($sqlDay);
                                    $resultDay->execute($dataDay);

                                    $dataClass = array('nameShort1' => $row['courseNameShort'], 'nameShort2' => $row['classNameShort'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                    $sqlClass = '(SELECT gibbonCourseClassID FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.nameShort=:nameShort1 AND gibbonCourseClass.nameShort=:nameShort2 AND gibbonSchoolYearID=:gibbonSchoolYearID)';
                                    $resultClass = $connection2->prepare($sqlClass);
                                    $resultClass->execute($dataClass);

                                    $dataSpace = array('name' => $row['spaceName']);
                                    $sqlSpace = '(SELECT gibbonSpaceID FROM gibbonSpace WHERE name=:name)';
                                    $resultSpace = $connection2->prepare($sqlSpace);
                                    $resultSpace->execute($dataSpace);
                                } catch (PDOException $e) {
                                    echo $e->getMessage();
                                    $ttSyncFail = true;
                                    $proceed = false;
                                    $addFail = true;
                                }

                                if ($resultRow->rowCount() != 1 and $resultDay->rowCount() != 1 and $resultClass->rowCount() != 1 and $resultSpace->rowCount() != 1) {
                                    $ttSyncFail = true;
                                    $proceed = false;
                                    $addFail = true;
                                } else {
                                    $rowRow = $resultRow->fetch();
                                    $rowDay = $resultDay->fetch();
                                    $rowClass = $resultClass->fetch();
                                    $rowSpace = $resultSpace->fetch();

                                    try {
                                        $sqlInsert = 'INSERT INTO gibbonTTDayRowClass SET gibbonTTColumnRowID='.$rowRow['gibbonTTColumnRowID'].', gibbonTTDayID='.$rowDay['gibbonTTDayID'].', gibbonCourseClassID='.$rowClass['gibbonCourseClassID'].', gibbonSpaceID='.$rowSpace['gibbonSpaceID'];
                                        $resultInsert = $connection2->query($sqlInsert);
                                        $gibbonTTDayRowClassID = $connection2->lastInsertId();
                                    } catch (PDOException $e) {
                                        $ttSyncFail = true;
                                        $proceed = false;
                                        $addFail = true;
                                    }

                                    //Add teacher exceptions
                                    $teachersFail = false;
                                    if ($addFail == false) {
                                        try {
                                            $dataTeachers = array();
                                            $sqlTeachers = "SELECT gibbonPerson.username, gibbonPerson.gibbonPersonID FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND role='Teacher' AND gibbonCourseClassID=".$rowClass['gibbonCourseClassID'];
                                            $resultTeachers = $connection2->prepare($sqlTeachers);
                                            $resultTeachers->execute($dataTeachers);
                                        } catch (PDOException $e) {
                                            $ttSyncFail = true;
                                            $proceed = false;
                                            $teachersFail = true;
                                        }

                                        if ($teachersFail == false) {
                                            while ($rowTeachers = $resultTeachers->fetch()) {
                                                $match = false;
                                                foreach ($staffs as $staff) {
                                                    if ($staff == $rowTeachers['username']) {
                                                        $match = true;
                                                    }
                                                }
                                                if ($match == false) {
                                                    try {
                                                        $dataException = array('gibbonTTDayRowClassID' => $gibbonTTDayRowClassID, 'gibbonPersonID' => $rowTeachers['gibbonPersonID']);
                                                        $sqlException = 'INSERT INTO gibbonTTDayRowClassException SET gibbonTTDayRowClassID=:gibbonTTDayRowClassID, gibbonPersonID=:gibbonPersonID';
                                                        $resultException = $connection2->prepare($sqlException);
                                                        $resultException->execute($dataException);
                                                    } catch (PDOException $e) {
                                                        $ttSyncFail = true;
                                                        $proceed = false;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($ttSyncFail == true) {
                            echo "<div class='error'>";
                            echo __('Add/update of periods from import failed. Parts of your timetable may display correctly, but others may be missing, incomplete or incorrect.');
                            echo '</div>';
                        } elseif ($ttSyncFail == false) {
                            echo "<div class='success'>";
                            echo __('Add/update of periods from import was successful. You may now wish to set long name, learning area and year groups for any new courses created in Step 2.');
                            echo '</div>';
                        }
                    }
                }

                //SPIT OUT RESULT
                echo '<h4>';
                echo __('Final Result');
                echo '</h4>';
                if ($proceed == false) {
                    echo "<div class='error'>";
                    echo '<b><u>'.__('Your input was partially or entirely unsuccessful.').'</u></b>';
                    echo '</div>';
                } elseif ($proceed == true) {
                    echo "<div class='success'>";
                    echo '<b><u>'.__('Success! Your new timetable is in place.').'</u></b>';
                    echo '</div>';
                }
            }
        }
    }
}
