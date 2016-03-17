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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_archive.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Archive Records') . "</div>" ;
	print "</div>" ;
	
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
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 

	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/in_archiveProcess.php"?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b><?php print __($guid, 'Delete Current Plans?') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Deletes Individual Education Plan fields only, not Individual Needs Status fields.') ?></i></span>
				</td>
				<td class="right">
					<select name="deleteCurrentPlans" id="deleteCurrentPlans" style="width: 302px">
						<option value="N"><?php print ynExpander($guid, 'N') ?></option>
						<option value="Y"><?php print ynExpander($guid, 'Y') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Archive Title') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input type='text' maxlength=50 name="title" id="title" style="width: 300px" value=''/>
					<script type="text/javascript">
						var title=new LiveValidation('title');
						title.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td style='width: 275px; vertical-align: top'> 
					<b><?php print __($guid, 'Students') ?> *</b><br/>
				</td>
				<td class="right">
					<?php
					print "<fieldset style='border: none'>" ;
					?>
					<script type="text/javascript">
						$(function () {
							$('.checkall').click(function () {
								$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
							});
						});
					</script>
					<?php
					try {
						$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlSelect="SELECT surname, preferredName, gibbonIN.* FROM gibbonPerson JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
							print $e->getMessage();
						print "</div>" ;
					}
					print __($guid, "All/None") . " <input type='checkbox' class='checkall'><br/>" ;
					if ($resultSelect->rowCount()<1) {
						print "<i>" . __($guid, 'No year groups available.') . "</i>" ;
					}
					else {
						while ($rowSelect=$resultSelect->fetch()) {
							print formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student", TRUE) . " <input type='checkbox' value='" . $rowSelect["gibbonPersonID"] . "' name='gibbonPersonID[]'><br/>" ; 
						}
					}
					print "</fieldset>" ;
					?>
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
?>