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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/schoolYear_manage.php'>".__($guid, 'Manage School Years')."</a> > </div><div class='trailEnd'>".__($guid, 'Add School Year').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/schoolYear_manage_edit.php&gibbonSchoolYearID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/schoolYear_manage_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=9 value="" type="text" class="standardWidth">
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
						<option value="Past"><?php echo __($guid, 'Past') ?></option>
						<option value="Current"><?php echo __($guid, 'Current') ?></option>
						<option value="Upcoming" selected><?php echo __($guid, 'Upcoming') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
				</td>
				<td class="right">
					<input name="sequenceNumber" id="sequenceNumber" maxlength=3 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var sequenceNumber=new LiveValidation('sequenceNumber');
						sequenceNumber.add(Validate.Numericality);
						sequenceNumber.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'First Day') ?> *</b><br/>
					<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="firstDay" id="firstDay" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var firstDay=new LiveValidation('firstDay');
						firstDay.add(Validate.Presence);
						firstDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							$( "#firstDay" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Last Day') ?> *</b><br/>
					<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="lastDay" id="lastDay" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var lastDay=new LiveValidation('lastDay');
						lastDay.add(Validate.Presence);
						lastDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							$( "#lastDay" ).datepicker();
						});
					</script>
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
?>