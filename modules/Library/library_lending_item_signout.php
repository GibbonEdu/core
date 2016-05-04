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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_signOut.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonLibraryItemID = $_GET['gibbonLibraryItemID'];

    if ($gibbonLibraryItemID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
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
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_lending.php'>".__($guid, 'Lending & Activity Log')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID'>".__($guid, 'View Item')."</a> > </div><div class='trailEnd'>".__($guid, 'Sign Out').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            if ($row['returnAction'] != '') {
                if ($row['gibbonPersonIDReturnAction'] != '') {
                    try {
                        $dataPerson = array('gibbonPersonID' => $row['gibbonPersonIDReturnAction']);
                        $sqlPerson = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                        $resultPerson = $connection2->prepare($sqlPerson);
                        $resultPerson->execute($dataPerson);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultPerson->rowCount() == 1) {
                        $rowPerson = $resultPerson->fetch();
                        $person = formatName('', htmlPrep($rowPerson['preferredName']), htmlPrep($rowPerson['surname']), 'Student');
                    }
                }

                echo "<div class='warning'>";
                if ($row['returnAction'] == 'Make Available') {
                    echo __($guid, 'This item has been marked to be <u>made available</u> for loan on return.');
                }
                if ($row['returnAction'] == 'Reserve' and $row['gibbonPersonIDReturnAction'] != '') {
                    echo __($guid, "This item has been marked to be <u>reserved</u> for <u>$person</u> on return.");
                }
                if ($row['returnAction'] == 'Decommission' and $row['gibbonPersonIDReturnAction'] != '') {
                    echo __($guid, "This item has been marked to be <u>decommissioned</u> by <u>$person</u> on return.");
                }
                if ($row['returnAction'] == 'Repair' and $row['gibbonPersonIDReturnAction'] != '') {
                    echo __($guid, "This item has been marked to be <u>repaired</u> by <u>$person</u> on return.");
                }
                echo ' '.__($guid, 'You can change this below if you wish.');
                echo '</div>';
            }

            if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_lending_item.php&name='.$_GET['name']."&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=".$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status']."'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/library_lending_item_signoutProcess.php?name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Item Details') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'ID') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="id" value="<?php echo $row['id'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Current Status') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="statusCurrent" id="statusCurrent" value="<?php echo $row['status'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'This Event') ?></h3>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php echo __($guid, 'New Status') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="status" id="status" class="standardWidth">
								<option value="On Loan" /> <?php echo __($guid, 'On Loan') ?>
								<option value="Reserved" /> <?php echo __($guid, 'Reserved') ?>
								<option value="Decommissioned" /> <?php echo __($guid, 'Decommissioned') ?>
								<option value="Lost" /> <?php echo __($guid, 'Lost') ?>
								<option value="Repair" /> <?php echo __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Responsible User') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Who is responsible for this new status?') ?></span>
						</td>
						<td class="right">
							<?php
                            echo "<select name='gibbonPersonIDStatusResponsible' id='gibbonPersonIDStatusResponsible' style='width: 300px'>";
							echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							echo "<optgroup label='--".__($guid, 'Students By Roll Group')."--'>";
							try {
								$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
								$sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							echo '</optgroup>';
							echo "<optgroup label='--".__($guid, 'All Users')."--'>";
							try {
								$dataSelect = array();
								$sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							echo '</optgroup>';
							echo '</select>'; ?>
							<script type="text/javascript">
								var gibbonPersonIDStatusResponsible=new LiveValidation('gibbonPersonIDStatusResponsible');
								gibbonPersonIDStatusResponsible.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<?php
                        $loanLength = getSettingByScope($connection2, 'Library', 'defaultLoanLength');
						if (is_numeric($loanLength) == false or $loanLength < 0) {
							$loanLength = 7;
						}
						?>
						<td> 
							<b><?php echo __($guid, 'Expected Return Date') ?></b><br/>
							<span class="emphasis small"><?php echo sprintf(__($guid, 'Default loan length is %1$s day(s).'), $loanLength) ?></span>
						</td>
						<td class="right">
							<input name="returnExpected" id="returnExpected" maxlength=10 value="<?php echo dateConvertBack($guid, date('Y-m-d', (time() + (24 * 60 * 60 * $loanLength)))) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var returnExpected=new LiveValidation('returnExpected');
								returnExpected.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#returnExpected" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'On Return') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Action') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'What to do when item is next returned.') ?><br/></span>
						</td>
						<td class="right">
							<select name="returnAction" id="returnAction" class="standardWidth">
								<option value="" />
								<option value="Reserve" /> <?php echo __($guid, 'Reserve') ?>
								<option value="Decommission" /> <?php echo __($guid, 'Decommission') ?>
								<option value="Repair" /> <?php echo __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Responsible User') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Who will be responsible for the future status?') ?></span>
						</td>
						<td class="right">
							<?php
                            echo "<select name='gibbonPersonIDReturnAction' id='gibbonPersonIDReturnAction' style='width: 300px'>";
							echo "<option value=''></option>";
							echo "<optgroup label='--".__($guid, 'Students By Roll Group')."--'>";
							try {
								$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
								$sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							echo '</optgroup>';
							echo "<optgroup label='--".__($guid, 'All Users')."--'>";
							try {
								$dataSelect = array();
								$sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
							}
							echo '</optgroup>';
							echo '</select>'; ?>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonLibraryItemID" id="gibbonLibraryItemID" value="<?php echo $gibbonLibraryItemID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="Sign Out">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>