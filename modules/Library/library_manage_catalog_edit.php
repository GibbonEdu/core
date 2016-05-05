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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_manage_catalog.php'>".__($guid, 'Manage Catalog')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Item').'</div>';
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

            if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_editProcess.php?name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Catalog Type') ?></h3>
						</td>
					</tr>
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
					
					<tr class='break' id='generalDetailsRow'>
						<td colspan=2>
							<h3><?php echo __($guid, 'General Details') ?></h3>
						</td>
					</tr>
					<tr id='nameRow'>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Volume or product name.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=255 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='idRow'>
						<td> 
							<b><?php echo __($guid, 'ID') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'School-unique ID or barcode.') ?></span>
						</td>
						<td class="right">
							<input name="id" id="idCheck" maxlength=255 value="<?php echo $row['id'] ?>" type="text" class="standardWidth">
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
								var idCheck=new LiveValidation('idCheck');
								idCheck.add( Validate.Exclusion, { within: [<?php echo $idList; ?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
								idCheck.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='producerRow'>
						<td> 
							<b><?php echo __($guid, 'Author/Brand') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Who created the item?') ?></span>
						</td>
						<td class="right">
							<input name="producer" id="producer" maxlength=255 value="<?php echo $row['producer'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var producer=new LiveValidation('producer');
								producer.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='vendorRow'>
						<td> 
							<b><?php echo __($guid, 'Vendor') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Who supplied the item?') ?></span>
						</td>
						<td class="right">
							<input name="vendor" id="vendor" maxlength=100 value="<?php echo $row['vendor'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr id='purchaseDateRow'>
						<td> 
							<b><?php echo __($guid, 'Purchase Date') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="purchaseDate" id="purchaseDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row['purchaseDate']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var purchaseDate=new LiveValidation('purchaseDate');
								purchaseDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
											?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
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
					<tr id='invoiceNumberRow'>
						<td> 
							<b><?php echo __($guid, 'Invoice Number') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="invoiceNumber" id="invoiceNumber" maxlength=50 value="<?php echo $row['invoiceNumber'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR IMAGE -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#imageType").change(function(){
								if ($('select.imageType option:selected').val()=="Link" ) {
									$("#imageFileRow").css("display","none");
									$("#imageLinkRow").slideDown("fast", $("#imageLinkRow").css("display","table-row")); 
									imageLink.enable();
									imageFile.disable();
								} else if ($('select.imageType option:selected').val()=="File" ) {
									$("#imageLinkRow").css("display","none");
									$("#imageFileRow").slideDown("fast", $("#imageFileRow").css("display","table-row")); 
									imageFile.enable();
									imageLink.disable();
								} 
								else {
									$("#imageFileRow").css("display","none");
									$("#imageLinkRow").css("display","none");
									imageFile.disable();
									imageLink.disable();
								}
							 });
						});
					</script>
					<tr id='imageTypeRow'>
						<td> 
							<b><?php echo __($guid, 'Image Type') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, '240px x 240px or smaller.') ?></span>
						</td>
						<td class="right">
							<select name="imageType" id="imageType" class='imageType standardWidth'>
								<option value=""></option>
								<option <?php if ($row['imageType'] == 'File') { echo 'selected'; } ?> value="File" /> <?php echo __($guid, 'File') ?>
								<option <?php if ($row['imageType'] == 'Link') { echo 'selected'; } ?> value="Link" /> <?php echo __($guid, 'Link') ?>
							</select>
						</td>
					</tr>
					<tr id="imageFileRow" <?php if ($row['imageType'] != 'File') { echo "style='display: none'"; } ?>>
						<td> 
							<b><?php echo __($guid, 'Image File') ?></b><br/>
						</td>
						<td class="right">
							<?php
                            if ($row['imageType'] == 'File' and $row['imageLocation'] != '') {
                                echo __($guid, 'Current attachment:')." <a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['imageLocation']."'>".$row['imageLocation'].'</a><br/><br/>';
                            }
            				?>
							<input type="file" name="imageFile" id="imageFile"><br/><br/>
							<script type="text/javascript">
								var imageFile=new LiveValidation('imageFile');
								imageFile.add( Validate.Inclusion, { within: ['.jpg','.jpeg','.png','.gif'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								<?php if ($row['imageType'] != 'File') { echo 'imageFile.disable();'; } ?>
							</script>	
							<?php
                            echo getMaxUpload($guid); ?>
						</td>
					</tr>
					<tr id="imageLinkRow" <?php if ($row['imageType'] != 'Link') { echo "style='display: none'"; } ?>>
						<td> 
							<b><?php echo __($guid, 'Image Link') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="imageLink" id="imageLink" maxlength=255 value="<?php if ($row['imageType'] == 'Link') { echo $row['imageLocation']; } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var imageLink=new LiveValidation('imageLink');
								imageLink.add(Validate.Presence);
								imageLink.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
								<?php if ($row['imageType'] != 'Link') { echo 'imageLink.disable();'; } ?>
							</script>	
						</td>
					</tr>
					
					
					<tr id="locationRow">
						<td> 
							<b><?php echo __($guid, 'Location') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" class="standardWidth">
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
					<tr id='locationDetailRow'>
						<td> 
							<b><?php echo __($guid, 'Location Detail') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Shelf, cabinet, sector, etc') ?></span>
						</td>
						<td class="right">
							<input name="locationDetail" id="locationDetail" maxlength=255 value="<?php echo $row['locationDetail'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR OWNERSHIP -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#ownershipType").change(function(){
								if ($('select.ownershipType option:selected').val()=="School" ) {
									$("#ownershipTypeIndividualRow").css("display","none");
									$("#ownershipTypeSchoolRow").slideDown("fast", $("#ownershipTypeSchoolRow").css("display","table-row")); 
								} else if ($('select.ownershipType option:selected').val()=="Individual" ) {
									$("#ownershipTypeSchoolRow").css("display","none");
									$("#ownershipTypeIndividualRow").slideDown("fast", $("#ownershipTypeIndividualRow").css("display","table-row")); 
								} 
								else {
									$("#ownershipTypeIndividualRow").css("display","none");
									$("#ownershipTypeSchoolRow").css("display","none");
								}
							 });
						});
					</script>
					<tr id='ownershipTypeRow'>
						<td> 
							<b><?php echo __($guid, 'Ownership Type') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="ownershipType" id="ownershipType" class='ownershipType standardWidth'>
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
					$selectContents .= "<optgroup label='--<?php print __($guid, 'All Users') ?>--'>";
					try {
						$dataSelect = array();
						$sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
						$resultSelect = $connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					} catch (PDOException $e) {
					}
					while ($rowSelect = $resultSelect->fetch()) {
						$expected = '';
						if ($rowSelect['status'] == 'Expected') {
							$expected = ' '.__($guid, '(Expected)');
						}
						$selected = '';
						if ($row['gibbonPersonIDOwnership'] == $rowSelect['gibbonPersonID']) {
							$selected = 'selected';
						}
						$selectContents .= "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')'.$expected.'</option>';
					}
					$selectContents .= '</optgroup>'; ?>
					<tr id="ownershipTypeSchoolRow" <?php if ($row['ownershipType'] != 'School') { echo "style='display: none'"; } ?>>
						<td> 
							<b><?php echo __($guid, 'Main User') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Person the device is assigned to.') ?></span>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipSchool" id="gibbonPersonIDOwnershipSchool" class="standardWidth">
								<?php echo $selectContents ?>
							</select>
						</td>
					</tr>
					<tr id="ownershipTypeIndividualRow" <?php if ($row['ownershipType'] != 'Individual') { echo "style='display: none'"; } ?>>
						<td> 
							<b><?php echo __($guid, 'Owner') ?></b><br/>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipIndividual" id="gibbonPersonIDOwnershipIndividual" class="standardWidth">
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
							<select name="gibbonDepartmentID" id="gibbonDepartmentID" class="standardWidth">
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
					<tr id='bookableRow'>
						<td> 
							<b><?php echo __($guid, 'Bookable As Facility?') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Can item be booked via Facility Booking in Timetable? Useful for laptop carts, etc.') ?></span>
						</td>
						<td class="right">
							<select name="bookable" id="bookable" class="standardWidth">
								<option <?php if ($row['bookable'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo __($guid, 'No') ?>
								<option <?php if ($row['bookable'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo __($guid, 'Yes') ?>
							</select>
						</td>
					</tr>
					<tr id='borrowableRow'>
						<td> 
							<b><?php echo __($guid, 'Borrowable?') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Is item available for loan?') ?></span>
						</td>
						<td class="right">
							<select name="borrowable" id="borrowable" class="borrowable standardWidth">
								<option <?php if ($row['borrowable'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo __($guid, 'Yes') ?>
								<option <?php if ($row['borrowable'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo __($guid, 'No') ?>
							</select>
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR IMAGE -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#borrowable").change(function(){
								if ($('select.borrowable option:selected').val()=="Y" ) {
									$("#statusRowNotBorrowable").css("display","none");
									$("#statusRowBorrowable").slideDown("fast", $("#statusRowBorrowable").css("display","table-row")); 
								} else if ($('select.borrowable option:selected').val()=="N" ) {
									$("#statusRowBorrowable").css("display","none");
									$("#statusRowNotBorrowable").slideDown("fast", $("#statusRowNotBorrowable").css("display","table-row")); 
								}
							 });
						});
					</script>
					<tr id='statusRowBorrowable' <?php if ($row['borrowable'] == 'N') { echo "style='display: none'"; } ?>>
						<td> 
							<b><?php echo __($guid, 'Status') ?>? *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name='statusBorrowable' style='width: 300px' type='text' value='<?php echo $row['status'] ?>' />
						</td>
					</tr>
					<tr id="statusRowNotBorrowable" <?php if ($row['borrowable'] == 'Y') { echo "style='display: none'"; } ?>>
						<td> 
							<b>Status? *</b><br/>
						</td>
						<td class="right">
							<select name="statusNotBorrowable" id="status" class="standardWidth">
								<option <?php if ($row['status'] == 'Available') { echo 'selected'; } ?> value="Available" /> <?php echo __($guid, 'Available') ?>
								<option <?php if ($row['status'] == 'In Use') { echo 'selected'; } ?> value="In Use" /> <?php echo __($guid, 'In Use') ?>
								<option <?php if ($row['status'] == 'Reserved') { echo 'selected'; } ?> value="Reserved" /> <?php echo __($guid, 'Reserved') ?>
								<option <?php if ($row['status'] == 'Decommissioned') { echo 'selected'; } ?> value="Decommissioned" /> <?php echo __($guid, 'Decommissioned') ?>
								<option <?php if ($row['status'] == 'Lost') { echo 'selected'; } ?> value="Lost" /> <?php echo __($guid, 'Lost') ?>
								<option <?php if ($row['status'] == 'Repair') { echo 'selected'; } ?> value="Repair" /> <?php echo __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					
					<script type="text/javascript">
						$(document).ready(function(){
							$("#replacement").change(function(){
								if ($('#replacement option:selected').val()=="Y" ) {
									$("#gibbonSchoolYearIDReplacementRow").slideDown("fast", $("#gibbonSchoolYearIDReplacementRow").css("display","table-row")); 
									$("#replacementCostRow").slideDown("fast", $("#replacementCostRow").css("display","table-row")); 
								}
								else {
									$("#gibbonSchoolYearIDReplacementRow").css("display","none");
									$("#replacementCostRow").css("display","none");
								}
							 });
						});
					</script>
					<tr id='replacementRow'>
						<td> 
							<b><?php echo __($guid, 'Plan Replacement?') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="replacement" id="replacement" class="standardWidth">
								<option <?php if ($row['replacement'] == 'N') { echo 'selected'; } ?> value="N"><?php echo ynExpander($guid, 'N') ?></option>
								<option <?php if ($row['replacement'] == 'Y') { echo 'selected'; } ?> value="Y"><?php echo ynExpander($guid, 'Y') ?></option>
							</select>
						</td>
					</tr>
					<tr id='gibbonSchoolYearIDReplacementRow' <?php if ($row['replacement'] == 'N') { echo "style='display: none'"; } ?>>
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
					<tr id='replacementCostRow' <?php if ($row['replacement'] == 'N') { echo "style='display: none'"; } ?>>
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
					<tr id='physicalConditionRow'>
						<td> 
							<b><?php echo __($guid, 'Physical Condition') ?></b><br/>
						</td>
						<td class="right">
							<select name="physicalCondition" id="physicalCondition" class="standardWidth">
								<option <?php if ($row['physicalCondition'] == '') { echo 'selected'; } ?> value="" />
								<option <?php if ($row['physicalCondition'] == 'As New') { echo 'selected'; } ?> value="As New" /> <?php echo __($guid, 'As New') ?>
								<option <?php if ($row['physicalCondition'] == 'Lightly Worn') { echo 'selected'; } ?> value="Lightly Worn" /> <?php echo __($guid, 'Lightly Worn') ?>
								<option <?php if ($row['physicalCondition'] == 'Moderately Worn') { echo 'selected'; } ?> value="Moderately Worn" /> <?php echo __($guid, 'Moderately Worn') ?>
								<option <?php if ($row['physicalCondition'] == 'Damaged') { echo 'selected'; } ?> value="Damaged" /> <?php echo __($guid, 'Damaged') ?>
								<option <?php if ($row['physicalCondition'] == 'Unusable') { echo 'selected'; } ?> value="Unusable" /> <?php echo __($guid, 'Unusable') ?>
							</select>
						</td>
					</tr>
					
					
					<tr id='commentRow'>
						<td colspan=2> 
							<b><?php echo __($guid, 'Comments/Notes') ?></b> 
							<textarea name='comment' id='comment' rows=10 style='width: 300px'><?php echo htmlPreP($row['comment']) ?></textarea>
						</td>
					</tr>
					
					
					<tr class='break' id='entryDisplayTitleRow'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Type-Specific Details') ?></h3>
						</td>
					</tr>
					
					<?php
                    try {
                        $dataFields = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
                        $sqlFields = "SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name";
                        $resultFields = $connection2->prepare($sqlFields);
                        $resultFields->execute($dataFields);
                    } catch (PDOException $e) {
                    }

					if ($resultFields->rowCount() != 1) {
						echo "<div class='error'>";
						echo __($guid, 'The specified record cannot be found.');
						echo '</div>';
					} else {
						$rowFields = $resultFields->fetch();
						$fields = unserialize($rowFields['fields']);
						$fieldValues = unserialize($row['fields']);
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
								echo "<input maxlength='".$field['options']."' name='field".$fieldName."' id='field".$fieldName."' value='";
								if (isset($fieldValues[$field['name']])) {
									echo htmlPrep($fieldValues[$field['name']]);
								}
								echo "' type='text' style='width: 300px'>";
							} elseif ($field['type'] == 'Select') {
								echo "<select name='field".$fieldName."' id='field".$fieldName."' type='text' style='width: 300px'>";
								if ($field['required'] == 'Y') {
									echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								}
								$options = explode(',', $field['options']);
								foreach ($options as $option) {
									$option = trim($option);
									$selected = '';
									if (isset($fieldValues[$field['name']])) {
										if ($option == $fieldValues[$field['name']]) {
											$selected = 'selected';
										}
									}
									echo "<option $selected value='$option'>$option</option>";
								}
								echo '</select>';
							} elseif ($field['type'] == 'Textarea') {
								echo "<textarea rows='".$field['options']."' name='field".$fieldName."' id='field".$fieldName."' style='width: 300px'>";
								if (isset($fieldValues[$field['name']])) {
									echo htmlPrep($fieldValues[$field['name']]);
								}
								echo '</textarea>';
							} elseif ($field['type'] == 'Date') {
								echo "<input name='field".$fieldName."' id='field".$fieldName."' maxlength=10 value='";
								if (isset($fieldValues[$field['name']])) {
									echo dateConvertBack($guid, $fieldValues[$field['name']]);
								}
								echo "' type='text' style='width: 300px'>";
								echo "<script type='text/javascript'>";
								echo 'var field'.$fieldName."=new LiveValidation('field".$fieldName."');";
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
								echo "$( '#field".$fieldName."' ).datepicker();";
								echo '});';
								echo '</script>';
							} elseif ($field['type'] == 'URL') {
								echo "<input maxlength='".$field['options']."' name='field".$fieldName."' id='field".$fieldName."' value='";
								if (isset($fieldValues[$field['name']])) {
									htmlPrep($fieldValues[$field['name']]);
								}
								echo "' type='text' style='width: 300px'>";
								echo "<script type='text/javascript'>";
								echo 'var field'.$fieldName."=new LiveValidation('field".$fieldName."');";
								echo 'field'.$fieldName.".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http://\" } );";
								echo '</script>';
							}
							echo '</td>';
							echo '</tr>';
							//NEED LIVE VALIDATION
							if ($field['required'] == 'Y') {
								if ($field['type'] == 'Text' or $field['type'] == 'Textarea' or $field['type'] == 'Date' or $field['type'] == 'URL') {
									echo "<script type='text/javascript'>";
									echo 'var field'.$fieldName."=new LiveValidation('field".$fieldName."');";
									echo 'field'.$fieldName.'.add(Validate.Presence);';
									echo '</script>';
								} elseif ($field['type'] == 'Select') {
									echo "<script type='text/javascript'>";
									echo 'var field'.$fieldName."=new LiveValidation('field".$fieldName."');";
									echo 'field'.$fieldName.".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});";
									echo '</script>';
								}
							}
						}
						echo "<script type='text/javascript'>";
						echo '$(document).ready(function(){';
						echo "$('#type').change(function(){";
						foreach ($fields as $field) {
							if ($field['required'] == 'Y') {
								$fieldName = preg_replace('/ /', '', $field['name']);
								echo 'field'.$fieldName.'.disable() ;';
							}
						}
						echo '})';
						echo '});';
						echo '</script>';
					}
					?>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
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
?>