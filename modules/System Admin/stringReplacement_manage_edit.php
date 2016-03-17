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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/stringReplacement_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/stringReplacement_manage.php'>" . __($guid, 'Manage String Replacements') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit String') . "</div>" ;
	print "</div>" ;
	
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonStringID=$_GET["gibbonStringID"] ;
	if ($gibbonStringID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonStringID"=>$gibbonStringID); 
			$sql="SELECT * FROM gibbonString WHERE gibbonStringID=:gibbonStringID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/stringReplacement_manage_editProcess.php?gibbonStringID=" . $row["gibbonStringID"] . "&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print __($guid, 'Original String') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="original" id="original" maxlength=100 value="<?php print htmlPrep($row["original"]) ?>" type="text" style="width: 300px">
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
							<input name="replacement" id="replacement" maxlength=100 value="<?php print htmlPrep($row["replacement"]) ?>" type="text" style="width: 300px">
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
							<select name="mode" id="mode" style="width: 302px">
								<?php
								$selected="" ;
								if ($row["mode"]=="Whole") {
									$selected="selected" ;
								}
								print "<option $selected value=\"Whole\">" . __($guid, 'Whole') . "</option>" ;
								$selected="" ;
								if ($row["mode"]=="Partial") {
									$selected="selected" ;
								}
								print "<option $selected value=\"Partial\">" . __($guid, 'Partial') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
			
					<tr>
						<td> 
							<b><?php print __($guid, 'Case Sensitive') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="caseSensitive" id="caseSensitive" style="width: 302px">
								<?php
								$selected="" ;
								if ($row["caseSensitive"]=="N") {
									$selected="selected" ;
								}
								print "<option $selected value='N'>" . ynExpander($guid, 'N') . "</option>" ;
								$selected="" ;
								if ($row["caseSensitive"]=="Y") {
									$selected="selected" ;
								}
								print "<option $selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Priority') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, "Higher priorities are substituted first.") ?></i></span>
						</td>
						<td class="right">
							<input name="priority" id="priority" maxlength=2 value="<?php print htmlPrep($row["priority"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var priority=new LiveValidation('priority');
								priority.add(Validate.Presence);
								priority.add(Validate.Numericality);
							</script>
						</td>
					</tr>	
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
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
	}
}
?>