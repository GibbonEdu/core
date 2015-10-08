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

$enableDescriptors=getSettingByScope($connection2, "Behaviour", "enableDescriptors") ;
$enableLevels=getSettingByScope($connection2, "Behaviour", "enableLevels") ;

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>" . _('Manage Behaviour Records') . "</a> > </div><div class='trailEnd'>" . _('Add Multiple') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	print "<div class='linkTop'>" ;
		$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
		if ($policyLink!="") {
			print "<a target='_blank' href='$policyLink'>" . _('View Behaviour Policy') . "</a>" ;
		}
		if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
			if ($policyLink!="") {
				print " | " ;
			}
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>" . _('Back to Search Results') . "</a>" ;
		}
	print "</div>" ;
	?>

	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_addMultiProcess.php?gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Students') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?> </span>
				</td>
				<td class="right">
					<select multiple name="gibbonPersonIDMulti[]" id="gibbonPersonIDMulti[]" style="width: 302px; height:150px">
						<optgroup label='--<?php print _('Students by Roll Group') ?>--'>
							<?php
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
							?>
						</optgroup>
						<optgroup label='--<?php print _('Students by Name') ?>--'>
							<?php
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Date') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?></i></span>
				</td>
				<td class="right">
					<input name="date" id="date" maxlength=10 value="<?php print date($_SESSION[$guid]["i18n"]["dateFormatPHP"]) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#date" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Type') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="type" id="type" style="width: 302px">
						<option value="Positive"><?php print _('Positive') ?></option>
						<option value="Negative"><?php print _('Negative') ?></option>
					</select>
				</td>
			</tr>
			<?php
			if ($enableDescriptors=="Y") {
				try {
					$sqlPositive="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='positiveDescriptors'" ;
					$resultPositive=$connection2->query($sqlPositive);   
					$sqlNegative="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
					$resultNegative=$connection2->query($sqlNegative);   
				}
				catch(PDOException $e) { }

				if ($resultPositive->rowCount()==1 AND $resultNegative->rowCount()==1) {
					$rowPositive=$resultPositive->fetch() ;
					$rowNegative=$resultNegative->fetch() ;
				
					$optionsPositive=$rowPositive["value"] ;
					$optionsNegative=$rowNegative["value"] ;
				
					if ($optionsPositive!="" AND $optionsNegative!="") {
						$optionsPositive=explode(",", $optionsPositive) ;
						$optionsNegative=explode(",", $optionsNegative) ;
						?>
						<tr>
							<td> 
								<b><?php print _('Descriptor') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="descriptor" id="descriptor" style="width: 302px">
									<option value="Please select..."><?php print _('Please select...') ?></option>
									<?php
									for ($i=0; $i<count($optionsPositive); $i++) {
									?>
										<option class='Positive' value="<?php print trim($optionsPositive[$i]) ?>"><?php print trim($optionsPositive[$i]) ?></option>
									<?php
									}
									?>
									<?php
									for ($i=0; $i<count($optionsNegative); $i++) {
									?>
										<option class='Negative' value="<?php print trim($optionsNegative[$i]) ?>"><?php print trim($optionsNegative[$i]) ?></option>
									<?php
									}
									?>
								</select>
								<script type="text/javascript">
									var descriptor=new LiveValidation('descriptor');
									descriptor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								</script>
								 <script type="text/javascript">
									$("#descriptor").chainedTo("#type");
								</script>
							</td>
						</tr>
						<?php
					}
				}
			}
			
			if ($enableLevels=="Y") {
				$optionsLevels=getSettingByScope($connection2, "Behaviour", "levels") ;
				if ($optionsLevels!="") {
					$optionsLevels=explode(",", $optionsLevels) ;
					?>
					<tr>
						<td> 
							<b><?php print _('Level') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="level" id="level" style="width: 302px">
								<option value="Please select..."><?php print _('Please select...') ?></option>
								<?php
								for ($i=0; $i<count($optionsLevels); $i++) {
								?>
									<option value="<?php print trim($optionsLevels[$i]) ?>"><?php print trim($optionsLevels[$i]) ?></option>
								<?php
								}
								?>
							</select>
							<script type="text/javascript">
								var level=new LiveValidation('level');
								level.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<?php
				}
			}
			?>
			<tr>
				<td colspan=2> 
					<b><?php print _('Incident') ?></b><br/>
					<textarea name="comment" id="comment" rows=8 style="width: 100%"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php print _('Follow Up') ?></b><br/>
					<textarea name="followup" id="followup" rows=8 style="width: 100%"></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _('Next') ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>