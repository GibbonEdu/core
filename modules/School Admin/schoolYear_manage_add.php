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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYear_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/schoolYear_manage.php'>" . __($guid, 'Manage School Years') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add School Year') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/schoolYear_manage_addProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
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
					<b><?php print __($guid, 'Status') ?> *</b>
				</td>
				<td class="right">
					<select class="standardWidth" name="status">
						<option value="Past"><?php print __($guid, 'Past') ?></option>
						<option value="Current"><?php print __($guid, 'Current') ?></option>
						<option value="Upcoming" selected><?php print __($guid, 'Upcoming') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Sequence Number') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
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
					<b><?php print __($guid, 'First Day') ?> *</b><br/>
					<span class="emphasis small"><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></span>
				</td>
				<td class="right">
					<input name="firstDay" id="firstDay" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var firstDay=new LiveValidation('firstDay');
						firstDay.add(Validate.Presence);
						firstDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<b><?php print __($guid, 'Last Day') ?> *</b><br/>
					<span class="emphasis small"><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></span>
				</td>
				<td class="right">
					<input name="lastDay" id="lastDay" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var lastDay=new LiveValidation('lastDay');
						lastDay.add(Validate.Presence);
						lastDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>