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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgetCycles_manage.php'>".__($guid, 'Manage Budget Cycles')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Budget Cycle').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'warning1' => 'Your request was successful, but some data was not properly saved.'));
    }

    //Check if school year specified
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    if ($gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
            $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
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
            //Let's go!
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/budgetCycles_manage_editProcess.php?gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=9 value="<?php if (isset($row['name'])) { echo htmlPrep($row['name']); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Status') ?> *</b>
						</td>
						<td class="right">
							<select class="standardWidth" name="status">
								<option <?php if ($row['status'] == 'Past') { echo 'selected '; } ?>value="Past"><?php echo __($guid, 'Past') ?></option>
								<option <?php if ($row['status'] == 'Current') { echo 'selected '; } ?>value="Current"><?php echo __($guid, 'Current') ?></option>
								<option <?php if ($row['status'] == 'Upcoming') { echo 'selected '; } ?>value="Upcoming"><?php echo __($guid, 'Upcoming') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=3 value="<?php if (isset($row['sequenceNumber'])) { echo htmlPrep($row['sequenceNumber']); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Numericality);
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="dateStart" id="dateStart" maxlength=10 value="<?php if (isset($row['dateStart'])) {
    echo dateConvertBack($guid, $row['dateStart']); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateStart=new LiveValidation('dateStart');
								dateStart.add(Validate.Presence);
								dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#dateStart" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'End Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php if (isset($row['dateEnd'])) { echo dateConvertBack($guid, $row['dateEnd']); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var dateEnd=new LiveValidation('dateEnd');
								dateEnd.add(Validate.Presence);
								dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#dateEnd" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php echo __($guid, 'Budget Allocations') ?></h3>
						</td>
					</tr>
					<?php
                    try {
                        $dataBudget = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                        $sqlBudget = 'SELECT gibbonFinanceBudget.*, value FROM gibbonFinanceBudget LEFT JOIN gibbonFinanceBudgetCycleAllocation ON (gibbonFinanceBudgetCycleAllocation.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID) ORDER BY name';
                        $resultBudget = $connection2->prepare($sqlBudget);
                        $resultBudget->execute($dataBudget);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
					if ($resultBudget->rowCount() < 1) {
						echo '<tr>';
						echo '<td colspan=2>';
						echo "<div class='error'>";
						echo __($guid, 'There are no records to display.');
						echo '</div>';
						echo '</td>';
						echo '</tr>';
					} else {
						while ($rowBudget = $resultBudget->fetch()) {
							?>
							<tr>
								<td> 
									<b><?php echo $rowBudget['name'] ?> *</b><br/>
									<span style="font-size: 90%">
										<i>
										<?php
                                        if ($_SESSION[$guid]['currency'] != '') {
                                            echo sprintf(__($guid, 'Numeric value in %1$s.'), $_SESSION[$guid]['currency']);
                                        } else {
                                            echo __($guid, 'Numeric value.');
                                        }
                    					?>
										</i>
									</span>
								</td>
								<td class="right">
									<input name="values[]" id="values" maxlength=15 value="<?php if (is_null($rowBudget['value'])) { echo '0.00'; } else { echo $rowBudget['value']; } ?>" type="text" class="standardWidth">
									<input type="hidden" name="gibbonFinanceBudgetIDs[]" value="<?php echo $rowBudget['gibbonFinanceBudgetID'] ?>">
									<script type="text/javascript">
										var values=new LiveValidation('values');
										values.add(Validate.Presence);
										values.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									</script>
								</td>
							</tr>
						<?php
						}
					}
					?>
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