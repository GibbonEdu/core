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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_renew.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonLibraryItemEventID=$_GET["gibbonLibraryItemEventID"] ;
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;
	if ($gibbonLibraryItemEventID=="" OR $gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending.php'>" . __($guid, 'Lending & Activity Log') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID'>" . __($guid, 'View Item') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Renew Item') . "</div>" ;
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
				else if ($updateReturn=="success0") {
					$updateReturnMessage=__($guid, "Your request was successful.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending_item.php&name=" . $_GET["name"] . "&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "'>" . __($guid, 'Back') . "</a>" ;
				print "</div>" ;
			}
			
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_renewProcess.php?gibbonLibraryItemEventID=$gibbonLibraryItemEventID&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'ID') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="id" id="id" value="<?php print $row["id"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php print $row["name"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Status') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="statusCurrent" id="statusCurrent" value="<?php print $row["status"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Responsible User') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
							try {
								$dataSelect=array("gibbonPersonID"=>$row["gibbonPersonIDStatusResponsible"]); 
								$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							if ($resultSelect->rowCount()==1) {
								$rowSelect=$resultSelect->fetch() ;
								print "<input readonly name='gibbonPersonIDStatusResponsiblename' id='gibbonPersonIDStatusResponsiblename' value='" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "' type='text' style='width: 300px'>" ;
								print "<input name='gibbonPersonIDStatusResponsible' id='gibbonPersonIDStatusResponsible' value='" . $row["gibbonPersonIDStatusResponsible"] . "' type='hidden' style='width: 300px'>" ;
							}
							?>
						</td>
					</tr>
					<tr>
						<?php
						$loanLength=getSettingByScope($connection2, "Library", "defaultLoanLength") ;
						if (is_numeric($loanLength)==FALSE OR $loanLength<0) {
							$loanLength=7 ;
						}
						?>
						<td> 
							<b><?php print __($guid, 'Expected Return Date') ?> *</b><br/>
							<span class="emphasis small"><?php print sprintf(__($guid, 'Default renew length is today plus %1$s day(s)'), $loanLength) ?>.</span>
						</td>
						<td class="right">
							<input name="returnExpected" id="returnExpected" maxlength=10 value="<?php print date($_SESSION[$guid]["i18n"]["dateFormatPHP"], time()+($loanLength*60*60*24)) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var returnExpected=new LiveValidation('returnExpected');
								returnExpected.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#returnExpected" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
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