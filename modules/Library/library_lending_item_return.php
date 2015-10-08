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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_return.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonLibraryItemEventID=$_GET["gibbonLibraryItemEventID"] ;
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;
	if ($gibbonLibraryItemEventID=="" OR $gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID, "gibbonLibraryItemEventID"=>$gibbonLibraryItemEventID); 
			$sql="SELECT gibbonLibraryItemEvent.*, gibbonLibraryItem.name AS name, gibbonLibraryItem.id FROM gibbonLibraryItem JOIN gibbonLibraryItemEvent ON (gibbonLibraryItem.gibbonLibraryItemID=gibbonLibraryItemEvent.gibbonLibraryItemID) WHERE gibbonLibraryItemEvent.gibbonLibraryItemID=:gibbonLibraryItemID AND gibbonLibraryItemEvent.gibbonLibraryItemEventID=:gibbonLibraryItemEventID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending.php'>" . _('Lending & Activity Log') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID'>" . _('View Item') . "</a> > </div><div class='trailEnd'>" . _('Return Item') . "</div>" ;
			print "</div>" ;
			
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail4") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending_item.php&name=" . $_GET["name"] . "&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "'>" . _('Back') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_returnProcess.php?gibbonLibraryItemEventID=$gibbonLibraryItemEventID&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3><?php print _('Item Details') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('ID') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="name" id="id" value="<?php print $row["id"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php print $row["name"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Current Status') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="statusCurrent" id="statusCurrent" value="<?php print $row["status"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2>
							<h3><?php print _('On Return') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<p><?php print _('The new status will be set to "Returned" unless the fields below are completed:') ?></p>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Action') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Previously requested action.') ?><br/></i></span>
						</td>
						<td class="right">
							<select name="returnAction" id="returnAction" style="width: 302px">
								<option value="" />
								<option <?php if ($row["returnAction"]=="Reserve") { print "selected" ; } ?> value="Reserve" /> <?php print _('Reserve') ?>
								<option <?php if ($row["returnAction"]=="Decommission") { print "selected" ; } ?> value="Decommission" /> <?php print _('Decommission') ?>
								<option <?php if ($row["returnAction"]=="Repair") { print "selected" ; } ?> value="Repair" /> <?php print _('Repair') ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Responsible User') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Who will be responsible for the future status?') ?></i></span>
						</td>
						<td class="right">
							<?php
							print "<select name='gibbonPersonIDReturnAction' id='gibbonPersonIDReturnAction' style='width: 300px'>" ;
								print "<option value=''></option>" ;
								print "<optgroup label='--" . _('Students By Roll Group') . "--'>" ;
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
								print "<optgroup label='--" . _('All Users') . "--'>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonPersonIDReturnAction"]==$rowSelect["gibbonPersonID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
							print "</select>" ;
							?>
						</td>
					</tr>
					
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonLibraryItemID" id="gibbonLibraryItemID" value="<?php print $gibbonLibraryItemID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Return">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>