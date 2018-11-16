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

$page->breadcrumbs->add(__('Import Records'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_import.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
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
        echo "<h2>" ;
			echo __('Step 1 - Select CSV Files');
		echo "</h2>";
		echo "<p>";
		      echo __('This page allows you to import library records from a CSV file. The import includes one row for each record. The system will match records by ID, updating any matching results, whilst creating new records not already existing in the system.')."<br/>";
		echo "</p>";

        $form = Form::create('importUserPhotos', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_import.php&step=2');

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

        ?>
		<h4>
			<?php echo __('Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php echo __('THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php echo __('You may only submit CSV files.') ?></li>
			<li><?php echo __('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php echo __('Imports can only be for one Type (e.g. Print Publication, Computer, etc). The type of the first item in the import will be applied to all other entries.') ?></li>
			<li><?php echo __('The submitted file must have the following fields in the following order (* denotes required field):') ?></li>
				<ol>
					<li><b><?php echo __('General Details'); ?></b></li>
					<ol>
						<li><b><?php echo __('Type') ?>* </b> - <?php echo __('One of:').' ';
						try {
							$dataType = array();
							$sqlType = "SELECT name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
							$resultType = $connection2->prepare($sqlType);
							$resultType->execute($dataType);
						} catch (PDOException $e) {
						}
						$typeCount = 1;
						while ($rowType = $resultType->fetch()) {
							echo "'".__($rowType['name'])."'";
							if ($typeCount < $resultType->rowCount()) {
								echo ', ';
							} else {
								echo '.';
							}
							++$typeCount;
						}
						?></li>
						<li><b><?php echo __('Name') ?> *</b> - <?php echo __('Volume or product name.') ?></li>
						<li><b><?php echo __('ID') ?> *</b> - <?php echo __('Must be unique, or will lead to update not insert.') ?></li>
						<li><b><?php echo __('Author/Brand') ?> *</b> - <?php echo __('Who created the item?') ?></li>
						<li><b><?php echo __('Vendor') ?></b> - <?php echo __('Who supplied the item?') ?></li>
						<li><b><?php echo __('Purchase Date') ?></b> - <?php echo __('dd/mm/yyyy') ?></li>
						<li><b><?php echo __('Invoice Number') ?></b></li>
						<li><b><?php echo __('Location') ?> *</b> - <?php echo __('Space \'Name\' field.') ?></li>
						<li><b><?php echo __('Location Detail') ?></b> - <?php echo __('Shelf, cabinet, sector, etc') ?></li>
						<li><b><?php echo __('Ownership Type') ?> *</b> - <?php echo __('One of: \'School\' or \'Individual\'.') ?></li>
						<li><b><?php echo __('Main User').'/'.__('Owner') ?></b> - <?php echo __('Username of person the device is assigned to.') ?></li>
						<li><b><?php echo __('Department') ?></b> - <?php echo __('\'Name\' field for department responsible for the item.') ?></li>
						<li><b><?php echo __('Borrowable?') ?> *</b> - <?php echo __('Is item available for loan?'.' '.__('One of: \'Y\' or \'N\'.')) ?></li>
						<li><b><?php echo __('Status?') ?> *</b> - <?php echo __('Initial availability.'.' '.'One of: \'Available\',\'In Use\',\'Decommissioned\',\'Lost\',\'On Loan\',\'Repair\' or \'Reserved\'.') ?></li>
                        <li><b><?php echo __('Replacement?') ?> *</b> - <?php echo __('One of: \'Y\' or \'N\'.') ?></li>
                        <li><b><?php echo __('Replacement Cost') ?> </b></li>
                        <li><b><?php echo __('Replacement Year') ?> </b> - <?php echo __('School year name, as set in School Admin. Must already exist.') ?></li>
                        <li><b><?php echo __('Comments/Notes') ?></b></li>
					</ol>
					<li><b><?php echo __('Type-Specific Details');?></b></li>
						<ol>
							<?php
                            try {
                                $dataType = array();
                                $sqlType = "SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
                                $resultType = $connection2->prepare($sqlType);
                                $resultType->execute($dataType);
                            } catch (PDOException $e) {
                            }
							while ($rowType = $resultType->fetch()) {
								echo '<li><b>'.$rowType['name'].'</b></li>';
								echo '<ol>';
								$fields = unserialize($rowType['fields']);
								foreach ($fields as $field) {
									echo '<li>';
									echo '<b>'.$field['name'];
									if ($field['required'] == 'Y') {
										echo ' *';
									}
									echo '</b>';
									if ($field['description'] != '') {
										echo ' - '.$field['description'].'</li>';
									}
								}
								echo '</ol>';
							}
							?>
						</ol>
					</ol>
				</li>
				<li><?php echo __('Do not include a header row in the CSV files.') ?></li>
			</ol>
		<?php

		} elseif ($step == 2) {
			?>
			<h2>
				<?php echo __('Step 2 - Data Check & Confirm') ?>
			</h2>
			<?php

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
					$sql = 'LOCK TABLES gibbonLibraryItem WRITE, gibbonLibraryType WRITE, gibbonPerson WRITE, gibbonDepartment WRITE, gibbonSpace WRITE, gibbonSchoolYear READ';
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

				if ($lockFail == false) {
					//READ IN DATA
					if ($proceed == true) {
						echo '<h4>';
						echo __('File Import');
						echo '</h4>';
						$importFail = false;
						$csvFile = $_FILES['file']['tmp_name'];
						$handle = fopen($csvFile, 'r');
						$results = array();
						$resultCount = 0;
						$resultSuccessCount = 0;
						while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
							//Turn type into gibbonTypeID (only needs to be done once)
							if ($resultCount == 0 and $data[0] != '') {
								try {
									$dataType = array('name' => $data[0]);
									$sqlType = 'SELECT gibbonLibraryTypeID, fields FROM gibbonLibraryType WHERE name=:name';
									$resultType = $connection2->prepare($sqlType);
									$resultType->execute($dataType);
								} catch (PDOException $e) {
									echo "<div class='error'>";
									echo __('Your request failed due to a database error.');
									echo '</div>';
									$types = null;
									$typeFields = null;
								}
								if ($resultType->rowCount() != 1) {
									echo "<div class='error'>";
									echo sprintf(__('Type with name %1$s in import cannot be found.'), $data[0]);
									echo '</div>';
									$types = null;
									$typeFields = null;
								} else {
									$rowType = $resultType->fetch();
									$type = $rowType['gibbonLibraryTypeID'];
									$typeFields = unserialize($rowType['fields']);
								}
							}

							//Get fields
							if ($data[0] != '' and $data[1] != '' and $data[2] != '' and $data[3] != '' and $data[7] != '' and $data[9] != '' and $data[12] != '' and $data[13] != '' and $data[14] != '') {
								//General fields
								$results[$resultSuccessCount]['type'] = '';
								if (isset($data[0])) {
									$results[$resultSuccessCount]['type'] = $data[0];
								}
								$results[$resultSuccessCount]['name'] = '';
								if (isset($data[1])) {
									$results[$resultSuccessCount]['name'] = $data[1];
								}
								$results[$resultSuccessCount]['id'] = '';
								if (isset($data[2])) {
									$results[$resultSuccessCount]['id'] = $data[2];
								}
								$results[$resultSuccessCount]['producer'] = '';
								if (isset($data[3])) {
									$results[$resultSuccessCount]['producer'] = $data[3];
								}
								$results[$resultSuccessCount]['vendor'] = '';
								if (isset($data[4])) {
									$results[$resultSuccessCount]['vendor'] = $data[4];
								}
								$results[$resultSuccessCount]['purchaseDate'] = '';
								if (isset($data[5])) {
									$results[$resultSuccessCount]['purchaseDate'] = $data[5];
								}
								$results[$resultSuccessCount]['invoiceNumber'] = '';
								if (isset($data[6])) {
									$results[$resultSuccessCount]['invoiceNumber'] = $data[6];
								}
								$results[$resultSuccessCount]['location'] = '';
								if (isset($data[7])) {
									$results[$resultSuccessCount]['location'] = $data[7];
								}
								$results[$resultSuccessCount]['locationDetail'] = '';
								if (isset($data[8])) {
									$results[$resultSuccessCount]['locationDetail'] = $data[8];
								}
								$results[$resultSuccessCount]['ownershipType'] = '';
								if (isset($data[9])) {
									$results[$resultSuccessCount]['ownershipType'] = $data[9];
								}
								$results[$resultSuccessCount]['username'] = '';
								if (isset($data[10])) {
									$results[$resultSuccessCount]['username'] = $data[10];
								}
								$results[$resultSuccessCount]['department'] = '';
								if (isset($data[11])) {
									$results[$resultSuccessCount]['department'] = $data[11];
								}
								$results[$resultSuccessCount]['borrowable'] = '';
								if (isset($data[12])) {
									$results[$resultSuccessCount]['borrowable'] = $data[12];
								}
								$results[$resultSuccessCount]['status'] = '';
								if (isset($data[13])) {
									$results[$resultSuccessCount]['status'] = $data[13];
								}
								$results[$resultSuccessCount]['replacement'] = '';
								if (isset($data[14])) {
									$results[$resultSuccessCount]['replacement'] = $data[14];
								}
								$results[$resultSuccessCount]['replacementCost'] = '';
								if (isset($data[15])) {
									$results[$resultSuccessCount]['replacementCost'] = $data[15];
								}
								$results[$resultSuccessCount]['gibbonSchoolYearIDReplacement'] = '';
								if (isset($data[16])) {
									$results[$resultSuccessCount]['gibbonSchoolYearIDReplacement'] = $data[16];
								}
								$results[$resultSuccessCount]['comment'] = '';
								if (isset($data[17])) {
									$results[$resultSuccessCount]['comment'] = $data[17];
								}


								//Type specific fields
								$results[$resultSuccessCount]['fields'] = '';
								$typeFieldValues = array();
								$totalFieldCount = 18;
								if (is_array($typeFields)) {
									foreach ($typeFields as $typeField) {
										if (isset($data[$totalFieldCount])) {
                                            if ($typeField['type'] == 'Date') {
                                                $typeFieldValues[$typeField['name']] = dateConvert($guid, $data[$totalFieldCount]);
                                            }
                                            else {
                                                $typeFieldValues[$typeField['name']] = $data[$totalFieldCount];
                                            }
										}
										++$totalFieldCount;
									}
								}
								if (count($typeFieldValues) > 0) {
									$results[$resultSuccessCount]['fields'] = serialize($typeFieldValues);
								}

								++$resultSuccessCount;
							} else {
								echo "<div class='error'>";
								echo sprintf(__('Record with ID %1$s had some information malformations.'), $data[2]);
								echo '</div>';
							}
							++$resultCount;
						}
						fclose($handle);
						if ($resultSuccessCount == 0) {
							echo "<div class='error'>";
							echo __('No useful results were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
							echo '</div>';
							$proceed = false;
						} elseif ($resultSuccessCount < $resultCount) {
							echo "<div class='error'>";
							echo __('Some results could not be successfully read or used, so the import will be aborted.');
							echo '</div>';
							$proceed = false;
						} elseif ($resultSuccessCount == $resultCount) {
							echo "<div class='success'>";
							echo __('All results could be read and used, so the import will proceed.');
							echo '</div>';
						} else {
							echo "<div class='error'>";
							echo __('An unknown error occured, so the import will be aborted.');
							echo '</div>';
							$proceed = false;
						}
					}

					if ($proceed == true) {
						echo '<h4>';
						echo __('Results');
						echo '</h4>';

						$locations = array();
						$users = array();
						$departments = array();

						//Scroll through all records
						foreach ($results as $result) {
							//If we have gibbonLibraryTypeID, move on
							if ($type != '' and is_array($typeFields)) {
								//Turn location into gibbonSpaceID in db-efficient manner
								if (isset($locations[$result['location']]) == false) {
									try {
										$dataLocation = array('name' => $result['location']);
										$sqlLocation = 'SELECT gibbonSpaceID FROM gibbonSpace WHERE name=:name';
										$resultLocation = $connection2->prepare($sqlLocation);
										$resultLocation->execute($dataLocation);
									} catch (PDOException $e) {
										echo "<div class='error'>";
										echo __('Your request failed due to a database error.');
										echo '</div>';
										$locations[$result['location']] = null;
									}
									if ($resultLocation->rowCount() != 1) {
										echo "<div class='error'>";
										echo sprintf(__('Location with name %1$s in import cannot be found.'), $result['location']);
										echo '</div>';
										$locations[$result['location']] = null;
									} else {
										$rowLocation = $resultLocation->fetch();
										$locations[$result['location']] = $rowLocation['gibbonSpaceID'];
									}
								}

								//If we have gibbonSpaceID, move on
								if ($locations[$result['location']] != '') {
									//Get users, but they are not compulsorary
									if ($result['username'] != '') {
										if (isset($users[$result['username']]) == false) {
											try {
												$dataUser = array('username' => $result['username']);
												$sqlUser = 'SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username';
												$resultUser = $connection2->prepare($sqlUser);
												$resultUser->execute($dataUser);
											} catch (PDOException $e) {
												echo "<div class='error'>";
												echo __('Your request failed due to a database error.');
												echo '</div>';
												$users[$result['username']] = null;
											}
											if ($resultUser->rowCount() != 1) {
												echo "<div class='error'>";
												echo sprintf(__('User with username %1$s in import cannot be found.'), $result['username']);
												echo '</div>';
												$users[$result['username']] = null;
											} else {
												$rowUser = $resultUser->fetch();
												$users[$result['username']] = $rowUser['gibbonPersonID'];
											}
										}
									}

									//Get departments, but they are not compulsorary
									if ($result['department'] != '') {
										if (isset($users[$result['department']]) == false) {
											try {
												$dataUser = array('name' => $result['department']);
												$sqlUser = 'SELECT gibbonDepartmentID FROM gibbonDepartment WHERE name=:name';
												$resultUser = $connection2->prepare($sqlUser);
												$resultUser->execute($dataUser);
											} catch (PDOException $e) {
												echo "<div class='error'>";
												echo __('Your request failed due to a database error.');
												echo '</div>';
												$departments[$result['department']] = null;
											}
											if ($resultUser->rowCount() != 1) {
												echo "<div class='error'>";
												echo sprintf(__('Department with name %1$s in import cannot be found.'), $result['department']);
												echo '</div>';
												$departments[$result['department']] = null;
											} else {
												$rowUser = $resultUser->fetch();
												$departments[$result['department']] = $rowUser['gibbonDepartmentID'];
											}
										}
									}

									//Check if we are OK to go
									if ($type == '' or $locations[$result['location']] == '' or ($result['username'] != '' and $users[$result['username']] == '') or ($result['department'] != '' and $departments[$result['department']] == '')) { //NOT OK!
										echo "<div class='error'>";
										echo sprintf(__('Record with ID %1$s had some information malformations.'), $data[2]);
										echo '</div>';
									} else { //OK!
										//GET FIELDS READY
										$name = $result['name'];
										$id = $result['id'];
										$producer = $result['producer'];
										$vendor = $result['vendor'];
										$purchaseDate = null;
										if ($result['purchaseDate'] != '') {
											$purchaseDate = dateConvert($guid, $result['purchaseDate']);
										}
										$invoiceNumber = $result['invoiceNumber'];
										$gibbonSpaceID = $locations[$result['location']];
										$locationDetail = $result['locationDetail'];
										$ownershipType = $result['ownershipType'];
										$gibbonPersonIDOwnership = null;
										if ($result['username'] != '') {
											$gibbonPersonIDOwnership = $users[$result['username']];
										}
										$gibbonDepartmentID = null;
										if ($result['department'] != '') {
											$gibbonDepartmentID = $departments[$result['department']];
										}
										$borrowable = $result['borrowable'];
										$status = $result['status'];
										$replacement = $result['replacement'];
										$replacementCost = $result['replacementCost'];
										$gibbonSchoolYearIDReplacement = $result['gibbonSchoolYearIDReplacement'];
										$comment = $result['comment'];
										$fields = $result['fields'];

										//CHECK IF ID EXISTS
										try {
											$dataCheck = array('id' => $id);
											$sqlCheck = 'SELECT * FROM gibbonLibraryItem WHERE id=:id';
											$resultCheck = $connection2->prepare($sqlCheck);
											$resultCheck->execute($dataCheck);
										} catch (PDOException $e) {
											echo "<div class='error'>";
											echo __('Your request failed due to a database error.');
											echo '</div>';
										}
										if ($resultCheck->rowCount() == 1) { //IF IT DOES, UPDATE
											$updateFail = false;
											try {
												$dataUpdate = array('gibbonLibraryTypeID' => $type, 'id' => $id, 'name' => $name, 'producer' => $producer, 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'borrowable' => $borrowable, 'status' => $status, 'replacement' => $replacement, 'replacementCost' => $replacementCost, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCreator' => date('Y-m-d H:i:s', time()), 'fields' => $fields);
												$sqlUpdate = 'UPDATE gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, name=:name, producer=:producer, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, replacement=:replacement, replacementCost=:replacementCost, gibbonSchoolYearIDReplacement=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE name=:gibbonSchoolYearIDReplacement), gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator, fields=:fields WHERE id=:id';
												$resultUpdate = $connection2->prepare($sqlUpdate);
												$resultUpdate->execute($dataUpdate);
											} catch (PDOException $e) {
												echo "<div class='error'>";
												echo $e->getMEssage();
												echo __('Your request failed due to a database error.');
												echo '</div>';
												$updateFail = true;
											}
											if ($updateFail == false) {
												echo "<div class='success'>";
												echo sprintf(__('%1$s was successfully updated.'), $result['id']);
												echo '</div>';
											}
										} else { //IF IT DOES NOT, INSERT
											$insertFail = false;
											try {
												$dataInsert = array('gibbonLibraryTypeID' => $type, 'id' => $id, 'name' => $name, 'producer' => $producer, 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'borrowable' => $borrowable, 'status' => $status, 'replacement' => $replacement, 'replacementCost' => $replacementCost, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCreator' => date('Y-m-d H:i:s', time()), 'fields' => $fields);
												$sqlInsert = 'INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, replacement=:replacement, replacementCost=:replacementCost, gibbonSchoolYearIDReplacement=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE name=:gibbonSchoolYearIDReplacement), gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator, fields=:fields';
												$resultInsert = $connection2->prepare($sqlInsert);
												$resultInsert->execute($dataInsert);
											} catch (PDOException $e) {
												echo "<div class='error'>";
												echo $e->getMEssage();
												echo __('Your request failed due to a database error.');
												echo '</div>';
												$insertFail = true;
											}
											if ($insertFail == false) {
												echo "<div class='success'>";
												echo sprintf(__('%1$s was successfully inserted into the system.'), $result['id']);
												echo '</div>';
											}
										}
									}
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
