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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Family Data').'</div>';
        echo '</div>';

        if ($highestAction == 'Update Personal Data_any') {
            echo '<p>';
            echo __($guid, 'This page allows a user to request selected family data updates for any family.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __($guid, 'This page allows any adult with data access permission to request selected family data updates for their family.');
            echo '</p>';
        }

        $customResponces = array();
        $error3 = __($guid, 'Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $error3 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['error3'] = $error3;

        $success0 = __($guid, 'Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $success0 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['success0'] = $success0;

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $customResponces);
        }

        echo '<h2>';
        echo __($guid, 'Choose Family');
        echo '</h2>';

        $gibbonFamilyID = null;
        if (isset($_GET['gibbonFamilyID'])) {
            $gibbonFamilyID = $_GET['gibbonFamilyID'];
        }
        ?>

		<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td>
						<b><?php echo __($guid, 'Family') ?> *</b><br/>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonFamilyID">
							<?php
                            if ($highestAction == 'Update Family Data_any') {
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = 'SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily ORDER BY name';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    if ($gibbonFamilyID == $rowSelect['gibbonFamilyID']) {
                                        echo "<option selected value='".$rowSelect['gibbonFamilyID']."'>".$rowSelect['name'].'</option>';
                                    } else {
                                        echo "<option value='".$rowSelect['gibbonFamilyID']."'>".$rowSelect['name'].'</option>';
                                    }
                                }
                            } else {
                                try {
                                    $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sqlSelect = "SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    if ($gibbonFamilyID == $rowSelect['gibbonFamilyID']) {
                                        echo "<option selected value='".$rowSelect['gibbonFamilyID']."'>".$rowSelect['name'].'</option>';
                                    } else {
                                        echo "<option value='".$rowSelect['gibbonFamilyID']."'>".$rowSelect['name'].'</option>';
                                    }
                                }
                            }
        					?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/data_family.php">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        if ($gibbonFamilyID != '') {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            //Check access to person
            if ($highestAction == 'Update Family Data_any') {
                try {
                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID);
                    $sqlCheck = 'SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }
            } else {
                try {
                    $dataCheck = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }

            if ($resultCheck->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;
                try {
                    $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonFamilyUpdate WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() > 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                } elseif ($result->rowCount() == 1) {
                    $existing = true;
                    echo "<div class='warning'>";
                    echo __($guid, 'You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.');
                    echo '</div>';
                    $proceed = true;
                } else {
                    //Get user's data
                    try {
                        $data = array('gibbonFamilyID' => $gibbonFamilyID);
                        $sql = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
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
                        $proceed = true;
                    }
                }

                if ($proceed == true) {
                    //Let's go!
                    $row = $result->fetch(); ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_familyProcess.php?gibbonFamilyID='.$gibbonFamilyID ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Address Name') ?><?php if ($highestAction != 'Update Family Data_any') { echo ' *';}?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Formal name to address parents with.') ?></span>
								</td>
								<td class="right">
									<input name="nameAddress" id="nameAddress" maxlength=100 value="<?php echo htmlPrep($row['nameAddress']) ?>" type="text" class="standardWidth">
                                    <?php
                                    if ($highestAction != 'Update Family Data_any') {
                                        ?>
                                        <script type="text/javascript">
    										var nameAddress=new LiveValidation('nameAddress');
    										nameAddress.add(Validate.Presence);
    									</script>
                                        <?php
                                    }
                                    ?>

								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Home Address') ?><?php if ($highestAction != 'Update Family Data_any') { echo ' *';}?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
								</td>
								<td class="right">
									<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php echo $row['homeAddress'] ?>" type="text" class="standardWidth">
                                    <?php
                                    if ($highestAction != 'Update Family Data_any') {
                                        ?>
                                        <script type="text/javascript">
    										var homeAddress=new LiveValidation('homeAddress');
    										homeAddress.add(Validate.Presence);
    									</script>
                                        <?php
                                    }
                                    ?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Home Address (District)') ?><?php if ($highestAction != 'Update Family Data_any') { echo ' *';}?></b><br/>
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
                                <?php
                                if ($highestAction != 'Update Family Data_any') {
                                    ?>
                                    <script type="text/javascript">
                                        var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
                                        homeAddressDistrict.add(Validate.Presence);
                                    </script>
                                    <?php
                                }
                                ?>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Home Address (Country)') ?><?php if ($highestAction != 'Update Family Data_any') { echo ' *';}?></b><br/>
								</td>
								<td class="right">
									<select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
										<?php
                                        if ($highestAction != 'Update Family Data_any') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        }
                                        else {
                                            echo "<option value=''></option>";
                                        }
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
                                    <?php
                                    if ($highestAction != 'Update Family Data_any') {
                                        ?>
                                        <script type="text/javascript">
    										var homeAddressCountry=new LiveValidation('homeAddressCountry');
    										homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
    									</script>
                                        <?php
                                    }
                                    ?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Home Language - Primary') ?><?php if ($highestAction != 'Update Family Data_any') { echo ' *';}?></b><br/>
								</td>
								<td class="right">
									<select name="languageHomePrimary" id="languageHomePrimary" class="standardWidth">
										<?php
                                        if ($highestAction != 'Update Family Data_any') {
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        }
                                        else {
                                            echo "<option value=''></option>";
                                        }
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
                                    <?php
                                    if ($highestAction != 'Update Family Data_any') {
                                        ?>
                                        <script type="text/javascript">
    										var languageHomePrimary=new LiveValidation('languageHomePrimary');
    										languageHomePrimary.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
    									</script>
                                        <?php
                                    }
                                    ?>
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
									<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
								</td>
								<td class="right">
									<?php
                                    if ($existing) {
                                        echo "<input type='hidden' name='existing' value='".$row['gibbonFamilyUpdateID']."'>";
                                    } else {
                                        echo "<input type='hidden' name='existing' value='N'>";
                                    }
                   		 			?>
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
    }
}
?>
