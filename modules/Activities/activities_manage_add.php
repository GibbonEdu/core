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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>" . _('Manage Activities') . "</a> > </div><div class='trailEnd'>" . _('Add Activity') . "</div>" ;
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
			$addReturnMessage="Your request failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage="Your request was successful, but some data was not properly saved." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	if ($_GET["search"]!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php&search=" .$_GET["search"] . "'>" . _('Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_addProcess.php?search=" . $_GET["search"] ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Basic Information') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=40 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Provider') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="provider" id="provider" style="width: 302px">
						<option value="School"><?php print $_SESSION[$guid]["organisationNameShort"] ?></option>
						<option value="External"><?php print _('External') ?></option>
					</select>
				</td>
			</tr>
			
			<?php
			try {
				$dataType=array(); 
				$sqlType="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='activityTypes'" ;
				$resultType=$connection2->prepare($sqlType);
				$resultType->execute($dataType);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultType->rowCount()==1) {
				$rowType=$resultType->fetch() ;
				
				$options=$rowType["value"] ;
				if ($options!="") {
					$options=explode(",", $options) ;
					?>
					<tr>
						<td> 
							<b><?php print _('Type') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value=""></option>
								<?php
								for ($i=0; $i<count($options); $i++) {
								?>
									<option value="<?php print trim($options[$i]) ?>"><?php print trim($options[$i]) ?></option>
								<?php
								}
								?>
							</select>
						</td>
					</tr>
					<?php
				}
			}
			?>
			
			<tr>
				<td> 
					<b><?php print _('Active') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="active" id="active" style="width: 302px">
						<option value="Y"><?php print _('Yes') ?></option>
						<option value="N"><?php print _('No') ?></option>
					</select>
				</td>
			</tr>
			
			<?php
			//Should we show date as term or date?
			$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 
			print "<input type='hidden' name='dateType' value='$dateType'>" ;				
			if ($dateType!="Date") {
				?>
				<tr>
					<td> 
						<b><?php print _('Terms') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Terms in which the activity will run.') ?><br/></i></span>
					</td>
					<td class="right">
						<?php 
						$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
						if ($terms=="") {
							print "<i>" . _('No terms available.') . "</i>" ;
						}
						else {
							for ($i=0; $i<count($terms); $i=$i+2) {
								$checked="checked " ;
								print $terms[($i+1)] . " <input $checked type='checkbox' name='gibbonSchoolYearTermID[]' value='$terms[$i]'><br/>" ;
							}
						}
						?>
					</td>
				</tr>
				<?php
			}
			else {
				$today=date("Y-m-d") ;
				try {
					$dataTerm=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "firstDay"=>$today, "lastDay"=>$today); 
					$sqlTerm="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND firstDay<=:firstDay AND lastDay>=:lastDay ORDER BY sequenceNumber" ;
					$resultTerm=$connection2->prepare($sqlTerm);
					$resultTerm->execute($dataTerm);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				//We are currently in term
				if ($resultTerm->rowCount()>0) {
					$rowTerm=$resultTerm->fetch() ;
					$listingStart=date("Y-m-d", (dateConvertToTimestamp($rowTerm["lastDay"])-1209600)) ;
				
					try {
						$dataTerm2=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "sequenceNumber"=>$rowTerm["sequenceNumber"]); 
						$sqlTerm2="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND sequenceNumber>:sequenceNumber ORDER BY sequenceNumber" ;
						$resultTerm2=$connection2->prepare($sqlTerm2);
						$resultTerm2->execute($dataTerm2);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					//There is another term coming up
					if ($resultTerm2->rowCount()>0) {
						$rowTerm2=$resultTerm2->fetch() ;
						$listingEnd=date("Y-m-d", (dateConvertToTimestamp($rowTerm2["firstDay"])+1209600)) ;
						$programStart=$rowTerm2["firstDay"] ;
						$programEnd=$rowTerm2["lastDay"] ;
					}
				}
				?>
				
				<tr>
					<td> 
						<b><?php print _('Listing Start Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('Default: 2 weeks before the end of the current term.') ?></i></span>
					</td>
					<td class="right">
						<input name="listingStart" id="listingStart" maxlength=10 value="<?php if ($listingStart!="") { print dateConvertBack($guid, $listingStart) ; } ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var listingStart=new LiveValidation('listingStart');
							listingStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						 </script>
						 <script type="text/javascript">
							$(function() {
								$( "#listingStart" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Listing End Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('Default: 2 weeks after the start of next term.') ?></i></span>
					</td>
					<td class="right">
						<input name="listingEnd" id="listingEnd" maxlength=10 value="<?php if ($listingEnd!="") { print dateConvertBack($guid, $listingEnd) ; } ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var listingEnd=new LiveValidation('listingEnd');
							listingEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						 </script>
						 <script type="text/javascript">
							$(function() {
								$( "#listingEnd" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Program Start Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('Default: first day of next term.') ?></i></span>
					</td>
					<td class="right">
						<input name="programStart" id="programStart" maxlength=10 value="<?php if ($programStart!="") { print dateConvertBack($guid, $programStart) ; } ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var programStart=new LiveValidation('programStart');
							programStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						 </script>
						 <script type="text/javascript">
							$(function() {
								$( "#programStart" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Program End Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('Default: last day of the next term.') ?></i></span>
					</td>
					<td class="right">
						<input name="programEnd" id="programEnd" maxlength=10 value="<?php if ($programEnd!="") { print dateConvertBack($guid, $programEnd) ; } ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var programEnd=new LiveValidation('programEnd');
							programEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						 </script>
						 <script type="text/javascript">
							$(function() {
								$( "#programEnd" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<?php
			}
			?>
			
			
			<tr>
				<td> 
					<b><?php print _('Year Groups') ?></b><br/>
				</td>
				<td class="right">
					<?php 
					$yearGroups=getYearGroups($connection2) ;
					if ($yearGroups=="") {
						print "<i>" . _('No year groups available.') . "</i>" ;
					}
					else {
						for ($i=0; $i<count($yearGroups); $i=$i+2) {
							$checked="checked " ;
							print _($yearGroups[($i+1)]) . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
							print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
						}
					}
					?>
					<input type="hidden" name="count" value="<?php print (count($yearGroups))/2 ?>">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Max Participants') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="maxParticipants" id="maxParticipants" maxlength=4 value="0" type="text" style="width: 300px">
					<script type="text/javascript">
						var maxParticipants=new LiveValidation('maxParticipants');
						maxParticipants.add(Validate.Presence);
						maxParticipants.add(Validate.Numericality);
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Cost') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('For entire programme') . ". " . $_SESSION[$guid]["currency"] . "." ?><br/></i></span>
				</td>
				<td class="right">
					<?php
						if (getSettingByScope($connection2, "Activities", "payment")=="None" OR getSettingByScope($connection2, "Activities", "payment")=="Single") {
						 	?>
						 	<input readonly name="paymentNote" id="paymentNote" maxlength=100 value="Per Activty payment is switched off" type="text" style="width: 300px">
							<?php
						}
						else {
							?>
							<input name="payment" id="payment" maxlength=7 value="0.00" type="text" style="width: 300px">
							<script type="text/javascript">
								var payment=new LiveValidation('payment');
								payment.add(Validate.Presence);
								payment.add(Validate.Numericality);
							 </script>
							 <?php
						}
					?>
					
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php print _('Description') ?></b> 
					<?php print getEditor($guid,  TRUE, "description", "", 10, TRUE ) ?>
				</td>
			</tr>
			
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Time Slots') ?></h3>
				</td>
			</tr>
			
			<script type="text/javascript">
				/* Resource 1 Option Control */
				$(document).ready(function(){
					$("#slot1InternalRow").css("display","none");
					$("#slot1ExternalRow").css("display","none");
					$("#slot1ButtonRow").css("display","none");
					
					$(".slot1Location").click(function(){
						if ($('input[name=slot1Location]:checked').val()=="External" ) {
							$("#slot1InternalRow").css("display","none");
							$("#slot1ExternalRow").slideDown("fast", $("#slot1ExternalRow").css("display","table-row")); 
							$("#slot1ButtonRow").slideDown("fast", $("#slot1ButtonRow").css("display","table-row")); 
						} else {
							$("#slot1ExternalRow").css("display","none");
							$("#slot1InternalRow").slideDown("fast", $("#slot1InternalRow").css("display","table-row")); 
							$("#slot1ButtonRow").slideDown("fast", $("#slot1ButtonRow").css("display","table-row")); 
						}
					 });
				});
				
				/* Resource 2 Display Control */
				$(document).ready(function(){
					$("#slot2Row").css("display","none");
					$("#slot2DayRow").css("display","none");
					$("#slot2StartRow").css("display","none");
					$("#slot2EndRow").css("display","none");
					$("#slot2LocationRow").css("display","none");
					$("#slot2InternalRow").css("display","none");
					$("#slot2ExternalRow").css("display","none");
					$("#slot2ButtonRow").css("display","none");
					
					$("#slot1Button").click(function(){
						$("#slot2Button").css("display","none");
						$("#slot2Row").slideDown("fast", $("#slot2Row").css("display","table-row")); 
						$("#slot2DayRow").slideDown("fast", $("#slot2DayRow").css("display","table-row")); 
						$("#slot2StartRow").slideDown("fast", $("#slot2StartRow").css("display","table-row")); 
						$("#slot2EndRow").slideDown("fast", $("#slot2EndRow").css("display","table-row")); 
						$("#slot2LocationRow").slideDown("fast", $("#slot2LocationRow").css("display","table-row")); 
					});
				});
				
				/* Resource 2 Option Control */
				$(document).ready(function(){
					$(".slot2Location").click(function(){
						if ($('input[name=slot2Location]:checked').val()=="External" ) {
							$("#slot2InternalRow").css("display","none");
							$("#slot2ExternalRow").slideDown("fast", $("#slot2ExternalRow").css("display","table-row")); 
						} else {
							$("#slot2ExternalRow").css("display","none");
							$("#slot2InternalRow").slideDown("fast", $("#slot2InternalRow").css("display","table-row")); 
						}
					 });
				});
			</script>
				
			<?php
			for ($i=1; $i<3; $i++) {
				?>
				<tr id="slot<?php print $i ?>Row">
					<td colspan=2> 
						<h4><?php print _('Slot') ?> <?php print $i ?></h4>
					</td>
				</tr>
				<tr id="slot<?php print $i ?>DayRow">
					<td> 
						<b><?php print sprintf(_('Slot %1$s Day'), $i) ?></b><br/>
					</td>
					<td class="right">
						<select name="gibbonDaysOfWeekID<?php print $i ?>" id="gibbonDaysOfWeekID<?php print $i ?>" style="width: 302px">
							<option value=""></option>
							<?php
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonDaysOfWeek ORDER BY sequenceNumber" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonDaysOfWeekID"] . "'>" . _($rowSelect["name"]) . "</option>" ; 
							}
							?>
						</select>
					</td>
				</tr>
				<tr id="slot<?php print $i ?>StartRow">
					<td> 
						<b><?php print sprintf(_('Slot %1$s Start Time'), $i) ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Format: hh:mm') ?></i></span>
					</td>
					<td class="right">
						<input name="timeStart<?php print $i ?>" id="timeStart<?php print $i ?>" maxlength=5 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT timeStart FROM gibbonActivitySlot ORDER BY timeStart" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . substr($rowAuto["timeStart"],0,5) . "\", " ;
									}
									?>
								];
								$( "#timeStart<?php print $i ?>" ).autocomplete({source: availableTags});
							});
						</script>
					</td>
				</tr>
				<tr id="slot<?php print $i ?>EndRow">
					<td> 
						<b><?php print sprintf(_('Slot %1$s End Time'), $i) ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Format: hh:mm') ?></i></span>
					</td>
					<td class="right">
						<input name="timeEnd<?php print $i ?>" id="timeEnd<?php print $i ?>" maxlength=5 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							$(function() {
								var availableTags=[
									<?php
									try {
										$dataAuto=array(); 
										$sqlAuto="SELECT DISTINCT timeEnd FROM gibbonActivitySlot ORDER BY timeEnd" ;
										$resultAuto=$connection2->prepare($sqlAuto);
										$resultAuto->execute($dataAuto);
									}
									catch(PDOException $e) { }
									while ($rowAuto=$resultAuto->fetch()) {
										print "\"" . substr($rowAuto["timeEnd"],0,5) . "\", " ;
									}
									?>
								];
								$( "#timeEnd<?php print $i ?>" ).autocomplete({source: availableTags});
							});
						</script>
					</td>
				</tr>
				<tr id="slot<?php print $i ?>LocationRow">
					<td> 
						<b><?php print sprintf(_('Slot %1$s Location'), $i) ?></b><br/>
					</td>
					<td class="right">
						<input type="radio" name="slot<?php print $i ?>Location" value="Internal" class="slot<?php print $i ?>Location" /> Internal
						<input type="radio" name="slot<?php print $i ?>Location" value="External" class="slot<?php print $i ?>Location" /> External
					</td>
				</tr>
				<tr id="slot<?php print $i ?>InternalRow">
					<td> 
						
					</td>
					<td class="right">
						<select name="gibbonSpaceID<?php print $i ?>" id="gibbonSpaceID<?php print $i ?>" style="width: 302px">
							<option value=""></option>
							<?php
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonSpaceID"] . "'>" . $rowSelect["name"] . "</option>" ; 
							}
							?>
						</select>
					</td>
				</tr>
				<tr id="slot<?php print $i ?>ExternalRow">
					<td> 
						
					</td>
					<td class="right">
						<input name="location<?php print $i ?>External" id="location<?php print $i ?>External" maxlength=50 value="" type="text" style="width: 300px">
					</td>
				</tr>
				<tr id="slot<?php print $i ?>ButtonRow">
					<td> 
					</td>
					<td class="right">
						<input class="buttonAsLink" id="slot<?php print $i ?>Button" type="button" value="Add Another Slot">
						<a href=""></a>
					</td>
				</tr>
				<?php
			}
			?>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print _('Staff') ?></h3>
				</td>
			</tr>
			<tr>
			<td> 
				<b><?php print _('Staff') ?></b><br/>
				<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
			</td>
			<td class="right">
				<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
					<?php
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }	
					while ($rowSelect=$resultSelect->fetch()) {
						print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
					}
					?>
				</select>
			</td>
			<tr>
				<td> 
					<b><?php print _('Role') ?></b><br/>
				</td>
				<td class="right">
					<select name="role" id="role" style="width: 302px">
						<option value="Organiser"><?php print _('Organiser') ?></option>
						<option value="Coach"><?php print _('Coach') ?></option>
						<option value="Assistant"><?php print _('Assistant') ?></option>
						<option value="Other"><?php print _('Other') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input name="viewBy" id="viewBy" value="<?php print $viewBy ?>" type="hidden">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>