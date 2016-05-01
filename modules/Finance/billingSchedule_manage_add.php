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

if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Billing Schedule')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Entry').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/billingSchedule_manage_edit.php&gibbonFinanceBillingScheduleID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }
        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/billingSchedule_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
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
						<b><?php echo __($guid, 'Name') ?> *</b><br/>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=100 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var name2=new LiveValidation('name');
							name2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Active') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="active" id="active" class="standardWidth">
							<option value="Y"><?php echo __($guid, 'Yes') ?></option>
							<option value="N"><?php echo __($guid, 'No') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Description') ?></b><br/>
					</td>
					<td class="right">
						<textarea name='description' id='description' rows=5 style='width: 300px'></textarea>
					</td>
				</tr>
				
				<tr>
					<td> 
						<b><?php echo __($guid, 'Invoice Issue Date') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Intended issue date.').'<br/>'.__($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
        ?><br/></span>
					</td>
					<td class="right">
						<input name="invoiceIssueDate" id="invoiceIssueDate" maxlength=10 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var invoiceIssueDate=new LiveValidation('invoiceIssueDate');
							invoiceIssueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							invoiceIssueDate.add(Validate.Presence);
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#invoiceIssueDate" ).datepicker();
							});
						</script>
					</td>
				</tr>
				
				<tr>
					<td> 
						<b>Invoice Due Date *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Final Payment Date.').'<br/>'.__($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
        ?><br/></span>
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
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
        ?></span>
					</td>
					<td class="right">
						<input name="gibbonFinanceBillingScheduleID" id="gibbonFinanceBillingScheduleID" value="<?php echo $gibbonFinanceBillingScheduleID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit');
        ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

    }
}
?>