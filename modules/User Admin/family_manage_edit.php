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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Family').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFamilyID = $_GET['gibbonFamilyID'];
    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    if ($gibbonFamilyID == '') {
        echo '<h1>';
        echo __($guid, 'Edit Family');
        echo '</h1>';
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyID' => $gibbonFamilyID);
            $sql = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo '<h1>';
            echo 'Edit Family';
            echo '</h1>';
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_editProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2> 
							<h3>
								<?php echo __($guid, 'General Information') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Family Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=100 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Status') ?></b><br/>
						</td>
						<td class="right">
							<select name="status" id="status" class="standardWidth">
								<option <?php if ($row['status'] == 'Married') { echo 'selected '; } ?>value="Married"><?php echo __($guid, 'Married') ?></option>
								<option <?php if ($row['status'] == 'Separated') { echo 'selected '; } ?>value="Separated"><?php echo __($guid, 'Separated') ?></option>
								<option <?php if ($row['status'] == 'Divorced') { echo 'selected '; } ?>value="Divorced"><?php echo __($guid, 'Divorced') ?></option>
								<option <?php if ($row['status'] == 'De Facto') { echo 'selected '; } ?>value="De Facto"><?php echo __($guid, 'De Facto') ?></option>
								<option <?php if ($row['status'] == 'Other') { echo 'selected '; } ?>value="Other"><?php echo __($guid, 'Other') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Language - Primary') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageHomePrimary" id="languageHomePrimary" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageHomePrimary'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Language - Secondary') ?></b><br/>
						</td>
						<td class="right">
							<select name="languageHomeSecondary" id="languageHomeSecondary" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($row['languageHomeSecondary'] == $rowSelect['name']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Address Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Formal name to address parents with.') ?></span>
						</td>
						<td class="right">
							<input name="nameAddress" id="nameAddress" maxlength=100 value="<?php echo $row['nameAddress'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameAddress=new LiveValidation('nameAddress');
								nameAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
						</td>
						<td class="right">
							<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php echo $row['homeAddress'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address (District)') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
						</td>
						<td class="right">
							<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<?php echo $row['homeAddressDistrict'] ?>" type="text" class="standardWidth">
						</td>
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
                                    try {
                                        $dataAuto = array();
                                        $sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
                                        $resultAuto = $connection2->prepare($sqlAuto);
                                        $resultAuto->execute($dataAuto);
                                    } catch (PDOException $e) {
                                    }
								while ($rowAuto = $resultAuto->fetch()) {
									echo '"'.$rowAuto['name'].'", ';
								}
								?>
								];
								$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
							});
						</script>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Home Address (Country)') ?></b><br/>
						</td>
						<td class="right">
							<select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
								<?php
                                echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['printable_name'] == $row['homeAddressCountry']) {
										$selected = ' selected';
									}
									echo "<option $selected value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
								}
								?>				
							</select>
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
			
			<?php
            //Get children and prep array
            try {
                $dataChildren = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlChildren = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName';
                $resultChildren = $connection2->prepare($sqlChildren);
                $resultChildren->execute($dataChildren);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $children = array();
            $count = 0;
            while ($rowChildren = $resultChildren->fetch()) {
                $children[$count]['image_240'] = $rowChildren['image_240'];
                $children[$count]['gibbonPersonID'] = $rowChildren['gibbonPersonID'];
                $children[$count]['preferredName'] = $rowChildren['preferredName'];
                $children[$count]['surname'] = $rowChildren['surname'];
                $children[$count]['status'] = $rowChildren['status'];
                $children[$count]['comment'] = $rowChildren['comment'];
                ++$count;
            }
            //Get adults and prep array
            try {
                $dataAdults = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlAdults = 'SELECT * FROM gibbonFamilyAdult, gibbonPerson WHERE (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                $resultAdults = $connection2->prepare($sqlAdults);
                $resultAdults->execute($dataAdults);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $adults = array();
            $count = 0;
            while ($rowAdults = $resultAdults->fetch()) {
                $adults[$count]['image_240'] = $rowAdults['image_240'];
                $adults[$count]['gibbonPersonID'] = $rowAdults['gibbonPersonID'];
                $adults[$count]['title'] = $rowAdults['title'];
                $adults[$count]['preferredName'] = $rowAdults['preferredName'];
                $adults[$count]['surname'] = $rowAdults['surname'];
                $adults[$count]['status'] = $rowAdults['status'];
                $adults[$count]['comment'] = $rowAdults['comment'];
                $adults[$count]['childDataAccess'] = $rowAdults['childDataAccess'];
                $adults[$count]['contactPriority'] = $rowAdults['contactPriority'];
                $adults[$count]['contactCall'] = $rowAdults['contactCall'];
                $adults[$count]['contactSMS'] = $rowAdults['contactSMS'];
                $adults[$count]['contactEmail'] = $rowAdults['contactEmail'];
                $adults[$count]['contactMail'] = $rowAdults['contactMail'];
                ++$count;
            }

            //Get relationships and prep array
            try {
                $dataRelationships = array('gibbonFamilyID' => $gibbonFamilyID);
                $sqlRelationships = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID';
                $resultRelationships = $connection2->prepare($sqlRelationships);
                $resultRelationships->execute($dataRelationships);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            $relationships = array();
            $count = 0;
            while ($rowRelationships = $resultRelationships->fetch()) {
                $relationships[$rowRelationships['gibbonPersonID1']][$rowRelationships['gibbonPersonID2']] = $rowRelationships['relationship'];
                ++$count;
            }

            echo '<h3>';
            echo __($guid, 'Relationships');
            echo '</h3>';
            echo '<p>';
            echo __($guid, 'Use the table below to show how each child is related to each adult in the family.');
            echo '</p>';
            if ($resultChildren->rowCount() < 1 or $resultAdults->rowCount() < 1) {
                echo "<div class='error'>".__($guid, 'There are not enough people in this family to form relationships.').'</div>';
            } else {
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_relationshipsProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search'>";
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Adults');
                echo '</th>';
                foreach ($children as $child) {
                    echo '<th>';
                    echo formatName('', $child['preferredName'], $child['surname'], 'Student');
                    echo '</th>';
                }
                echo '</tr>';
                $count = 0;
                foreach ($adults as $adult) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;
                    echo "<tr class='$rowNum'>";
                    echo '<td>';
                    echo '<b>'.formatName($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent').'<b>';
                    echo '</td>';
                    foreach ($children as $child) {
                        echo '<td>';
                        ?>
							<select name="relationships[]" id="relationships[]" style="width: 100%">
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == '') { echo 'selected'; } ?> value=""></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Mother') { echo 'selected'; } ?> value="Mother"><?php echo __($guid, 'Mother') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Father') { echo 'selected'; } ?> value="Father"><?php echo __($guid, 'Father') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Step-Mother') { echo 'selected'; } ?> value="Step-Mother"><?php echo __($guid, 'Step-Mother') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Step-Father') { echo 'selected'; } ?> value="Step-Father"><?php echo __($guid, 'Step-Father') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Adoptive Parent') { echo 'selected'; } ?> value="Adoptive Parent"><?php echo __($guid, 'Adoptive Parent') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Guardian') { echo 'selected'; } ?> value="Guardian"><?php echo __($guid, 'Guardian') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Grandmother') { echo 'selected'; } ?> value="Grandmother"><?php echo __($guid, 'Grandmother') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Grandfather') { echo 'selected'; } ?> value="Grandfather"><?php echo __($guid, 'Grandfather') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Aunt') { echo 'selected'; } ?> value="Aunt"><?php echo __($guid, 'Aunt') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Uncle') { echo 'selected'; } ?> value="Uncle"><?php echo __($guid, 'Uncle') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Nanny/Helper') { echo 'selected'; } ?> value="Nanny/Helper"><?php echo __($guid, 'Nanny/Helper') ?></option>
								<option <?php if (@$relationships[$adult['gibbonPersonID']][$child['gibbonPersonID']] == 'Other') { echo 'selected'; } ?> value="Other"><?php echo __($guid, 'Other') ?></option>
							</select>
							<input type="hidden" name="gibbonPersonID1[]" value="<?php echo $adult['gibbonPersonID'] ?>">
							<input type="hidden" name="gibbonPersonID2[]" value="<?php echo $child['gibbonPersonID'] ?>">
							<?php
						echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
						<tr><td colspan="<?php echo count($children) + 1 ?>" class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td></tr>
						<?php
                    echo '</table>';
                echo '</form>';
            }

            echo '<h3>';
            echo __($guid, 'View Children');
            echo '</h3>';

            if ($resultChildren->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Photo');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Status');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                foreach ($children as $child) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo getUserPhoto($guid, $child['image_240'], 75);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$child['gibbonPersonID']."'>".formatName('', $child['preferredName'], $child['surname'], 'Student').'</a>';
                    echo '</td>';
                    echo '<td>';
                    echo $child['status'];
                    echo '</td>';
                    echo '<td>';
                    try {
                        $dataDetail = array('gibbonPersonID' => $child['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlDetail = 'SELECT * FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo '<td>';
                    echo nl2brr($child['comment']);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_deleteChild.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/user_manage_password.php&gibbonPersonID='.$child['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Change Password')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/key.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_addChildProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3>
							<?php echo __($guid, 'Add Child') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Child\'s Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>'; ?>
								<optgroup label='--<?php echo __($guid, 'Enroled Students') ?>--'>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student').'</option>';
								}
								?>
								</optgroup>
								<optgroup label='--<?php echo __($guid, 'All Users') ?>--'>
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT * FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									$expected = '';
									if ($rowSelect['status'] == 'Expected') {
										$expected = ' (Expected)';
									}
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')'.$expected.'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonPersonID=new LiveValidation('gibbonPersonID');
								gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Comment') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 class="standardWidth"></textarea>
						</td>
					</tr>
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

			<?php	
            echo '<h3>';
            echo __($guid, 'View Adults');
            echo '</h3>';
            echo "<div class='warning'>";
            echo __($guid, 'Logic exists to try and ensure that there is always one and only one parent with Contact Priority set to 1. This may result in values being set which are not exactly what you chose.');
            echo '</div>';

            if ($resultAdults->rowCount() < 1) {
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
                echo __($guid, 'Status');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px; height: 100px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Data Access').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact Priority').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Phone').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By SMS').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Email').'</div>';
                echo '</th>';
                echo "<th style='max-width: 50px; padding-left: 1px; padding-right: 1px'>";
                echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);'>".__($guid, 'Contact By Mail').'</div>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                foreach ($adults as $adult) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$adult['gibbonPersonID']."'>".formatName($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent').'</a>';
                    echo '</td>';
                    echo '<td>';
                    echo $adult['status'];
                    echo '</td>';
                    echo '<td>';
                    echo nl2brr($adult['comment']);
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['childDataAccess'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactPriority'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactCall'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactSMS'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactEmail'];
                    echo '</td>';
                    echo "<td style='padding-left: 1px; padding-right: 1px'>";
                    echo $adult['contactMail'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_editAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_deleteAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=".$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/user_manage_password.php&gibbonPersonID='.$adult['gibbonPersonID']."&search=$search'><img title='".__($guid, 'Change Password')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/key.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_edit_addAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3>
							<?php echo __($guid, 'Add Adult') ?>
							</h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Adult\'s Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonPersonID2" id="gibbonPersonID2" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								try {
									$dataSelect = array();
									$sqlSelect = "SELECT status, gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$expected = '';
									if ($rowSelect['status'] == 'Expected') {
										$expected = ' (Expected)';
									}
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Parent', true, true).' ('.$rowSelect['username'].')'.$expected.'</option>';
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonPersonID2=new LiveValidation('gibbonPersonID2');
								gibbonPersonID2.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Comment') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Data displayed in full Student Profile') ?><br/></span>
						</td>
						<td class="right">
							<textarea name="comment2" id="comment2" rows=8 class="standardWidth"></textarea>
							<script type="text/javascript">
								var comment2=new LiveValidation('comment2');
								comment2.add( Validate.Length, { maximum: 1000 } );
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Data Access?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Access data on family\'s children?') ?></span>
						</td>
						<td class="right">
							<select name="childDataAccess" id="childDataAccess" class="standardWidth">
								<option value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Contact Priority') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'The order in which school should contact family members.') ?></span>
						</td>
						<td class="right">
							<select name="contactPriority" id="contactPriority" class="standardWidth">
								<option value="1"><?php echo __($guid, '1') ?></option>
								<option value="2"><?php echo __($guid, '2') ?></option>
								<option value="3"><?php echo __($guid, '3') ?></option>
							</select>
							<script type="text/javascript">
								/* Advanced Options Control */
								$(document).ready(function(){
									<?php 
                                    echo '$("#contactCall").attr("disabled", "disabled");';
									echo '$("#contactSMS").attr("disabled", "disabled");';
									echo '$("#contactEmail").attr("disabled", "disabled");';
									echo '$("#contactMail").attr("disabled", "disabled");'; ?>	
									$("#contactPriority").change(function(){
										if ($('#contactPriority').val()=="1" ) {
											$("#contactCall").attr("disabled", "disabled");
											$("#contactCall").val("Y");
											$("#contactSMS").attr("disabled", "disabled");
											$("#contactSMS").val("Y");
											$("#contactEmail").attr("disabled", "disabled");
											$("#contactEmail").val("Y");
											$("#contactMail").attr("disabled", "disabled");
											$("#contactMail").val("Y");
										} 
										else {
											$("#contactCall").removeAttr("disabled");
											$("#contactSMS").removeAttr("disabled");
											$("#contactEmail").removeAttr("disabled");
											$("#contactMail").removeAttr("disabled");
										}
									 });
								});
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php echo __($guid, 'Call?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency phone calls from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactCall" id="contactCall" class="standardWidth">
								<option value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'SMS?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency SMS messages from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactSMS" id="contactSMS" class="standardWidth">
								<option value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Email?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive non-emergency emails from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactEmail" id="contactEmail" class="standardWidth">
								<option value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Mail?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Receive postage mail from school?') ?></span>
						</td>
						<td class="right">
							<select name="contactMail" id="contactMail" class="standardWidth">
								<option value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option value="N"><?php echo __($guid, 'No') ?></option>
							</select>
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
			<?php

        }
    }
}
?>