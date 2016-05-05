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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/import_studentEnrolment.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Student Enrolment').'</div>';
    echo '</div>';

    $step = null;
    if (isset($_GET['step'])) {
        $step = $_GET['step'];
    }
    if ($step == '') {
        $step = 1;
    } elseif (($step != 1) and ($step != 2)) {
        $step = 1;
    }

    //STEP 1, SELECT TERM
    if ($step == 1) {
        ?>
		<h2>
			<?php echo __($guid, 'Step 1 - Select CSV Files') ?>
		</h2>
		<p>
			<?php echo __($guid, 'This page allows you to import student enrolment data from a CSV file, in one of two modes: 1) Sync - the import file includes all students. The system will take the import and delete enrolment for any existing students not present in the file, whilst importing new enrolments into the system, or 2) Import - the import file includes only student enrolments you wish to add to the system. Select the CSV file you wish to use for the synchronise operation.') ?><br/>
		</p>
		
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_studentEnrolment.php&step=2' ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td> 
						<b>Mode *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="mode" id="mode" class="standardWidth">
							<option value="sync"><?php echo __($guid, 'Sync') ?></option>
							<option value="import"><?php echo __($guid, 'Import') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars">
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Field Delimiter') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'String Enclosure') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		
		
		
		<h4>
			<?php echo __($guid, 'Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php echo __($guid, 'THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php echo __($guid, 'You may only submit CSV files.') ?></li>
			<li><?php echo __($guid, 'Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php echo __($guid, 'Your import should only include all current students.') ?></li>
			<li><?php echo __($guid, 'The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
				<ol>
					<li><b><?php echo __($guid, 'Username') ?></b> - <?php echo __($guid, 'Must be unique.') ?></li>
					<li><b><?php echo __($guid, 'Roll Group') ?></b> - <?php echo __($guid, 'Roll group short name, as set in School Admim. Must already exist.') ?></li>
					<li><b><?php echo __($guid, 'Year Group') ?></b> - <?php echo __($guid, 'Year group short name, as set in School Admin. Must already exist') ?></li>
					<li><b><?php echo __($guid, 'Roll Order') ?></b> - <?php echo __($guid, 'Must be unique to roll group if set.') ?></li>
				</ol>
			</li>
			<li><?php echo __($guid, 'Do not include a header row in the CSV files.') ?></li>
		</ol>
	<?php

    } elseif ($step == 2) {
        ?>
		<h2>
			<?php echo __($guid, 'Step 2 - Data Check & Confirm') ?>
		</h2>
		<?php

        //Check file type
        if (($_FILES['file']['type'] != 'text/csv') and ($_FILES['file']['type'] != 'text/comma-separated-values') and ($_FILES['file']['type'] != 'text/x-comma-separated-values') and ($_FILES['file']['type'] != 'application/vnd.ms-excel') and ($_FILES['file']['type'] != 'application/csv')) {
            ?>
			<div class='error'>
				<?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
			</div>
			<?php

        } elseif (($_POST['fieldDelimiter'] == '') or ($_POST['stringEnclosure'] == '')) {
            ?>
			<div class='error'>
				<?php echo __($guid, 'Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
			</div>
			<?php

        } elseif ($_POST['mode'] != 'sync' and $_POST['mode'] != 'import') {
            ?>
			<div class='error'>
				<?php echo __($guid, 'Import cannot proceed, as the "Mode" field have been left blank.') ?><br/>
			</div>
			<?php

        } else {
            $proceed = true;
            $mode = $_POST['mode'];

            if ($mode == 'sync') { //SYNC			
                //PREPARE TABLES
                echo '<h4>';
                echo __($guid, 'Prepare Database Tables');
                echo '</h4>';
                //Lock tables
                $lockFail = false;
                try {
                    $sql = 'LOCK TABLES gibbonStudentEnrolment WRITE, gibbonRollGroup WRITE, gibbonYearGroup WRITE, gibbonPerson WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $lockFail = true;
                    $proceed = false;
                }
                if ($lockFail == true) {
                    echo "<div class='error'>";
                    echo __($guid, 'The database could not be locked for use.');
                    echo '</div>';
                } elseif ($lockFail == false) {
                    echo "<div class='success'>";
                    echo __($guid, 'The database was successfully locked.');
                    echo '</div>';
                }

                if ($lockFail == false) {
                    //READ IN DATA
                    if ($proceed == true) {
                        echo '<h4>';
                        echo __($guid, 'File Import');
                        echo '</h4>';
                        $importFail = false;
                        $csvFile = $_FILES['file']['tmp_name'];
                        $handle = fopen($csvFile, 'r');
                        $users = array();
                        $userCount = 0;
                        $userSuccessCount = 0;
                        while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                            if ($data[0] != '' and $data[1] != '' and $data[2] != '') {
                                $users[$userSuccessCount]['username'] = $data[0];
                                $users[$userSuccessCount]['rollGroup'] = $data[1];
                                $users[$userSuccessCount]['yearGroup'] = $data[2];
                                $users[$userSuccessCount]['rollOrder'] = $data[3];
                                if ($data[3] == '' or is_null($data[3])) {
                                    $users[$userSuccessCount]['rollOrder'] = null;
                                }
                                ++$userSuccessCount;
                            } else {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'Student with username %1$s had some information malformations.'), $data[7]);
                                echo '</div>';
                            }
                            ++$userCount;
                        }
                        fclose($handle);
                        if ($userSuccessCount == 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'No useful students were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount < $userCount) {
                            echo "<div class='error'>";
                            echo __($guid, 'Some students could not be successfully read or used, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount == $userCount) {
                            echo "<div class='success'>";
                            echo __($guid, 'All students could be read and used, so the import will proceed.');
                            echo '</div>';
                        } else {
                            echo "<div class='error'>";
                            echo __($guid, 'An unknown error occured, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        }
                    }

                    if ($proceed == true) {
                        //SET USERS NOT IN IMPORT TO LEFT
                        echo '<h4>';
                        echo __($guid, 'Delete All Enrolments');
                        echo '</h4>';
                        $deleteAllFail = false;
                        try {
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sql = 'DELETE FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $deleteAllFail = true;
                        }

                        if ($deleteAllFail == true) {
                            echo "<div class='error'>";
                            echo __($guid, 'An error was encountered in deleting all enrolments.');
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo __($guid, 'All enrolments were deleted.');
                            echo '</div>';
                        }

                        if ($deleteAllFail == false) {
                            echo '<h4>';
                            echo __($guid, 'Enrol All Students');
                            echo '</h4>';
                            foreach ($users as $user) {
                                $addUserFail = false;
                                try {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'username' => $user['username'], 'rollGroup' => $user['rollGroup'], 'yearGroup' => $user['yearGroup'], 'rollOrder' => $user['rollOrder']);
                                    $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonRollGroupID=(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort=:rollGroup AND gibbonSchoolYearID=:gibbonSchoolYearID2), gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup), rollOrder=:rollOrder';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addUserFail = true;
                                }

                                //Spit out results
                                if ($addUserFail == true) {
                                    echo "<div class='error'>";

                                    echo __($guid, 'There was an error enroling student:').' '.$user['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'User %1$s was successfully enroled.'), $user['username']);
                                    echo '</div>';
                                }
                            }
                        }
                    }

                    //UNLOCK TABLES
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                    }
                }
            } elseif ($mode == 'import') { //IMPORT
                //PREPARE TABLES
                echo '<h4>';
                echo __($guid, 'Prepare Database Tables');
                echo '</h4>';
                //Lock tables
                $lockFail = false;
                try {
                    $sql = 'LOCK TABLES gibbonStudentEnrolment WRITE, gibbonRollGroup WRITE, gibbonYearGroup WRITE, gibbonPerson WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $lockFail = true;
                    $proceed = false;
                }
                if ($lockFail == true) {
                    echo "<div class='error'>";
                    echo __($guid, 'The database could not be locked for use.');
                    echo '</div>';
                } elseif ($lockFail == false) {
                    echo "<div class='success'>";
                    echo __($guid, 'The database was successfully locked.');
                    echo '</div>';
                }

                if ($lockFail == false) {
                    //READ IN DATA
                    if ($proceed == true) {
                        echo '<h4>';
                        echo __($guid, 'File Import');
                        echo '</h4>';
                        $importFail = false;
                        $csvFile = $_FILES['file']['tmp_name'];
                        $handle = fopen($csvFile, 'r');
                        $users = array();
                        $userCount = 0;
                        $userSuccessCount = 0;
                        while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                            if ($data[0] != '' and $data[1] != '' and $data[2] != '') {
                                $users[$userSuccessCount]['username'] = $data[0];
                                $users[$userSuccessCount]['rollGroup'] = $data[1];
                                $users[$userSuccessCount]['yearGroup'] = $data[2];
                                $users[$userSuccessCount]['rollOrder'] = $data[3];
                                if ($data[3] == '' or is_null($data[3])) {
                                    $users[$userSuccessCount]['rollOrder'] = null;
                                }
                                ++$userSuccessCount;
                            } else {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'Student with username %1$s had some information malformations.'), $data[7]);
                                echo '</div>';
                            }
                            ++$userCount;
                        }
                        fclose($handle);
                        if ($userSuccessCount == 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'No useful students were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount < $userCount) {
                            echo "<div class='error'>";
                            echo __($guid, 'Some students could not be successfully read or used, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount == $userCount) {
                            echo "<div class='success'>";
                            echo __($guid, 'All students could be read and used, so the import will proceed.');
                            echo '</div>';
                        } else {
                            echo "<div class='error'>";
                            echo __($guid, 'An unknown error occured, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        }
                    }

                    if ($proceed == true) {
                        echo '<h4>';
                        echo __($guid, 'Enrol All Students');
                        echo '</h4>';
                        foreach ($users as $user) {
                            $addUserFail = false;
                            //Check for existing enrolment
                            try {
                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'username' => $user['username']);
                                $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username)';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $addUserFail = true;
                            }

                            if ($result->rowCount() > 0) {
                                $addUserFail = true;
                                echo "<div class='error'>";
                                echo __($guid, 'There was an error enroling student:').' '.$user['username'].'.';
                                echo '</div>';
                            } else {
                                try {
                                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'username' => $user['username'], 'rollGroup' => $user['rollGroup'], 'yearGroup' => $user['yearGroup'], 'rollOrder' => $user['rollOrder']);
                                    $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonRollGroupID=(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort=:rollGroup AND gibbonSchoolYearID=:gibbonSchoolYearID2), gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup), rollOrder=:rollOrder';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo $e->getMessage();
                                    $addUserFail = true;
                                }

                                //Spit out results
                                if ($addUserFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error enroling student:').' '.$user['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'User %1$s was successfully enroled.'), $user['username']);
                                    echo '</div>';
                                }
                            }
                        }
                    }

                    //UNLOCK TABLES
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                    }
                }
            }
        }
    }
}
?>