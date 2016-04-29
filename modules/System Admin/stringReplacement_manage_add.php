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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/stringReplacement_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>" . __($guid, 'Manage String Replacements') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add String') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	
	if ($search!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/stringReplacement_manage_addProcess.php?search=$search" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td> 
					<b><?php print __($guid, 'Original String') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="original" id="original" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var original=new LiveValidation('original');
						original.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Replacement String') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="replacement" id="replacement" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var replacement=new LiveValidation('replacement');
						replacement.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Mode') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="mode" id="mode" class="standardWidth">
						<?php
						print "<option value=\"Whole\">" . __($guid, 'Whole') . "</option>" ;
						print "<option value=\"Partial\">" . __($guid, 'Partial') . "</option>" ;
						?>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'Case Sensitive') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="caseSensitive" id="caseSensitive" class="standardWidth">
						<?php
						print "<option value='N'>" . ynExpander($guid, 'N') . "</option>" ;
						print "<option value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
						?>
					</select>
				</td>
			</tr>	
			<tr>
				<td> 
					<b><?php print __($guid, 'Priority') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, "Higher priorities are substituted first.") ?></span>
				</td>
				<td class="right">
					<input name="priority" id="priority" maxlength=2 value="0" type="text" class="standardWidth">
					<script type="text/javascript">
						var priority=new LiveValidation('priority');
						priority.add(Validate.Presence);
						priority.add(Validate.Numericality);
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