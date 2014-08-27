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


if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>" . _('Manage Activities') . "</a> > </div><div class='trailEnd'>" . _('Edit Activity') . "</div>" ;
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
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail0") {
			$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($deleteReturn=="fail1") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($deleteReturn=="fail3") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonActivityID=$_GET["gibbonActivityID"];
	if ($gibbonActivityID=="Y") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonActivityID"=>$gibbonActivityID); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php&search=" .$_GET["search"] . "'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_editProcess.php?gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] ?>">
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
							<input name="name" id="name" maxlength=40 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print _('Provider') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="provider" id="provider" style="width: 302px">
								<option <?php if ($row["provider"]=="School") {print "selected ";}?>value="School"><?php print $_SESSION[$guid]["organisationNameShort"] ?></option>
								<option <?php if ($row["provider"]=="External") {print "selected ";}?>value="External"><?php print _('External') ?></option>
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
					catch(PDOException $e) { }
					
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
											<option <?php if ($row["type"]==trim($options[$i])) {print "selected ";}?>value="<?php print trim($options[$i]) ?>"><?php print trim($options[$i]) ?></option>
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
								<option <?php if ($row["active"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["active"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print _('Registration') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Assuming system-wide registration is open, should this activity be open for registration?') ?></i></span>
						</td>
						<td class="right">
							<select name="registration" id="registration" style="width: 302px">
								<option <?php if ($row["registration"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["registration"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
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
										$checked="" ;
										if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
											$checked="checked " ;
										}
										print $terms[($i+1)] . " <input $checked type='checkbox' name='gibbonSchoolYearTermID[]' value='$terms[$i]'><br/>" ;
									}
								}
								?>
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td> 
								<b><?php print _('Listing Start Date') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Format:') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('Default: 2 weeks before the end of the current term.') ?></i></span>
							</td>
							<td class="right">
								<input name="listingStart" id="listingStart" maxlength=10 value="<?php print dateConvertBack($guid, $row["listingStart"]) ?>" type="text" style="width: 300px">
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
								<input name="listingEnd" id="listingEnd" maxlength=10 value="<?php print dateConvertBack($guid, $row["listingEnd"]) ?>" type="text" style="width: 300px">
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
								<input name="programStart" id="programStart" maxlength=10 value="<?php print dateConvertBack($guid, $row["programStart"]) ?>" type="text" style="width: 300px">
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
								<input name="programEnd" id="programEnd" maxlength=10 value="<?php print dateConvertBack($guid, $row["programEnd"]) ?>" type="text" style="width: 300px">
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
							$yearGroups=getYearGroups($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
							if ($yearGroups=="") {
								print "<i>" . _('No year groups available.') . "</i>" ;
							}
							else {
								for ($i=0; $i<count($yearGroups); $i=$i+2) {
									$checked="" ;
									if (is_numeric(strpos($row["gibbonYearGroupIDList"], $yearGroups[$i]))) {
										$checked="checked " ;
									}
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
							<input name="maxParticipants" id="maxParticipants" maxlength=4 value="<?php print $row["maxParticipants"] ?>" type="text" style="width: 300px">
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
									<input readonly name="paymentNote" id="paymentNote" maxlength=100 value="<?php print _('Per Activty payment is switched off') ?>" type="text" style="width: 300px">
									<?php
								}
								else {
									?>
									<input name="payment" id="payment" maxlength=7 value="<?php print $row["payment"] ?>" type="text" style="width: 300px">
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
							<?php print getEditor($guid,  TRUE, "description", $row["description"], 10, TRUE ) ?>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Current Time Slots') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?php
							try {
								$data=array("gibbonActivityID"=>$gibbonActivityID); 
								$sql="SELECT * FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY gibbonDaysOfWeek.gibbonDaysOfWeekID" ; 
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							if ($result->rowCount()<1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a time slot, any unsaved changes to this planner entry will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print _("Name") ;
										print "</th>" ;
										print "<th>" ;
											print _("Time") ;
										print "</th>" ;
										print "<th>" ;
											print _("Location") ;
										print "</th>" ;
										print "<th>" ;
											print _("Actions") ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$rowNum="odd" ;
									while ($row=$result->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
										$count++ ;
										
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print _($row["name"]) ;
											print "</td>" ;
											print "<td>" ;
												print substr($row["timeStart"],0,5) . " - " . substr($row["timeEnd"],0,5) ;
											print "</td>" ;
											print "<td>" ;
												if ($row["gibbonSpaceID"]!="") {
													try {
														$dataSpace=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
														$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
														$resultSpace=$connection2->prepare($sqlSpace);
														$resultSpace->execute($dataSpace);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													
													if ($resultSpace->rowCount()==1) {
														$rowSpace=$resultSpace->fetch() ;
														print $rowSpace["name"] ;
													}
												}
												else {
													print $row["locationExternal"] ;
												}
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit_slot_deleteProcess.php?address=" . $_GET["q"] . "&gibbonActivitySlotID=" . $row["gibbonActivitySlotID"] . "&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
											print "</td>" ;
										print "</tr>" ;
									}
								print "</table>" ;
							}
							?>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('New Time Slots') ?></h3>
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
								<h4><?php print sprintf(_('Slot %1$s'), $i) ?></h4>
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
							<h3><?php print _('Current Staff') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?php
							try {
								$data=array("gibbonActivityID"=>$gibbonActivityID); 
								$sql="SELECT preferredName, surname, gibbonActivityStaff.* FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($result->rowCount()<1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a guest, any unsaved changes to this planner entry will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print _("Name") ;
										print "</th>" ;
										print "<th>" ;
											print _("Role") ;
										print "</th>" ;
										print "<th>" ;
											print _("Actions") ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$rowNum="odd" ;
									while ($row=$result->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
										$count++ ;
										
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print formatName("", $row["preferredName"], $row["surname"], "Staff", true, true) ;
											print "</td>" ;
											print "<td>" ;
												print $row["role"] ;
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonActivityStaffID=" . $row["gibbonActivityStaffID"] . "&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
											print "</td>" ;
										print "</tr>" ;
									}
								print "</table>" ;
							}
							?>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('New Staff') ?></h3>
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
?>