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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/import_userPhotos.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Users').'</div>';
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
        echo __('Step 1 - Select ZIP File');
        echo '</h2>';
        echo '<p>';
        echo __('This page allows you to bulk import user photos, in the form of a ZIP file contain images named with individual usernames. See notes below for sizing information.');
        echo '</p>';

        $form = Form::create('importUserPhotos', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_userPhotos.php&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $_SESSION[$guid]['gibbonSchoolYearID']);

        $row = $form->addRow();
            $row->addLabel('file', __('ZIP File'))->description(__('See Notes below for specification.'));
            $row->addFileUpload('file')->isRequired();

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
            <li><?php echo __($guid, 'You may only submit ZIP files.') ?></li>
            <li><?php echo __($guid, 'Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
            <li><?php echo __($guid, 'Please note the following requirements for images in preparing your ZIP file:') ?></li>
                <ol>
                    <li><b><?php echo __($guid, 'File Name') ?></b> - <?php echo __($guid, 'File name of each image must be username plus extension, e.g. astudent.jpg') ?></li>
                    <li><b><?php echo __($guid, 'Folder') ?> *</b> - <?php echo __($guid, 'The ZIP file must not contain any folders, only files.') ?></li>
                    <li><b><?php echo __($guid, 'File Type') ?> *</b> - <?php echo __($guid, 'Images must be formatted as JPG or PNG.') ?></li>
                    <li><b><?php echo __($guid, 'Image Size') ?> *</b> - <?php echo __($guid, 'Displayed at 240px by 320px.') ?></li>
                    <li><b><?php echo __($guid, 'Size Range') ?> *</b> - <?php echo __($guid, 'Accepts images up to 360px by 480px.') ?></li>
                    <li><b><?php echo __($guid, 'Aspect Ratio Range') ?> *</b> - <?php echo __($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?></li>
                </ol>
            </li>
        </ol>
    <?php

    } elseif ($step == 2) {
        ?>
        <h2>
            <?php echo __($guid, 'Step 2 - Data Check & Confirm') ?>
        </h2>
        <?php

        //Check file type
        if ($_FILES['file']['type'] != 'application/zip' and $_FILES['file']['type'] != 'application/x-zip-compressed') {
            ?>
            <div class='error'>
                <?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a ZIP file.'), $_FILES['file']['type']) ?><br/>
            </div>
            <?php

        } else {
            $proceed = true;

            //PREPARE TABLES
            echo '<h4>';
            echo __($guid, 'Prepare Database Tables');
            echo '</h4>';
            //Lock tables
            $lockFail = false;
            try {
                $sql = 'LOCK TABLES gibbonPerson WRITE';
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
                $path = $_FILES['file']['tmp_name'];
                $path = str_replace('\\', '/', $path);
                $zip = new ZipArchive();
                $time = time();

                $year = date('Y', $time);
                $month = date('m', $time);

                //Check for folder in uploads based on today's date
                $pathTemp = $_SESSION[$guid]['absolutePath'];
                if (is_dir($pathTemp.'/uploads/'.$year.'/'.$month) == false) {
                    mkdir($pathTemp.'/uploads/'.$year.'/'.$month, 0777, true);
                }

                if ($zip->open($path) === true) { //Success
                    for ($i = 0; $i < $zip->numFiles; ++$i) {
                        if (substr($zip->getNameIndex($i), 0, 8) != '__MACOSX') {
                            $filename = $zip->getNameIndex($i);

                            //Check file type
                            $fileTypeFail = false;
                            if (strtolower(substr($filename, -4, 4)) != '.jpg' and strtolower(substr($filename, -4, 4)) != '.png') {
                                $fileTypeFail = true;
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'Image %1$s does not appear to be, formatted as JPG or PNG.'), $_FILES['file']['type']);
                                echo '</div>';
                            }

                            if ($fileTypeFail == false) {
                                //Extract username from file name, and check existence of user
                                $userCheckFail = false;
                                $username = substr($filename, 0, -4);

                                try {
                                    $data = array('username' => $username);
                                    $sql = 'SELECT username FROM gibbonPerson WHERE username=:username';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $userCheckFail = true;
                                }
                                if ($result->rowCount() != 1) {
                                    $userCheckFail = true;
                                }

                                if ($userCheckFail) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error locating user:').' '.$username.'.';
                                    echo '</div>';
                                } else {
                                    if ($userCheckFail == false) {
                                        //Upload file with unique name
                                        $fileUploadFail = false;
                                        $filePath = '';
                                        $unique = false;
                                        $count = 0;
                                        while ($unique == false and $count < 100) {
                                            if ($count == 0) {
                                                $filePath = 'uploads/'.$year.'/'.$month.'/'.$username.strrchr($filename, '.');
                                            } else {
                                                $filePath = 'uploads/'.$year.'/'.$month.'/'.$username."_$count".strrchr($filename, '.');
                                            }
                                            if (!(file_exists($_SESSION[$guid]['absolutePath'].'/'.$filePath))) {
                                                $unique = true;
                                            }
                                            ++$count;
                                        }
                                        if (!(@copy('zip://'.$path.'#'.$filename, $filePath))) {
                                            $fileUploadFail = true;
                                            echo "<div class='error'>";
                                            echo __($guid, 'There was an error uploading photo for user:').' '.$username.'.';
                                            echo '</div>';
                                        }

                                        if ($fileUploadFail == false) {
                                            //Check image properties
                                            $imageFail = false;

                                            $size = getimagesize($_SESSION[$guid]['absolutePath'].'/'.$filePath);
                                            $width = $size[0];
                                            $height = $size[1];
                                            $aspect = $height / $width;
                                            if ($width > 360 or $height > 480 or $aspect < 1.2 or $aspect > 1.4) {
                                                $imageFail = true;
                                                //Report error
                                                echo "<div class='error'>";
                                                echo __($guid, 'There was an error in the sizing of the photo for user:').' '.$username.'.';
                                                echo '</div>';
                                            }

                                            if ($imageFail == false) {
                                                //Update gibbonPerson
                                                $updateFail = false;
                                                try {
                                                    $data = array('image_240' => $filePath, 'username' => $username);
                                                    $sql = 'UPDATE gibbonPerson SET image_240=:image_240 WHERE username=:username';
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>";
                                                    echo __($guid, 'There was an error updating user:').' '.$username.'.';
                                                    echo '</div>';
                                                    $updateFail = true;
                                                }

                                                //Spit out results
                                                if ($updateFail == false) {
                                                    echo "<div class='success'>";
                                                    echo sprintf(__($guid, 'User %1$s was successfully updated.'), $username);
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $zip->close();
                } else {    //Error
                    echo "<div class='error'>";
                    echo __($guid, 'The import file could not be decompressed.');
                    echo '</div>';
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
?>
