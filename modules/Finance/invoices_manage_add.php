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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Fees & Invoices').'</div>';
    echo '</div>';

    $error3 = __($guid, 'Some aspects of your update failed, effecting the following areas:').'<ul>';
    if (!empty($_GET['studentFailCount'])) {
        $error3 .= '<li>'.$_GET['studentFailCount'].' '.__($guid, 'students encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFailCount'].' '.__($guid, 'invoices encountered problems.').'</li>';
    }
    if (!empty($_GET['invoiceFeeFailCount'])) {
        $error3 .= '<li>'.$_GET['invoiceFeeFailCount'].' '.__($guid, 'fee entires encountered problems.').'</li>';
    }
    $error3 .= '</ul>'.__($guid, 'It is recommended that you remove all pending invoices and try to recreate them.');

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => $error3));
    }

    echo '<p>';
    echo __($guid, 'Here you can add fees to one or more students. These fees will be added to an existing invoice or used to form a new invoice, depending on the specified billing schedule and other details.');
    echo '</p>';

    //Check if school year specified
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $status = isset($_GET['status'])? $_GET['status'] : '';
    $gibbonFinanceInvoiceeID = isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '';
    $monthOfIssue = isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '';
    $gibbonFinanceBillingScheduleID = isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '';
    $gibbonFinanceFeeCategoryID = isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '';
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $linkParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID'); 
        
        $form = Form::create('invoice', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_addProcess.php?'.http_build_query($linkParams));
                        
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $data= array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name AS schoolYear FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);
        $schoolYearName = $result->rowCount() > 0? $result->fetchColumn(0) : '';

        $form->addRow()->addHeading(__('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('schoolYear', __('School Year'));
            $row->addTextField('schoolYear')->isRequired()->readonly()->setValue($schoolYearName);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeIDs', __('Invoicees'))->append(sprintf(__('Visit %1$sManage Invoicees%2$s to automatically generate missing students.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoicees_manage.php'>", '</a>'));
            $row->addSelect('gibbonFinanceInvoiceeIDs')->isRequired()->selectMultiple();

        $scheduling = array('Scheduled' => __('Scheduled'), 'Ad Hoc' => __('Ad Hoc'));
        $row = $form->addRow();
            $row->addLabel('scheduling', __('Scheduling'))->description(__('When using scheduled, invoice due date is linked to and determined by the schedule.'));
            $row->addRadio('scheduling')->fromArray($scheduling)->isRequired()->inline()->checked('Scheduled');

        $form->toggleVisibilityByClass('schedulingScheduled')->onRadio('scheduling')->when('Scheduled');
        $form->toggleVisibilityByClass('schedulingAdHoc')->onRadio('scheduling')->when('Ad Hoc');

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFinanceBillingScheduleID as value, name FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
        $row = $form->addRow()->addClass('schedulingScheduled');
            $row->addLabel('gibbonFinanceBillingScheduleID', __('Billing Schedule'));
            $row->addSelect('gibbonFinanceBillingScheduleID')
                ->fromQuery($pdo, $sql, $data)
                ->isRequired()
                ->placeholder()
                ->selected($gibbonFinanceBillingScheduleID);

        $row = $form->addRow()->addClass('schedulingAdHoc');
            $row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__('For fees added to existing invoice, specified date will override existing due date.'));
            $row->addDate('invoiceDueDate')->isRequired();

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))->description(__('Notes will be displayed on the final invoice and receipt.'));
            $row->addTextArea('notes')->setRows(5);

        $form->addRow()->addHeading(__('Fees'));

        // Fee selector
        $sql = "SELECT gibbonFinanceFeeCategory.name as groupBy, gibbonFinanceFee.gibbonFinanceFeeID as value, gibbonFinanceFee.name FROM gibbonFinanceFee 
                JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) ORDER BY name";
        $feeSelector = $form->getFactory()->createSelect('addNewFee')->addClass('addBlock')->addData('event', 'change')
            ->fromArray(array('' => __('Choose a fee to add it')))
            ->fromArray(array('Ad Hoc Fee' => __('Ad Hoc Fee')))
            ->fromQuery($pdo, $sql, array(), 'groupBy');

        // Reusable block builder
        $buildFeesBlock = function($feeName = '', $feeValue = '', $description = '', $readonly = false) use ($form) {
            $block = $form->getFactory()->createTable()->setClass('blank');
            $row = $block->addRow();
                $row->addTextField('name')->setClass('mediumWidth floatNone')->placeholder(__('Fee Name'))->setValue($feeName)->readonly($readonly);
                
            $col = $block->addRow()->addColumn()->setClass('inline');
                $col->addTextField('fee')->setClass('shortWidth floatNone')->placeholder(__('Value'))->setValue($feeValue)->readonly($readonly);
                
            $col = $block->addRow()->addClass('showHide displayNone')->addColumn();
                $col->addLabel('description', __('Description'));
                $col->addTextArea('description')->setRows(3)->setClass('floatNone')->setValue($description);

            return $block;
        };

        $row = $form->addRow();
            $customBlocks = $row->addCustomBlocks('feesBlock', $buildFeesBlock(), $gibbon->session)
                ->placeholder(__('Fees will be listed here...'))
                ->addToolInput($feeSelector)
                ->addBlockButton(__('Show/Hide'), 'plus.png', 'showHide');

        $customBlocks->addBlock( 'foo', $buildFeesBlock('Test', '123', 'This is a test.', true) );

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
        ?>

        <script>
            function showHide(block) {
                console.log("Show/Hide " + block.blockNumber);
                block.find('.showHide').toggle('slideDown');
            }
        </script>

		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/invoices_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr class='break'>
					<td colspan=2>
						<h3><?php echo __($guid, 'Basic Information') ?></h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'School Year') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
					</td>
					<td class="right">
						<?php
                        $yearName = '';
						try {
							$dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
							$sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
							$resultYear = $connection2->prepare($sqlYear);
							$resultYear->execute($dataYear);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
						if ($resultYear->rowCount() == 1) {
							$rowYear = $resultYear->fetch();
							$yearName = $rowYear['name'];
						}
						?>
						<input readonly name="yearName" id="yearName" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var yearName=new LiveValidation('yearName');
							yearname2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Invoicees') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?><br/><?php echo sprintf(__($guid, 'Visit %1$sManage Invoicees%2$s to automatically generate missing students.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoicees_manage.php'>", '</a>') ?></span>
					</td>
					<td class="right">
						<select name="gibbonFinanceInvoiceeIDs[]" id="gibbonFinanceInvoiceeIDs[]" multiple class='standardWidth' style="height: 150px">
							<optgroup label='--<?php echo __($guid, 'All Enrolled Students by Roll Group') ?>--'>
							<?php
                            $students = array();
							$count = 0;
							try {
								$dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
								$sqlSelect = "SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.name AS name, dayType FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup, gibbonFinanceInvoicee WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonFinanceInvoiceeID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
								$students[$count]['gibbonFinanceInvoiceeID'] = $rowSelect['gibbonFinanceInvoiceeID'];
								$students[$count]['student'] = formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true);
								$students[$count]['rollGroup'] = htmlPrep($rowSelect['name']);
								$students[$count]['dayType'] = htmlPrep($rowSelect['dayType']);
								++$count;
							}
							?>
							</optgroup>
							<?php
                            $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
							if ($dayTypeOptions != '') {
								$dayTypes = explode(',', $dayTypeOptions);
								foreach ($dayTypes as $dayType) {
									echo "<optgroup label='--$dayType ".__($guid, 'Students by Roll Groups')."--'>";
									foreach ($students as $student) {
										if ($student['dayType'] == $dayType) {
											echo "<option value='".$student['gibbonFinanceInvoiceeID']."'>".$student['rollGroup'].' - '.$student['student'].'</option>';
										}
									}
									echo '</optgroup>';
								}
							}
							?>
							<optgroup label='--<?php echo __($guid, 'All Enrolled Students by Alphabet') ?>--'>
							<?php
                            $students = array();
							$count = 0;
							try {
								$dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
								$sqlSelect = "SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.name AS name, dayType FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup, gibbonFinanceInvoicee WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonFinanceInvoiceeID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' - '.htmlPrep($rowSelect['name']).'</option>';
							}
							?>
							</optgroup>

						</select>
					</td>
				</tr>
				<?php //BILLING TYPE CHOOSER ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Scheduling') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'When using scheduled, invoice due date is linked to and determined by the schedule.') ?></span>
					</td>
					<td class="right">
						<input checked type="radio" name="scheduling" class="scheduling" value="Scheduled" /> Scheduled
						<input type="radio" name="scheduling" class="scheduling" value="Ad Hoc" /> Ad Hoc
					</td>
				</tr>
				<script type="text/javascript">
					$(document).ready(function(){
						$("#adHocRow").css("display","none");
						invoiceDueDate.disable() ;
						$("#schedulingRow").slideDown("fast", $("#schedulingRow").css("display","table-row"));

						$(".scheduling").click(function(){
							if ($('input[name=scheduling]:checked').val()=="Scheduled" ) {
								$("#adHocRow").css("display","none");
								invoiceDueDate.disable() ;
								$("#schedulingRow").slideDown("fast", $("#schedulingRow").css("display","table-row"));
								gibbonFinanceBillingScheduleID.enable() ;
							} else {
								$("#schedulingRow").css("display","none");
								gibbonFinanceBillingScheduleID.disable() ;
								$("#adHocRow").slideDown("fast", $("#adHocRow").css("display","table-row"));
								invoiceDueDate.enable() ;
							}
						 });
					});
				</script>

				<tr id="schedulingRow">
					<td>
						<b><?php echo __($guid, 'Billing Schedule') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="gibbonFinanceBillingScheduleID" id="gibbonFinanceBillingScheduleID" class="standardWidth">
							<?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							try {
								$dataSelect = array();
								$sqlSelect = "SELECT * FROM gibbonFinanceBillingSchedule WHERE active='Y' ORDER BY name";
								$resultSelect = $connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							} catch (PDOException $e) {
							}
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['gibbonFinanceBillingScheduleID']."'>".$rowSelect['name'].'</option>';
							}
							?>
						</select>
						<script type="text/javascript">
							var gibbonFinanceBillingScheduleID=new LiveValidation('gibbonFinanceBillingScheduleID');
							gibbonFinanceBillingScheduleID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr id="adHocRow">
					<td>
						<b><?php echo __($guid, 'Invoice Due Date') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'For fees added to existing invoice, specified date will override existing due date.') ?></span>
					</td>
					<td class="right">
						<input name="invoiceDueDate" id="invoiceDueDate" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
							var invoiceDueDate=new LiveValidation('invoiceDueDate');
							invoiceDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							invoiceDueDate.add(Validate.Presence);
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#invoiceDueDate" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<b><?php echo __($guid, 'Notes') ?></b><br/>
                        <span class="emphasis small"><?php echo __($guid, 'Notes will be displayed on the final invoice and receipt.') ?></span>
						<textarea name='notes' id='notes' rows=5 style='width: 300px'></textarea>
					</td>
				</tr>

				<tr class='break'>
					<td colspan=2>
						<h3><?php echo __($guid, 'Fees') ?></h3>
					</td>
				</tr>
				<?php
                $type = 'fee';
        		?>
				<style>
					#<?php echo $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
					#<?php echo $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
					div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
					html>body #<?php echo $type ?> li { min-height: 58px; line-height: 1.2em; }
					.<?php echo $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
					.<?php echo $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
				</style>
				<tr>
					<td colspan=2>
						<div class="fee" id="fee" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
							<div id="feeOuter0">
								<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'><?php echo __($guid, 'Fees will be listed here...') ?></div>
							</div>
						</div>
						<div style='width: 100%; padding: 0px 0px 0px 0px'>
							<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
								<table class='blank' cellspacing='0' style='width: 100%'>
									<tr>
										<td style='width: 50%'>
											<script type="text/javascript">
												var feeCount=1 ;
											</script>
											<select id='newFee' onChange='feeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
												<option class='all' value='0'><?php echo __($guid, 'Choose a fee to add it') ?></option>
												<?php
                                                echo "<option value='Ad Hoc'>Ad Hoc Fee</option>";
												$switchContents = 'case "Ad Hoc": ';
												$switchContents .= "$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
												$switchContents .= '$("#feeOuter" + feeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Finance/invoices_manage_add_blockFeeAjax.php","mode=add&id=" + feeCount + "&feeType='.urlencode('Ad Hoc').'&gibbonFinanceFeeID=&name='.urlencode('Ad Hoc Fee').'&description=&gibbonFinanceFeeCategoryID=1&fee=") ;';
												$switchContents .= 'feeCount++ ;';
												$switchContents .= "$('#newFee').val('0');";
												$switchContents .= 'break;';
												$currentCategory = '';
												$lastCategory = '';
												for ($i = 0; $i < 2; ++$i) {
													try {
														$dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
														if ($i == 0) {
															$sqlSelect = "SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name";
														} else {
															$sqlSelect = "SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name";
														}
														$resultSelect = $connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													} catch (PDOException $e) {
														echo "<div class='error'>".$e->getMessage().'</div>';
													}
													while ($rowSelect = $resultSelect->fetch()) {
														$currentCategory = $rowSelect['category'];
														if (($currentCategory != $lastCategory) and $currentCategory != '') {
															echo "<optgroup label='--".$currentCategory."--'>";
															$categories[$categoryCount] = $currentCategory;
															++$categoryCount;
														}
														echo "<option value='".$rowSelect['gibbonFinanceFeeID']."'>".$rowSelect['name'].'</option>';
														$switchContents .= 'case "'.$rowSelect['gibbonFinanceFeeID'].'": ';
														$switchContents .= "$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
														$switchContents .= '$("#feeOuter" + feeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Finance/invoices_manage_add_blockFeeAjax.php","mode=add&id=" + feeCount + "&feeType=Standard&gibbonFinanceFeeID='.urlencode($rowSelect['gibbonFinanceFeeID']).'&name='.urlencode($rowSelect['name']).'&description='.urlencode($rowSelect['description']).'&gibbonFinanceFeeCategoryID='.urlencode($rowSelect['gibbonFinanceFeeCategoryID']).'&fee='.urlencode($rowSelect['fee']).'&category='.urlencode($rowSelect['category']).'") ;';
														$switchContents .= 'feeCount++ ;';
														$switchContents .= "$('#newFee').val('0');";
														$switchContents .= 'break;';
														$lastCategory = $rowSelect['category'];
													}
												}
												?>
											</select>
											<script type='text/javascript'>
												function feeDisplayElements(number) {
													$("#<?php echo $type ?>Outer0").css("display", "none") ;
													switch(number) {
														<?php echo $switchContents ?>
													}
												}
											</script>
										</td>
									</tr>
								</table>
							</div>
						</div>
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
?>
