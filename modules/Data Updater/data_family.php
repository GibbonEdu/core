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

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_family.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Update Family Data') . "</div>" ;
		print "</div>" ;
	
		if ($highestAction=="Update Personal Data_any") {
			print "<p>" ;
			print _("This page allows a user to request selected family data updates for any family.") ;
			print "</p>" ;
		}
		else {
			print "<p>" ;
			print _("This page allows any adult with data access permission to request selected family data updates for their family.") ;
			print "</p>" ;
		}
		
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
			else if ($updateReturn=="fail5") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(_('Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(_('Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		print "<h2>" ;
		print _("Choose Family") ;
		print "</h2>" ;
		
		$gibbonFamilyID=NULL ;
		if (isset($_GET["gibbonFamilyID"])) {
			$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
		}
		?>
		
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td> 
						<b><?php print _('Family') ?> *</b><br/>
					</td>
					<td class="right">
						<select style="width: 302px" name="gibbonFamilyID">
							<?php
							if ($highestAction=="Update Family Data_any") {
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonFamilyID==$rowSelect["gibbonFamilyID"]) {
										print "<option selected value='" . $rowSelect["gibbonFamilyID"] . "'>" . $rowSelect["name"] . "</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonFamilyID"] . "'>" . $rowSelect["name"] . "</option>" ;
									}
								}
							}
							else {
								try {
									$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlSelect="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
										if ($gibbonFamilyID==$rowSelect["gibbonFamilyID"]) {
											print "<option selected value='" . $rowSelect["gibbonFamilyID"] . "'>" . $rowSelect["name"] . "</option>" ;
										}
										else {
											print "<option value='" . $rowSelect["gibbonFamilyID"] . "'>" . $rowSelect["name"] . "</option>" ;
										}
									}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/data_family.php">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		if ($gibbonFamilyID!="") {
			print "<h2>" ;
			print _("Update Data") ;
			print "</h2>" ;
			
			//Check access to person
			if ($highestAction=="Update Family Data_any") {
				try {
					$dataCheck=array("gibbonFamilyID"=>$gibbonFamilyID); 
					$sqlCheck="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
			}
			else {
				try {
					$dataCheck=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCheck="SELECT name, gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { print $e->getMessage() ;}
			}
			
			if ($resultCheck->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Check if there is already a pending form for this user
				$existing=FALSE ;
				$proceed=FALSE;
				try {
					$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonFamilyUpdate WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()>1) {
					print "<div class='error'>" ;
						print _("Your request failed due to a database error.") ;
					print "</div>" ;
				}
				else if ($result->rowCount()==1) {
					$existing=TRUE ;
					$proceed=FALSE;
					if ($addReturn=="") {
						print "<div class='warning'>" ;
							print _("You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.") ;
						print "</div>" ;
					}
					if ($highestAction!="Update Personal Data_any") {
						$required=unserialize(getSettingByScope( $connection2, "User Admin", "personalDataUpdaterRequiredFields")) ;
						if (is_array($required)) {
							$proceed=TRUE;
						}
					}
					else {
						$proceed=TRUE;
					}
				}
				else {
					//Get user's data
					try {
						$data=array("gibbonFamilyID"=>$gibbonFamilyID); 
						$sql="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
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
						$proceed=TRUE;
					}
				}
			
				if ($proceed==TRUE) {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_familyProcess.php?gibbonFamilyID=" . $gibbonFamilyID ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									<b><?php print _('Address Name') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Formal name to address parents with.') ?></i></span>
								</td>
								<td class="right">
									<input name="nameAddress" id="nameAddress" maxlength=100 value="<?php print htmlPrep($row["nameAddress"]) ?>" type="text" style="width: 300px">								
									<script type="text/javascript">
										var nameAddress=new LiveValidation('nameAddress');
										nameAddress.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Home Address') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Unit, Building, Street') ?></i></span>
								</td>
								<td class="right">
									<input name="homeAddress" id="homeAddress" maxlength=255 value="<?php print $row["homeAddress"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var homeAddress=new LiveValidation('homeAddress');
										homeAddress.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Home Address (District)') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('County, State, District') ?></i></span>
								</td>
								<td class="right">
									<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="<?php print $row["homeAddressDistrict"] ?>" type="text" style="width: 300px">
								</td>
								<script type="text/javascript">
									$(function() {
										var availableTags=[
											<?php
											try {
												$dataAuto=array(); 
												$sqlAuto="SELECT DISTINCT name FROM gibbonDistrict ORDER BY name" ;
												$resultAuto=$connection2->prepare($sqlAuto);
												$resultAuto->execute($dataAuto);
											}
											catch(PDOException $e) { }
											while ($rowAuto=$resultAuto->fetch()) {
												print "\"" . $rowAuto["name"] . "\", " ;
											}
											?>
										];
										$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
									});
								</script>
								<script type="text/javascript">
										var homeAddressDistrict=new LiveValidation('homeAddressDistrict');
										homeAddressDistrict.add(Validate.Presence);
									</script>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Home Address (Country)') ?></b><br/>
								</td>
								<td class="right">
									<select name="homeAddressCountry" id="homeAddressCountry" style="width: 302px">
										<?php
										print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["printable_name"]==$row["homeAddressCountry"]) {
												$selected=" selected" ;
											}
											print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep(_($rowSelect["printable_name"])) . "</option>" ;
										}
										?>				
									</select>
									<script type="text/javascript">
										var homeAddressCountry=new LiveValidation('homeAddressCountry');
										homeAddressCountry.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
									 </script>
								</td>
							</tr>
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<?php
									if ($existing) {
										print "<input type='hidden' name='existing' value='" . $row["gibbonFamilyUpdateID"] . "'>" ;
									}
									else {
										print "<input type='hidden' name='existing' value='N'>" ;
									}
									?>
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<?php print _("Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}	
			}
		}
	}
}
?>