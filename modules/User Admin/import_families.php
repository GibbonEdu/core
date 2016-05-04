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
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Families').'</div>';
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
			<?php echo __($guid, 'This page allows you to import family data from a CSV file, and functions as follows: data contained in the CSV files that is new will be added to the system, whereas data that already exists in the system, but has been changed, will be updated.') ?><br/>
		</p>
		
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_families.php&step=2' ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'Family CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="fileFamily" id="fileFamily" size="chars">
						<script type="text/javascript">
							var fileFamily=new LiveValidation('fileFamily');
							fileFamily.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Parent CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="fileParent" id="fileParent" size="chars">
						<script type="text/javascript">
							var fileParent=new LiveValidation('fileParent');
							fileParent.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Child CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="fileChild" id="fileChild" size="chars">
						<script type="text/javascript">
							var fileChild=new LiveValidation('fileChild');
							fileChild.add(Validate.Presence);
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
			<li><?php echo __($guid, 'The submitted <b><u>family file</u></b> must have the following fields in the following order (* denotes required field).') ?>: 
				<ol>
					<li><b><?php echo __($guid, 'Family Sync Key') ?> *</b> - <?php echo __($guid, 'Unique ID for family, according to source system.') ?></li>
					<li><b><?php echo __($guid, 'Name') ?> *</b> - <?php echo __($guid, 'Name by which family is known.') ?></li>
					<li><b><?php echo __($guid, 'Address Name') ?></b> - <?php echo __($guid, 'Name to appear on written communication to family.') ?></li>
					<li><b><?php echo __($guid, 'Home Address') ?></b> - <?php echo __($guid, 'Unit, Building, Street') ?></li>
					<li><b><?php echo __($guid, 'Home Address (District)') ?></b> - <?php echo __($guid, 'County, State, District') ?></li>
					<li><b><?php echo __($guid, 'Home Address (Country)') ?></b></li>
					<li><b><?php echo __($guid, 'Marital Status') ?></b> - <?php echo __($guid, 'Married, Separated, Divorced, De Facto or Other') ?></li>
					<li><b><?php echo __($guid, 'Home Language - Primary') ?></b></li>
				</ol>
			</li>
			<li><?php echo __($guid, 'The submitted <b><u>parent file</u></b> must have the following fields in the following order (* denotes required field):') ?> 
				<ol>
					<li><b><?php echo __($guid, 'Family Sync Key') ?> *</b> - <?php echo __($guid, 'Unique ID for family, according to source system.') ?></li>
					<li><b><?php echo __($guid, 'Username') ?> *</b> - <?php echo __($guid, 'Parent username') ?>.</li>
					<li><b><?php echo __($guid, 'Contact Priority') ?> *</b> - <?php echo __($guid, '1, 2 or 3 (each family needs one and only one 1).') ?></li>
				</ol>
			</li>
			<li><?php echo __($guid, 'The submitted <b><u>child file</u></b> must have the following fields in the following order (* denotes required field):') ?> 
				<ol>
					<li><b><?php echo __($guid, 'Family Sync Key') ?> *</b> - <?php echo __($guid, 'Unique ID for family, according to source system.') ?></li>
					<li><b><?php echo __($guid, 'Username') ?> *</b> - <?php echo __($guid, 'Child username.') ?></li>
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

        //DEAL WITH FAMILIES
        //Check file type
        if (($_FILES['fileFamily']['type'] != 'text/csv') and ($_FILES['fileFamily']['type'] != 'text/comma-separated-values') and ($_FILES['fileFamily']['type'] != 'text/x-comma-separated-values') and ($_FILES['fileFamily']['type'] != 'application/vnd.ms-excel')) {
            ?>
			<div class='error'>
				<?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted family file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileFamily']['type']) ?><br/>
			</div>
			<?php

        } elseif (($_FILES['fileParent']['type'] != 'text/csv') and ($_FILES['fileParent']['type'] != 'text/comma-separated-values') and ($_FILES['fileParent']['type'] != 'text/x-comma-separated-values') and ($_FILES['fileParent']['type'] != 'application/vnd.ms-excel')) {
            ?>
			<div class='error'>
				<?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted parent file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileParent']['type']) ?><br/>
			</div>
			<?php

        } elseif (($_FILES['fileChild']['type'] != 'text/csv') and ($_FILES['fileChild']['type'] != 'text/comma-separated-values') and ($_FILES['fileChild']['type'] != 'text/x-comma-separated-values') and ($_FILES['fileChild']['type'] != 'application/vnd.ms-excel')) {
            ?>
			<div class='error'>
				<?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted parent file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileChild']['type']) ?><br/>
			</div>
			<?php

        } elseif (($_POST['fieldDelimiter'] == '') or ($_POST['stringEnclosure'] == '')) {
            ?>
			<div class='error'>
				<?php echo __($guid, 'Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
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
                $sql = 'LOCK TABLES gibbonFamily WRITE, gibbonFamilyAdult WRITE, gibbonFamilyChild WRITE, gibbonPerson WRITE';
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

                    //Families
                    $csvFileFamily = $_FILES['fileFamily']['tmp_name'];
                    $handle = fopen($csvFileFamily, 'r');
                    $families = array();
                    $familyCount = 0;
                    $familySuccessCount = 0;
                    while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                        if ($data[0] != '' and $data[1] != '') {
                            $families[$familySuccessCount]['familySync'] = $data[0];
                            $families[$familySuccessCount]['name'] = $data[1];
                            $families[$familySuccessCount]['nameAddress'] = $data[2];
                            $families[$familySuccessCount]['homeAddress'] = $data[3];
                            $families[$familySuccessCount]['homeAddressDistrict'] = $data[4];
                            $families[$familySuccessCount]['homeAddressCountry'] = $data[5];
                            $families[$familySuccessCount]['status'] = $data[6];
                            $families[$familySuccessCount]['languageHomePrimary'] = $data[7];
                            ++$familySuccessCount;
                        } else {
                            echo "<div class='error'>";
                            echo sprintf(__($guid, 'Family with sync key %1$s had some information malformations.'), $data[0]);
                            echo '</div>';
                        }
                        ++$familyCount;
                    }
                    fclose($handle);
                    if ($familySuccessCount == 0) {
                        echo "<div class='error'>";
                        echo __($guid, 'No useful families were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($familySuccessCount < $familyCount) {
                        echo "<div class='error'>";
                        echo __($guid, 'Some families could not be successfully read or used, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($familySuccessCount == $familyCount) {
                        echo "<div class='success'>";
                        echo __($guid, 'All families could be read and used, so the import will proceed.');
                        echo '</div>';
                    } else {
                        echo "<div class='error'>";
                        echo __($guid, 'An unknown family error occured, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    }

                    //Parents
                    $csvFileParent = $_FILES['fileParent']['tmp_name'];
                    $handle = fopen($csvFileParent, 'r');
                    $parents = array();
                    $parentCount = 0;
                    $parentSuccessCount = 0;
                    while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                        if ($data[0] != '' and $data[1] != '' and $data[2] != '') {
                            $parents[$parentSuccessCount]['familySync'] = $data[0];
                            $parents[$parentSuccessCount]['username'] = $data[1];
                            $parents[$parentSuccessCount]['contactPriority'] = $data[2];
                            ++$parentSuccessCount;
                        } else {
                            echo "<div class='error'>";
                            echo sprintf(__($guid, 'Parent with username %1$s had some information malformations.'), $data[1]);
                            echo '</div>';
                        }
                        ++$parentCount;
                    }
                    fclose($handle);
                    if ($parentSuccessCount == 0) {
                        echo "<div class='error'>";
                        echo __($guid, 'No useful parents were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($parentSuccessCount < $parentCount) {
                        echo "<div class='error'>";
                        echo __($guid, 'Some parents could not be successfully read or used, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($parentSuccessCount == $parentCount) {
                        echo "<div class='success'>";
                        echo __($guid, 'All parents could be read and used, so the import will proceed.');
                        echo '</div>';
                    } else {
                        echo "<div class='error'>";
                        echo __($guid, 'An unknown parent error occured, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    }

                    //Children
                    $csvFileChild = $_FILES['fileChild']['tmp_name'];
                    $handle = fopen($csvFileChild, 'r');
                    $children = array();
                    $childCount = 0;
                    $childSuccessCount = 0;
                    while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                        if ($data[0] != '' and $data[1] != '') {
                            $children[$childSuccessCount]['familySync'] = $data[0];
                            $children[$childSuccessCount]['username'] = $data[1];
                            ++$childSuccessCount;
                        } else {
                            echo "<div class='error'>";
                            echo sprintf(__($guid, 'Child with username %1$s had some information malformations.'), $data[1]);
                            echo '</div>';
                        }
                        ++$childCount;
                    }
                    fclose($handle);
                    if ($childSuccessCount == 0) {
                        echo "<div class='error'>";
                        echo __($guid, 'No useful children were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($childSuccessCount < $childCount) {
                        echo "<div class='error'>";
                        echo __($guid, 'Some children could not be successfully read or used, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($childSuccessCount == $childCount) {
                        echo "<div class='success'>";
                        echo __($guid, 'All children could be read and used, so the import will proceed.');
                        echo '</div>';
                    } else {
                        echo "<div class='error'>";
                        echo __($guid, 'An unknown error occured, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    }
                }

                if ($proceed == true) {
                    //CHECK FAMILIES IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
                    echo '<h4>';
                    echo __($guid, 'Update & Insert Families');
                    echo '</h4>';
                    foreach ($families as $family) {
                        $familyProceed = true;
                        try {
                            $data = array('familySync' => $family['familySync']);
                            $sql = 'SELECT * FROM gibbonFamily WHERE familySync=:familySync';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $familyProceed = false;
                        }

                        if ($familyProceed == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'There was an error locating family:').' '.$family['familySync'].'.';
                            echo '</div>';
                        } else {
                            if ($result->rowCount() == 1) {
                                $row = $result->fetch();
                                //UPDATE FAMILY
                                $updateFamilyFail = false;
                                try {
                                    $data = array('name' => $family['name'],  'nameAddress' => $family['nameAddress'],  'homeAddress' => $family['homeAddress'],  'homeAddressDistrict' => $family['homeAddressDistrict'],  'homeAddressCountry' => $family['homeAddressCountry'],  'status' => $family['status'],  'languageHomePrimary' => $family['languageHomePrimary'], 'familySync' => $family['familySync']);
                                    $sql = 'UPDATE gibbonFamily SET name=:name, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, status=:status, languageHomePrimary=:languageHomePrimary WHERE familySync=:familySync';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $updateFamilyFail = true;
                                }

                                //Spit out results
                                if ($updateFamilyFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error updating family:').' '.$family['familySync'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'Family %1$s was successfully updated.'), $family['familySync']);
                                    echo '</div>';
                                }
                            } elseif ($result->rowCount() == 0) {
                                //ADD FAMILY
                                $addFamilyFail = false;
                                try {
                                    $data = array('name' => $family['name'],  'nameAddress' => $family['nameAddress'],  'homeAddress' => $family['homeAddress'],  'homeAddressDistrict' => $family['homeAddressDistrict'],  'homeAddressCountry' => $family['homeAddressCountry'],  'status' => $family['status'],  'languageHomePrimary' => $family['languageHomePrimary'], 'familySync' => $family['familySync']);
                                    $sql = 'INSERT INTO gibbonFamily SET name=:name, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, status=:status, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=NULL, familySync=:familySync';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addFamilyFail = true;
                                }

                                //Spit out results
                                if ($addFamilyFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error creating family:').' '.$family['familySync'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'Family %1$s was successfully created.'), $family['familySync']);
                                    echo '</div>';
                                }
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'There was an error locating family:').' '.$family['familySync'].'.';
                                echo '</div>';
                            }
                        }
                    }

                    //CHECK PARENTS IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
                    echo '<h4>';
                    echo __($guid, 'Update & Insert Parents');
                    echo '</h4>';
                    foreach ($parents as $parent) {
                        $familyProceed = true;
                        try {
                            $data = array('username' => $parent['username'], 'familySync' => $parent['familySync']);
                            $sql = 'SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $familyProceed = false;
                            echo $e->getMessage();
                        }

                        if ($familyProceed == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'There was an error locating parent:').' '.$parent['username'].'.';
                            echo '</div>';
                        } else {
                            if ($result->rowCount() == 1) {
                                $row = $result->fetch();
                                //UPDATE PARENT
                                $updateFamilyFail = false;
                                try {
                                    $data = array('familySync' => $parent['familySync'], 'username' => $parent['username'], 'contactPriority' => $parent['contactPriority']);
                                    $sql = 'UPDATE gibbonFamilyAdult SET contactPriority=:contactPriority WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $updateFamilyFail = true;
                                }

                                //Spit out results
                                if ($updateFamilyFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error updating parent:').' '.$parent['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'Parent %1$s was successfully updated.'), $parent['username']);
                                    echo '</div>';
                                }
                            } elseif ($result->rowCount() == 0) {
                                //ADD PARENT
                                $addFamilyFail = false;
                                try {
                                    $data = array('familySync' => $parent['familySync'], 'username' => $parent['username'], 'contactPriority' => $parent['contactPriority']);
                                    $sql = "INSERT INTO gibbonFamilyAdult SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync), contactPriority=:contactPriority, childDataAccess='Y', contactCall='Y', contactSMS='Y', contactEmail='Y', contactMail='Y'";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addFamilyFail = true;
                                }

                                //Spit out results
                                if ($addFamilyFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error creating parent:').' '.$parent['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'Family %1$s was successfully created.'), $parent['username']);
                                    echo '</div>';
                                }
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'There was an error locating family:').' '.$parent['username'].'.';
                                echo '</div>';
                            }
                        }
                    }

                    //CHECK STUDENTS IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
                    echo '<h4>';
                    echo __($guid, 'Update & Insert Students');
                    echo '</h4>';
                    foreach ($children as $child) {
                        $familyProceed = true;
                        try {
                            $data = array('username' => $child['username'], 'familySync' => $child['familySync']);
                            $sql = 'SELECT * FROM gibbonFamilyChild WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $familyProceed = false;
                            echo $e->getMessage();
                        }

                        if ($familyProceed == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'There was an error locating student:').' '.$child['username'].'.';
                            echo '</div>';
                        } else {
                            if ($result->rowCount() == 1) {
                                $row = $result->fetch();
                                //UPDATE STUDENT
                                $updateFamilyFail = false;

                                //NOTHING TO UPDATE YET, MAY NEED THIS ONE DAY
                                /*try {
                                    $data=array("familySync"=>$child["familySync"], "username"=>$child["username"]); 
                                    $sql="UPDATE gibbonFamilyAdult SET WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
                                    $result=$connection2->prepare($sql);
                                    $result->execute($data);
                                }
                                catch(PDOException $e) { 
                                    $updateFamilyFail=TRUE ;
                                }
                                
                                //Spit out results
                                if ($updateFamilyFail==TRUE) {
                                    print "<div class='error'>" ;
                                        print __($guid, "There was an error student:") . " " . $child["username"] . "." ;
                                    print "</div>" ;
                                }
                                else {
                                    print "<div class='success'>" ;
                                        print sprintf(__($guid, 'Student %1$s was successfully updated.'), $child["username"]) ;
                                    print "</div>" ;
                                }*/

                                echo "<div class='success'>";
                                echo sprintf(__($guid, 'Student %1$s was successfully updated.'), $child['username']);
                                echo '</div>';
                            } elseif ($result->rowCount() == 0) {
                                //ADD STUDENT
                                $addFamilyFail = false;
                                try {
                                    $data = array('familySync' => $child['familySync'], 'username' => $child['username']);
                                    $sql = 'INSERT INTO gibbonFamilyChild SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $addFamilyFail = true;
                                }

                                //Spit out results
                                if ($addFamilyFail == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There was an error creating student:').' '.$child['username'].'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='success'>";
                                    echo sprintf(__($guid, 'Student %1$s was successfully created.'), $child['username']);
                                    echo '</div>';
                                }
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'There was an error locating student:').' '.$child['username'].'.';
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
?>