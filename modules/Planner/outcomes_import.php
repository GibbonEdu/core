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

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_import.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check access based on privileges in Manage Outcomes
    $permission = false;
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/outcomes.php', $connection2);
    if ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
        $permission = 'Learning Area';
    } elseif ($highestAction == 'Manage Outcomes_viewEditAll') {
        $permission = 'School';
    }

    if ($permission != 'Learning Area' and $permission != 'School') {
        //Acess denied due to privileges in Manage Outcomes
        echo "<div class='error'>";
        echo __($guid, 'You do not have access to this action.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Outcomes').'</div>';
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

        $yearGroups = getYearGroups($connection2);

        //STEP 1, SELECT TERM
        if ($step == 1) {
            ?>
			<h2>
				<?php echo __($guid, 'Step 1 - Select CSV Files') ?>
			</h2>
			<p>
				<?php echo __($guid, 'This page allows you to import outcomes from a CSV file, based on your access level in Manage Outcomes.') ?><br/>
			</p>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/outcomes_import.php&step=2' ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
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
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
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
				<li><?php echo __($guid, 'The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
					<ol>
						<?php
                        if ($permission == 'Learning Area') {
                            echo '<li><b>'.__($guid, 'Scope').' *</b> - '.__($guid, 'Learning Area').'</li>';
                        } elseif ($permission == 'School') {
                            echo '<li><b>'.__($guid, 'Scope').' *</b> - '.__($guid, 'School or Learning Area').'</li>';
                        }
            ?>
						<li><b><?php echo __($guid, 'Learning Area') ?></b> - <?php echo __($guid, 'Learning Area name, or blank if scope is School') ?></li>
						<li><b><?php echo __($guid, 'Name') ?> *</b></li>
						<li><b><?php echo __($guid, 'Short Name') ?> *</b></li>
						<li><b><?php echo __($guid, 'Category') ?></b></li>
						<li><b><?php echo __($guid, 'Description') ?></b></li>
						<?php
                        $yearGroupList = '';
            for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
                $yearGroupList .= __($guid, $yearGroups[($i + 1)]).', ';
            }
            $yearGroupList = substr($yearGroupList, 0, -2);
            ?>
						<li><b><?php echo __($guid, 'Year Groups') ?></b> - <?php echo sprintf(__($guid, 'Comma separated list, e.g: %1$s'), '<i>'.$yearGroupList.'</i>') ?></li>
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

            } else {
                $proceed = true;

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
                    if ($data[0] != '' and $data[2] != '' and $data[3] != '') {
                        $users[$userSuccessCount]['scope'] = '';
                        if (isset($data[0])) {
                            $users[$userSuccessCount]['scope'] = $data[0];
                        }
                        $users[$userSuccessCount]['learningArea'] = '';
                        if (isset($data[1])) {
                            $users[$userSuccessCount]['learningArea'] = $data[1];
                        }
                        $users[$userSuccessCount]['name'] = '';
                        if (isset($data[2])) {
                            $users[$userSuccessCount]['name'] = $data[2];
                        }
                        $users[$userSuccessCount]['nameShort'] = '';
                        if (isset($data[3])) {
                            $users[$userSuccessCount]['nameShort'] = $data[3];
                        }
                        $users[$userSuccessCount]['category'] = '';
                        if (isset($data[4])) {
                            $users[$userSuccessCount]['category'] = $data[4];
                        }
                        $users[$userSuccessCount]['description'] = '';
                        if (isset($data[5])) {
                            $users[$userSuccessCount]['description'] = $data[5];
                        }
                        $users[$userSuccessCount]['yearGroups'] = '';
                        if (isset($data[6])) {
                            $users[$userSuccessCount]['yearGroups'] = $data[6];
                        }

                        ++$userSuccessCount;
                    } else {
                        echo "<div class='error'>";
                        echo sprintf(__($guid, 'Outcome with name %1$s had some information malformations.'), $data[2]);
                        echo '</div>';
                    }
                    ++$userCount;
                }
                fclose($handle);
                if ($userSuccessCount == 0) {
                    echo "<div class='error'>";
                    echo __($guid, 'No useful outcomes were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                    echo '</div>';
                    $proceed = false;
                } elseif ($userSuccessCount < $userCount) {
                    echo "<div class='error'>";
                    echo __($guid, 'Some outcomes could not be successfully read or used, so the import will be aborted.');
                    echo '</div>';
                    $proceed = false;
                } elseif ($userSuccessCount == $userCount) {
                    echo "<div class='success'>";
                    echo __($guid, 'All outcomes could be read and used, so the import will proceed.');
                    echo '</div>';
                } else {
                    echo "<div class='error'>";
                    echo __($guid, 'An unknown error occured, so the import will be aborted.');
                    echo '</div>';
                    $proceed = false;
                }
            }

            if ($proceed == true) {
                foreach ($users as $user) {
                    //ADD USER
                    $addUserFail = false;

                    //Check permisison
                    if ($user['scope'] == 'School' and $permission != 'School') {
                        echo "<div class='error'>";
                        echo __($guid, 'There was an error creating outcome:').' '.$user['name'].'.';
                        echo '</div>';
                    } else {
                        $gibbonDepartmentID = null;
                        if ($user['learningArea'] != '') {
                            try {
                                $data = array('learningArea' => $user['learningArea']);
                                $sql = 'SELECT gibbonDepartmentID FROM gibbonDepartment WHERE name=:learningArea';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }
                            if ($result->rowCount() == 1) {
                                $row = $result->fetch();
                                $gibbonDepartmentID = $row['gibbonDepartmentID'];
                            }
                        }
                        $gibbonYearGroupIDList = '';
                        $yearGroupsSelected = explode(',', $user['yearGroups']);
                        foreach ($yearGroupsSelected as $yearGroupSelected) {
                            for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
                                if (trim($yearGroupSelected) == $yearGroups[($i + 1)]) {
                                    $gibbonYearGroupIDList .= $yearGroups[$i].',';
                                }
                            }
                        }
                        if ($gibbonYearGroupIDList != '') {
                            $gibbonYearGroupIDList = substr($gibbonYearGroupIDList, 0, -1);
                        }

                        //Add smart year group ID fill here...
                        try {
                            $data = array('scope' => $user['scope'], 'gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $user['name'], 'nameShort' => $user['nameShort'], 'category' => $user['category'], 'description' => $user['description'], 'gibbonYearGroupIDList' => $gibbonYearGroupIDList);
                            $sql = 'INSERT INTO gibbonOutcome SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $addUserFail = true;
                            echo $e->getMessage();
                        }

                        //Spit out results
                        if ($addUserFail == true) {
                            echo "<div class='error'>";
                            echo __($guid, 'There was an error creating outcome:').' '.$user['name'].'.';
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo sprintf(__($guid, 'Outcome %1$s was successfully created.'), $user['name']);
                            echo '</div>';
                        }
                    }
                }
            }
        }
    }
}
?>