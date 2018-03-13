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

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/import_staff.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Staff').'</div>';
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
        echo '<h2>';
        echo __('Step 1 - Select CSV Files');
        echo '</h2>';
        echo '<p>';
        echo __('This page allows you to import staff records from a CSV file, in one of two modes: 1) Sync - the import file includes all staff. The system will take the import and delete records for any existing Staff not present in the file, whilst importing new records into the system, or 2) Import - the import file includes only staff you wish to add to the system. Select the CSV file you wish to use for the synchronise operation.');
        echo '</p>';

        $form = Form::create('importStaff', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_staff.php&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('mode', __('Mode'));
            $row->addSelect('mode')->fromArray(array('import' => __('Import'), 'sync' => __('Sync')))->isRequired();

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
        ?>

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
                    <li><b><?php echo __($guid, 'Username') ?> *</b> - <?php echo __($guid, 'Must be unique.') ?></li>
                    <li><b><?php echo __($guid, 'Type') ?> *</b> - <?php echo __($guid, 'Teaching or Support') ?></li>
                    <li><b><?php echo __($guid, 'Initials') ?></b> - <?php echo __($guid, 'Must be unique if set.') ?></li>
                    <li><b><?php echo __($guid, 'Job Title') ?></b></li>
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
                    $sql = 'LOCK TABLES gibbonStaff WRITE, gibbonPerson WRITE';
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
                            if ($data[1] != '' and $data[2] != '') {
                                $users[$userSuccessCount]['username'] = $data[0];
                                $users[$userSuccessCount]['type'] = $data[1];
                                $users[$userSuccessCount]['initials'] = $data[2];
                                if ($data[2] == '' or is_null($data[2])) {
                                    $users[$userSuccessCount]['initials'] = null;
                                }
                                $users[$userSuccessCount]['jobTitle'] = $data[3];
                                ++$userSuccessCount;
                            } else {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'Staff with username %1$s had some information malformations.'), $data[7]);
                                echo '</div>';
                            }
                            ++$userCount;
                        }
                        fclose($handle);
                        if ($userSuccessCount == 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'No useful staff were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount < $userCount) {
                            echo "<div class='error'>";
                            echo __($guid, 'Some staff could not be successfully read or used, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount == $userCount) {
                            echo "<div class='success'>";
                            echo __($guid, 'All staff could be read and used, so the import will proceed.');
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
                        echo __($guid, 'Delete All Staff');
                        echo '</h4>';
                        $deleteAllFail = false;
                        try {
                            $data = array();
                            $sql = 'DELETE FROM gibbonStaff';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $deleteAllFail = true;
                        }

                        if ($deleteAllFail == true) {
                            echo "<div class='error'>";
                            echo __($guid, 'An error was encountered in deleting all staff.');
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo __($guid, 'All staff were deleted.');
                            echo '</div>';
                        }

                        if ($deleteAllFail == false) {
                            echo '<h4>';
                            echo __($guid, 'Import All Staff');
                            echo '</h4>';
                            foreach ($users as $user) {
                                $addUserFail = false;
                                try {
                                    $data = array('username' => $user['username'], 'type' => $user['type'], 'initials' => $user['initials'], 'jobTitle' => $user['jobTitle']);
                                    $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), type=:type, initials=:initials, jobTitle=:jobTitle';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addUserFail = true;
                                }

                                //Spit out results
                                if ($addUserFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error adding staff:').' '.$user['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'User %1$s was successfully added.'), $user['username']);
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
                    $sql = 'LOCK TABLES gibbonStaff WRITE, gibbonPerson WRITE';
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
                            if ($data[1] != '' and $data[2] != '') {
                                $users[$userSuccessCount]['username'] = $data[0];
                                $users[$userSuccessCount]['type'] = $data[1];
                                $users[$userSuccessCount]['initials'] = $data[2];
                                if ($data[2] == '' or is_null($data[2])) {
                                    $users[$userSuccessCount]['initials'] = null;
                                }
                                $users[$userSuccessCount]['jobTitle'] = $data[3];
                                ++$userSuccessCount;
                            } else {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'Staff with username %1$s had some information malformations.'), $data[7]);
                                echo '</div>';
                            }
                            ++$userCount;
                        }
                        fclose($handle);
                        if ($userSuccessCount == 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'No useful staff were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount < $userCount) {
                            echo "<div class='error'>";
                            echo __($guid, 'Some staff could not be successfully read or used, so the import will be aborted.');
                            echo '</div>';
                            $proceed = false;
                        } elseif ($userSuccessCount == $userCount) {
                            echo "<div class='success'>";
                            echo __($guid, 'All staff could be read and used, so the import will proceed.');
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
                        echo __($guid, 'Import All Staff');
                        echo '</h4>';
                        foreach ($users as $user) {
                            $addUserFail = false;
                            //Check for existing record
                            try {
                                $data = array('username' => $user['username']);
                                $sql = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username)';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $addUserFail = true;
                            }

                            if ($result->rowCount() > 0) {
                                $addUserFail = true;
                                echo "<div class='error'>";
                                echo __($guid, 'There was an error adding staff:').' '.$user['username'].'.';
                                echo '</div>';
                            } else {
                                try {
                                    $data = array('username' => $user['username'], 'type' => $user['type'], 'initials' => $user['initials'], 'jobTitle' => $user['jobTitle']);
                                    $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), type=:type, initials=:initials, jobTitle=:jobTitle';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addUserFail = true;
                                }

                                //Spit out results
                                if ($addUserFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error adding staff:').' '.$user['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'User %1$s was successfully added.'), $user['username']);
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
