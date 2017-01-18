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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_duplicate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_manage_catalog.php'>".__($guid, 'Manage Catalog')."</a> > </div><div class='trailEnd'>".__($guid, 'Duplicate Item').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonLibraryItemID = $_GET['gibbonLibraryItemID'];
    if ($gibbonLibraryItemID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            $step = null;
            if (isset($_GET['step'])) {
                $step = $_GET['step'];
            }
            if ($step != 1 and $step != 2) {
                $step = 1;
            }

            //Step 1
            if ($step == 1) {
                ?>
				<h2>
					<?php echo __($guid, 'Step 1 - Quantity') ?>
				</h2>
				<?php
                if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicate.php&step=2&gibbonLibraryItemID='.$row['gibbonLibraryItemID'].'&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'] ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly style='width: 300px' type='text' value='<?php echo __($guid, $row['type']) ?>' />
								<input type='hidden' name='gibbonLibraryTypeID' value='<?php echo $row['gibbonLibraryTypeID'] ?>'>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Name') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=255 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'ID') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="id" id="id" maxlength=255 value="<?php echo $row['id'] ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Author/Brand') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<input readonly name="producer" id="producer" maxlength=255 value="<?php echo $row['producer'] ?>" type="text" class="standardWidth">
							</td>
						</tr>

						<tr>
							<td>
								<b><?php echo __($guid, 'Number of Copies') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'How many copies do you want to make of this item?') ?></span>
							</td>
							<td class="right">
								<select name='number' id='number' style='width: 304px'>
									<?php
                                        for ($i = 1; $i < 21; ++$i) {
                                            echo "<option value='$i'>$i</option>";
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
								<input type="submit" value="<?php echo __($guid, 'Next') ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
            //Step 1
            elseif ($step == 2) {
                ?>
				<h2>
					<?php echo __($guid, 'Step 2 - Details') ?>
				</h2>
				<?php
                if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicateProcess.php?gibbonLibraryItemID='.$row['gibbonLibraryItemID'].'&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'] ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<?php
                        $number = $_POST['number'];
						for ($i = 1; $i <= $number; ++$i) {
							echo "<tr class='break'>";
							echo '<td colspan=2>';
							echo '<h3>';
							echo "Copy $i";
							echo '</h3>';
							echo '</td>';
							echo '</tr>';
                            //GENERAL FIELDS
                            ?>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Name') ?> *</b><br/>
									<span class="emphasis small">Volume or product name.</span>
								</td>
								<td class="right">
									<input name="name<?php echo $i ?>" id="name<?php echo $i ?>" maxlength=255 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var name<?php echo $i ?>=new LiveValidation('name<?php echo $i ?>');
										name<?php echo $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'ID') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'School-unique ID or barcode.') ?></span>
								</td>
								<td class="right">
									<input name="id<?php echo $i ?>" id="id<?php echo $i ?>" maxlength=255 value="" type="text" class="standardWidth">
									<?php
                                    //Get list of all ids already in use
                                    $idList = '';
									try {
										$dataSelect = array();
										$sqlSelect = "SELECT id FROM gibbonLibraryItem WHERE NOT id='".$row['id']."' ORDER BY id";
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									while ($rowSelect = $resultSelect->fetch()) {
										$idList .= "'".$rowSelect['id']."',";
									}
									?>
									<script type="text/javascript">
										var id<?php echo $i ?>=new LiveValidation('id<?php echo $i ?>');
										id<?php echo $i ?>.add( Validate.Exclusion, { within: [<?php echo $idList; ?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
										id<?php echo $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Author/Brand') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Who created the item?') ?></span>
								</td>
								<td class="right">
									<input name="producer<?php echo $i ?>" id="producer<?php echo $i ?>" maxlength=255 value="<?php echo $row['producer'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var producer<?php echo $i ?>=new LiveValidation('producer<?php echo $i ?>');
										producer<?php echo $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Vendor') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Who supplied the item?') ?></span>
								</td>
								<td class="right">
									<input name="vendor<?php echo $i ?>" id="vendor<?php echo $i ?>" maxlength=100 value="<?php echo $row['vendor'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Purchase Date') ?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<input name="purchaseDate<?php echo $i ?>" id="purchaseDate<?php echo $i ?>" maxlength=10 value="<?php echo dateConvertBack($guid, $row['purchaseDate']) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var purchaseDate=new LiveValidation('purchaseDate');
										purchaseDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
										?>." } );
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#purchaseDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Invoice Number') ?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<input name="invoiceNumber<?php echo $i ?>" id="invoiceNumber<?php echo $i ?>" maxlength=50 value="<?php echo $row['invoiceNumber'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr id="locationRow">
								<td>
									<b><?php echo __($guid, 'Location') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonSpaceID<?php echo $i ?>" id="gibbonSpaceID<?php echo $i ?>" class="standardWidth">
										<?php
                                        echo "<option value=''></option>";
										try {
											$dataSelect = array();
											$sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
											$resultSelect = $connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										} catch (PDOException $e) {
										}
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if ($row['gibbonSpaceID'] == $rowSelect['gibbonSpaceID']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonSpaceID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Location Detail') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Shelf, cabinet, sector, etc') ?></span>
								</td>
								<td class="right">
									<input name="locationDetail<?php echo $i ?>" id="locationDetail<?php echo $i ?>" maxlength=255 value="<?php echo $row['locationDetail'] ?>" type="text" class="standardWidth">
								</td>
							</tr>

							<!-- FIELDS & CONTROLS FOR OWNERSHIP -->
							<script type="text/javascript">
								$(document).ready(function(){
									$("#ownershipType<?php echo $i ?>").change(function(){
										if ($('#ownershipType<?php echo $i ?>').val()=="School" ) {
											$("#ownershipTypeIndividualRow<?php echo $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<?php echo $i ?>").slideDown("fast", $("#ownershipTypeSchoolRow<?php echo $i ?>").css("display","table-row"));
										} else if ($('#ownershipType<?php echo $i ?>').val()=="Individual" ) {
											$("#ownershipTypeSchoolRow<?php echo $i ?>").css("display","none");
											$("#ownershipTypeIndividualRow<?php echo $i ?>").slideDown("fast", $("#ownershipTypeIndividualRow<?php echo $i ?>).css("display","table-row"));
										}
										else {
											$("#ownershipTypeIndividualRow<?php echo $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<?php echo $i ?>").css("display","none");
										}
									 });
								});
							</script>
							<tr id='ownershipTypeRow<?php echo $i ?>'>
								<td>
									<b><?php echo __($guid, 'Ownership Type') ?></b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="ownershipType<?php echo $i ?>" id="ownershipType<?php echo $i ?>" class='ownershipType<?php echo $i ?> standardWidth'>
										<option value=""></option>
										<option <?php if ($row['ownershipType'] == 'School') { echo 'selected'; } ?> value="School" /> <?php echo __($guid, 'School') ?>
										<option <?php if ($row['ownershipType'] == 'Individual') { echo 'selected'; } ?> value="Individual" /> <?php echo __($guid, 'Individual') ?>
									</select>
								</td>
							</tr>
							<?php
                            $selectContents = "<option value=''></option>";
							$selectContents .= "<optgroup label='--".__($guid, 'Students By Roll Group')."--'>";
							try {
								$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
								$sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								$selectContents .= "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							$selectContents .= '</optgroup>';
							$selectContents .= "<optgroup label='--".__($guid, 'All Users')."--'>";
							try {
								$dataSelect = array();
								$sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								$expected = '';
								if ($rowSelect['status'] == 'Expected') {
									$expected = ' (Expected)';
								}
								$selected = '';
								if ($row['gibbonPersonIDOwnership'] == $rowSelect['gibbonPersonID']) {
									$selected = 'selected';
								}
								$selectContents .= "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true)."$expected</option>";
							}
							$selectContents .= '</optgroup>'; ?>
							<tr id="ownershipTypeSchoolRow<?php echo $i ?>" <?php if ($row['ownershipType'] != 'School') { echo "style='display: none'"; } ?>>
								<td>
									<b><?php echo __($guid, 'Main User') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Person the device is assigned to.') ?></span>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipSchool<?php echo $i ?>" id="gibbonPersonIDOwnershipSchool<?php echo $i ?>" class="standardWidth">
										<?php echo $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="ownershipTypeIndividualRow<?php echo $i ?>" <?php if ($row['ownershipType'] != 'Individual') { echo "style='display: none'"; } ?>>
								<td>
									<b><?php echo __($guid, 'Owner') ?></b><br/>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipIndividual<?php echo $i ?>" id="gibbonPersonIDOwnershipIndividual<?php echo $i ?>" class="standardWidth">
										<?php echo $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="gibbonDepartmentIDRow">
								<td>
									<b><?php echo __($guid, 'Department') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Which department is responsible for the item?') ?></span>
								</td>
								<td class="right">
									<select name="gibbonDepartmentID<?php echo $i ?>" id="gibbonDepartmentID<?php echo $i ?>" class="standardWidth">
										<?php
                                        echo "<option value=''></option>";
										try {
											$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
											$sqlSelect = 'SELECT * FROM gibbonDepartment ORDER BY name';
											$resultSelect = $connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										} catch (PDOException $e) {
										}
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if ($row['gibbonDepartmentID'] == $rowSelect['gibbonDepartmentID']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonDepartmentID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Borrowable?') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Is item available for loan?') ?></span>
								</td>
								<td class="right">
									<select name="borrowable<?php echo $i ?>" id="borrowable<?php echo $i ?>" class="standardWidth">
										<option <?php if ($row['borrowable'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo __($guid, 'Yes') ?>
										<option <?php if ($row['borrowable'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo __($guid, 'No') ?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Status?') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right">
									<input readonly style='width: 300px' type='text' value='Available' />
								</td>
							</tr>

							<tr id='gibbonSchoolYearIDReplacement'>
								<td>
									<b><?php echo __($guid, 'Replacement Year'); ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'When is this item scheduled for replacement.') ?></span>
								</td>
								<td class="right">
									<select name="gibbonSchoolYearIDReplacement" id="gibbonSchoolYearIDReplacement" class="standardWidth">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber DESC';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										echo "<option value=''></option>";
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if ($rowSelect['gibbonSchoolYearID'] == $row['gibbonSchoolYearIDReplacement']) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr id='replacementCostRow'>
								<td>
									<b><?php echo __($guid, 'Replacement Cost'); ?></b><br/>
									<span style="font-size: 90%">
										<i>
										<?php
                                        if ($_SESSION[$guid]['currency'] != '') {
                                            echo sprintf(__($guid, 'Numeric value of the replacement cost in %1$s.'), $_SESSION[$guid]['currency']);
                                        } else {
                                            echo __($guid, 'Numeric value of the replacement cost.');
                                        }
                    					?>
										</i>
									</span>
								</td>
								<td class="right">
									<input name="replacementCost" id="replacementCost" maxlength=13 value="<?php echo $row['replacementCost'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var replacementCost=new LiveValidation('replacementCost');
										replacementCost.add(Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									</script>
								</td>
							</tr>

							<tr>
								<td colspan=2>
									<b><?php echo __($guid, 'Comments/Notes') ?></b>
									<textarea name='comment<?php echo $i ?>' id='comment<?php echo $i ?>' rows=10 style='width: 300px'><?php echo htmlPreP($row['comment']) ?></textarea>
								</td>
							</tr>
							<?php
                            //TYPE SPECIFIC FIELDS
                            if ($i == 1) {
                                try {
                                    $dataFields = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
                                    $sqlFields = "SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name";
                                    $resultFields = $connection2->prepare($sqlFields);
                                    $resultFields->execute($dataFields);
                                } catch (PDOException $e) {
                                }

                                if ($resultFields->rowCount() == 1) {
                                    $rowFields = $resultFields->fetch();
                                    $fields = unserialize($rowFields['fields']);
                                    $fieldValues = unserialize($row['fields']);
                                }
                            }

							if (count($fields) < 1 or is_array($fields) == false) {
								echo '<tr>';
								echo "<td colspan='2'> ";
								echo "<div class='error'>";
								echo __($guid, 'Your request failed due to a database error.');
								echo '</div>';
								echo '</td> ';
								echo '</tr> ';
							} else {
								$output = '';
								foreach ($fields as $field) {
									$fieldName = preg_replace('/ /', '', $field['name']);
									echo '<tr>';
									echo '<td> ';
									echo '<b>'.__($guid, $field['name']).'</b>';
									if ($field['required'] == 'Y') {
										echo ' *';
									}
									$output .= "<br/><span style='font-size: 90%'><i>".str_replace('dd/mm/yyyy', $_SESSION[$guid]['i18n']['dateFormat'], $field['description']).'</span>';
									echo '</td>';
									echo "<td class='right'>";
									if ($field['type'] == 'Text') {
										echo "<input maxlength='".$field['options']."' name='field".$fieldName.$i."' id='field".$fieldName.$i."' value='".htmlPrep($fieldValues[$field['name']])."' type='text' style='width: 300px'>";
									} elseif ($field['type'] == 'Select') {
										echo "<select name='field".$fieldName.$i."' id='field".$fieldName.$i."' type='text' style='width: 300px'>";
										if ($field['required'] == 'Y') {
											echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
										}
										$options = explode(',', $field['options']);
										foreach ($options as $option) {
											$option = trim($option);
											$selected = '';
											if ($option == $fieldValues[$field['name']]) {
												$selected = 'selected';
											}
											echo "<option $selected value='$option'>$option</option>";
										}
										echo '</select>';
									} elseif ($field['type'] == 'Textarea') {
										echo "<textarea rows='".$field['options']."' name='field".$fieldName.$i."' id='field".$fieldName.$i."' style='width: 300px'>".htmlPrep($fieldValues[$field['name']]).'</textarea>';
									} elseif ($field['type'] == 'Date') {
										echo "<input name='field".$fieldName.$i."' id='field".$fieldName.$i."' maxlength=10 value='".dateConvertBack($guid, $fieldValues[$field['name']])."' type='text' style='width: 300px'>";
										echo "<script type='text/javascript'>";
										echo 'var field'.$fieldName.$i."=new LiveValidation('field".$fieldName.$i."');";
										$output .= 'field'.$fieldName.'.add( Validate.Format, {pattern:';
										if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
											$output .= "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											$output .= $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										$output .= ", failureMessage: 'Use ".$_SESSION[$guid]['i18n']['dateFormat'].".' } );";
										echo '</script>';
										echo "<script type='text/javascript'>";
										echo '$(function() {';
										echo "$( '#field".$fieldName.$i."' ).datepicker();";
										echo '});';
										echo '</script>';
									} elseif ($field['type'] == 'URL') {
										echo "<input maxlength='".$field['options']."' name='field".$fieldName.$i."' id='field".$fieldName.$i."' value='".htmlPrep($fieldValues[$field['name']])."' type='text' style='width: 300px'>";
										echo "<script type='text/javascript'>";
										echo 'var field'.$fieldName.$i."=new LiveValidation('field".$fieldName.$i."');";
										echo 'field'.$fieldName.$i.".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http://\" } );";
										echo '</script>';
									}
									echo '</td>';
									echo '</tr>';
                                    //NEED LIVE VALIDATION
                                    if ($field['required'] == 'Y') {
                                        if ($field['type'] == 'Text' or $field['type'] == 'Textarea' or $field['type'] == 'Date') {
                                            echo "<script type='text/javascript'>";
                                            echo 'var field'.$fieldName.$i."=new LiveValidation('field".$fieldName.$i."');";
                                            echo 'field'.$fieldName.$i.'.add(Validate.Presence);';
                                            echo '</script>';
                                        } elseif ($field['type'] == 'Select') {
                                            echo "<script type='text/javascript'>";
                                            echo 'var field'.$fieldName.$i."=new LiveValidation('field".$fieldName.$i."');";
                                            echo 'field'.$fieldName.$i.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});";
                                            echo '</script>';
                                        }
                                    }
								}
							}
						}
						?>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="count" value="<?php echo $number ?>">
								<input type='hidden' name='gibbonLibraryTypeID' value='<?php echo $_POST['gibbonLibraryTypeID'] ?>'>
								<input type="hidden" name="gibbonLibraryItemID" value="<?php echo $row['gibbonLibraryItemID'] ?>">
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
?>
