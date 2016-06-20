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

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Finance Data').'</div>';
        echo '</div>';

        if ($highestAction == 'Update Finance Data_any') {
            echo '<p>';
            echo __($guid, 'This page allows a user to request selected finance data updates for any user. If a user does not appear in the list, please visit the Manage Invoicees page to create any missing students.');
            echo '</p>';
        } else {
            echo '<p>';
            echo sprintf(__($guid, 'This page allows any adult with data access permission to request selected finance data updates for any children in their family. If any of your children do not appear in this list, please contact %1$s.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>');
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
        echo __($guid, 'Choose User');
        echo '</h2>';

        $gibbonFinanceInvoiceeID = null;
        if (isset($_GET['gibbonFinanceInvoiceeID'])) {
            $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
        }
        ?>

		<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'Invoicee') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Individual for whom invoices are generated.') ?></span>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonFinanceInvoiceeID">
							<?php
                            if ($highestAction == 'Update Finance Data_any') {
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    if ($gibbonFinanceInvoiceeID == $rowSelect['gibbonFinanceInvoiceeID']) {
                                        echo "<option selected value='".$rowSelect['gibbonFinanceInvoiceeID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
                                    } else {
                                        echo "<option value='".$rowSelect['gibbonFinanceInvoiceeID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
                                    }
                                }
                            } else {
                                try {
                                    $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sqlSelect = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                echo "<option value=''></option>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    try {
                                        $dataSelect2 = array('gibbonFamilyID' => $rowSelect['gibbonFamilyID']);
                                        $sqlSelect2 = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoiceeID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID";
                                        $resultSelect2 = $connection2->prepare($sqlSelect2);
                                        $resultSelect2->execute($dataSelect2);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowSelect2 = $resultSelect2->fetch()) {
                                        if ($gibbonFinanceInvoiceeID == $rowSelect2['gibbonFinanceInvoiceeID']) {
                                            echo "<option selected value='".$rowSelect2['gibbonFinanceInvoiceeID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
                                        } else {
                                            echo "<option value='".$rowSelect2['gibbonFinanceInvoiceeID']."'>".formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
                                        }
                                    }
                                }
                            }
        					?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/data_finance.php">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        if ($gibbonFinanceInvoiceeID != '') {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Finance Data_any') {
                try {
                    $dataSelect = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                $checkCount = $resultSelect->rowCount();
            } else {
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoiceeID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                    }
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonFinanceInvoiceeID == $rowCheck2['gibbonFinanceInvoiceeID']) {
                            ++$checkCount;
                        }
                    }
                }
            }

            if ($checkCount < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;
                try {
                    $data = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'gibbonPersonIDUpdater' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonFinanceInvoiceeUpdate WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
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
                        $data = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                        $sql = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
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
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_financeProcess.php?gibbonFinanceInvoiceeID='.$gibbonFinanceInvoiceeID ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr class='break'>
								<td colspan=2>
									<h4><?php echo __($guid, 'Invoice To') ?></h4>
								</td>
							</tr>

							<script type="text/javascript">
								/* Resource 1 Option Control */
								$(document).ready(function(){
									if ($('input[name=invoiceTo]:checked').val()=="Family" ) {
										$("#companyNameRow").css("display","none");
										$("#companyContactRow").css("display","none");
										$("#companyAddressRow").css("display","none");
										$("#companyEmailRow").css("display","none");
										$("#companyCCFamilyRow").css("display","none");
										$("#companyPhoneRow").css("display","none");
										$("#companyAllRow").css("display","none");
										$("#companyCategoriesRow").css("display","none");
										companyEmail.disable() ;
										companyAddress.disable() ;
										companyContact.disable() ;
										companyName.disable() ;
									}
									else {
										if ($('input[name=companyAll]:checked').val()=="Y" ) {
											$("#companyCategoriesRow").css("display","none");
										}
									}

									$(".invoiceTo").click(function(){
										if ($('input[name=invoiceTo]:checked').val()=="Family" ) {
											$("#companyNameRow").css("display","none");
											$("#companyContactRow").css("display","none");
											$("#companyAddressRow").css("display","none");
											$("#companyEmailRow").css("display","none");
											$("#companyCCFamilyRow").css("display","none");
											$("#companyPhoneRow").css("display","none");
											$("#companyAllRow").css("display","none");
											$("#companyCategoriesRow").css("display","none");
											companyEmail.disable() ;
											companyAddress.disable() ;
											companyContact.disable() ;
											companyName.disable() ;
										} else {
											$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row"));
											$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row"));
											$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row"));
											$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row"));
											$("#companyCCFamilyRow").slideDown("fast", $("#companyCCFamilyRow").css("display","table-row"));
											$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row"));
											$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row"));
											if ($('input[name=companyAll]:checked').val()=="Y" ) {
												$("#companyCategoriesRow").css("display","none");
											} else {
												$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
											}
											companyEmail.enable() ;
											companyAddress.enable() ;
											companyContact.enable() ;
											companyName.enable() ;
										}
									 });

									 $(".companyAll").click(function(){
										if ($('input[name=companyAll]:checked').val()=="Y" ) {
											$("#companyCategoriesRow").css("display","none");
										} else {
											$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row"));
										}
									 });
								});
							</script>
							<tr id="familyRow">
								<td colspan=2'>
									<p><?php echo __($guid, 'If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Send Invoices To') ?></b><br/>
								</td>
								<td class="right">
									<input <?php if ($row['invoiceTo'] == 'Family' or $row['invoiceTo'] == '') { echo 'checked'; } ?> type="radio" name="invoiceTo" value="Family" class="invoiceTo" /> <?php echo __($guid, 'Family') ?>
									<input <?php if ($row['invoiceTo'] == 'Company') { echo 'checked'; } ?> type="radio" name="invoiceTo" value="Company" class="invoiceTo" /> <?php echo __($guid, 'Company') ?>
								</td>
							</tr>
							<tr id="companyNameRow">
								<td>
									<b><?php echo __($guid, 'Company Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyName" id="companyName" maxlength=100 value="<?php echo $row['companyName'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var companyName=new LiveValidation('companyName');
										companyName.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyContactRow">
								<td>
									<b><?php echo __($guid, 'Company Contact Person') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyContact" id="companyContact" maxlength=100 value="<?php echo $row['companyContact'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var companyContact=new LiveValidation('companyContact');
										companyContact.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyAddressRow">
								<td>
									<b><?php echo __($guid, 'Company Address') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php echo $row['companyAddress'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var companyAddress=new LiveValidation('companyAddress');
										companyAddress.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyEmailRow">
								<td>
									<b><?php echo __($guid, 'Company Emails') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Comma-separated list of email address.') ?></span>
								</td>
								<td class="right">
									<input name="companyEmail" id="companyEmail" value="<?php echo $row['companyEmail'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var companyEmail=new LiveValidation('companyEmail');
										companyEmail.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyCCFamilyRow">
								<td>
									<b><?php echo __($guid, 'CC Family?') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Should the family be sent a copy of billing emails?') ?></span>
								</td>
								<td class="right">
									<select name="companyCCFamily" id="companyCCFamily" class="standardWidth">
										<option <?php if ($row['companyCCFamily'] == 'N') { echo 'selected'; } ?> value="N" /> <?php echo __($guid, 'No') ?>
										<option <?php if ($row['companyCCFamily'] == 'Y') { echo 'selected'; } ?> value="Y" /> <?php echo __($guid, 'Yes') ?>
									</select>
								</td>
							</tr>
							<tr id="companyPhoneRow">
								<td>
									<b><?php echo __($guid, 'Company Phone') ?></b><br/>
								</td>
								<td class="right">
									<input name="companyPhone" id="companyPhone" maxlength=20 value="<?php echo $row['companyPhone'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<?php
                            try {
                                $dataCat = array();
                                $sqlCat = "SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
                                $resultCat = $connection2->prepare($sqlCat);
                                $resultCat->execute($dataCat);
                            } catch (PDOException $e) {
                            }
							if ($resultCat->rowCount() < 1) {
								echo '<input type="hidden" name="companyAll" value="Y" class="companyAll"/>';
							} else {
								?>
								<tr id="companyAllRow">
									<td>
										<b><?php echo __($guid, 'Company All?') ?></b><br/>
										<span class="emphasis small"><?php echo __($guid, 'Should all items be billed to the specified company, or just some?') ?></span>
									</td>
									<td class="right">
										<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row['companyAll'] == 'Y' or $row['companyAll'] == '') { echo 'checked'; } ?> /> <?php echo __($guid, 'All') ?>
										<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row['companyAll'] == 'N') { echo 'checked'; } ?> /> <?php echo __($guid, 'Selected') ?>
									</td>
								</tr>
								<tr id="companyCategoriesRow">
									<td>
										<b><?php echo __($guid, 'Company Fee Categories') ?></b><br/>
										<span class="emphasis small"><?php echo __($guid, 'If the specified company is not paying all fees, which categories are they paying?') ?></span>
									</td>
									<td class="right">
										<?php
                                        while ($rowCat = $resultCat->fetch()) {
                                            $checked = '';
                                            if (strpos($row['gibbonFinanceFeeCategoryIDList'], $rowCat['gibbonFinanceFeeCategoryID']) !== false) {
                                                $checked = 'checked';
                                            }
                                            echo $rowCat['name']." <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='".$rowCat['gibbonFinanceFeeCategoryID']."'/><br/>";
                                        }
										$checked = '';
										if (strpos($row['gibbonFinanceFeeCategoryIDList'], '0001') !== false) {
											$checked = 'checked';
										}
										echo __($guid, 'Other')." <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>";
										?>
									</td>
								</tr>
								<?php
							}
							?>

							<tr>
								<td>
									<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
								</td>
								<td class="right">
									<?php
                                    if ($existing) {
                                        echo "<input type='hidden' name='existing' value='".$row['gibbonFinanceInvoiceeUpdateID']."'>";
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
