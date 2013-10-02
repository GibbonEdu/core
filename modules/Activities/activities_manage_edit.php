<?
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

session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>Manage Activities</a> > </div><div class='trailEnd'>Edit Activity</div>" ;
	print "</div>" ;
	
	$updateReturn = $_GET["updateReturn"] ;
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage ="Update failed because your attachment could not be uploaded." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	$deleteReturn = $_GET["deleteReturn"] ;
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail0") {
			$deleteReturnMessage ="Delete failed because you do not have access to this action." ;	
		}
		else if ($deleteReturn=="fail1") {
			$deleteReturnMessage ="Delete failed because a required parameter was not set." ;	
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage ="Delete failed due to a database error." ;	
		}
		else if ($deleteReturn=="fail3") {
			$deleteReturnMessage ="Delete failed because your inputs were invalid." ;	
		}
		else if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Delete was successful." ;	
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
			print "You have not specified an activity." ;
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
				print "The selected activity does not exist, is in a previous school year, or you do not have access to it." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php&search=" .$_GET["search"] . "'>Back to Search Results</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_editProcess.php?gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>Basic Information</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name = new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					
					<?
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
									<b>Type</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="type" id="type" style="width: 302px">
										<option value=""></option>
										<?
										for ($i=0; $i<count($options); $i++) {
										?>
											<option <? if ($row["type"]==trim($options[$i])) {print "selected ";}?>value="<? print trim($options[$i]) ?>"><? print trim($options[$i]) ?></option>
										<?
										}
										?>
									</select>
								</td>
							</tr>
							<?
						}
					}
					?>
					
					<tr>
						<td> 
							<b>Active *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="active" id="active" style="width: 302px">
								<option <? if ($row["active"]=="Y") {print "selected ";}?>value="Y">Y</option>
								<option <? if ($row["active"]=="N") {print "selected ";}?>value="N">N</option>
							</select>
						</td>
					</tr>
					
					<?
					//Should we show date as term or date?
					$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 

					print "<input type='hidden' name='dateType' value='$dateType'>" ;
						
					if ($dateType!="Date") {
						?>
						<tr>
							<td> 
								<b>Terms</b><br/>
								<span style="font-size: 90%"><i>Terms in which the activity will run.<br/></i></span>
							</td>
							<td class="right">
								<? 
								$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
								if ($terms=="") {
									print "<i>No terms available.</i>" ;
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
						<?
					}
					else {
						?>
						<tr>
							<td> 
								<b>Listing Start Date *</b><br/>
								<span style="font-size: 90%"><i>Format: dd/mm/yyyy<br/>Default: 2 weeks before the end of the current term.</i></span>
							</td>
							<td class="right">
								<input name="listingStart" id="listingStart" maxlength=10 value="<? print dateConvertBack($row["listingStart"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var listingStart = new LiveValidation('listingStart');
									listingStart.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
								<b>Listing End Date *</b><br/>
								<span style="font-size: 90%"><i>Format: dd/mm/yyyy<br/>Default: 2 weeks after the start of next term.</i></span>
							</td>
							<td class="right">
								<input name="listingEnd" id="listingEnd" maxlength=10 value="<? print dateConvertBack($row["listingEnd"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var listingEnd = new LiveValidation('listingEnd');
									listingEnd.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
								<b>Program Start Date *</b><br/>
								<span style="font-size: 90%"><i>Format: dd/mm/yyyy<br/>Default: first day of next term.</i></span>
							</td>
							<td class="right">
								<input name="programStart" id="programStart" maxlength=10 value="<? print dateConvertBack($row["programStart"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var programStart = new LiveValidation('programStart');
									programStart.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
								<b>Program End Date *</b><br/>
								<span style="font-size: 90%"><i>Format: dd/mm/yyyy<br/>Default: last day of the next term.</i></span>
							</td>
							<td class="right">
								<input name="programEnd" id="programEnd" maxlength=10 value="<? print dateConvertBack($row["programEnd"]) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var programEnd = new LiveValidation('programEnd');
									programEnd.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
								 </script>
								 <script type="text/javascript">
									$(function() {
										$( "#programEnd" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<?
					}
					
					?>
					
					
					<tr>
						<td> 
							<b>Year Groups</b><br/>
							<span style="font-size: 90%"><i>Students year groups which may participate<br/></i></span>
						</td>
						<td class="right">
							<? 
							$yearGroups=getYearGroups($connection2, $_GET["gibbonSchoolYearID"]) ;
							if ($yearGroups=="") {
								print "<i>No year groups available.</i>" ;
							}
							else {
								for ($i=0; $i<count($yearGroups); $i=$i+2) {
									$checked="" ;
									if (is_numeric(strpos($row["gibbonYearGroupIDList"], $yearGroups[$i]))) {
										$checked="checked " ;
									}
									print $yearGroups[($i+1)] . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
									print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
								}
							}
							?>
							<input type="hidden" name="count" value="<? print (count($yearGroups))/2 ?>">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Max Participants *</b><br/>
						</td>
						<td class="right">
							<input name="maxParticipants" id="maxParticipants" maxlength=4 value="<? print $row["maxParticipants"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var maxParticipants = new LiveValidation('maxParticipants');
								maxParticipants.add(Validate.Presence);
								maxParticipants.add(Validate.Numericality);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Cost *</b><br/>
							<span style="font-size: 90%"><i>For entire programme<br/></i></span>
						</td>
						<td class="right">
							<?
								if (getSettingByScope($connection2, "Activities", "payment")=="None" OR getSettingByScope($connection2, "Activities", "payment")=="Single") {
									?>
									<input readonly name="paymentNote" id="paymentNote" maxlength=100 value="Per Activty payment is switched off" type="text" style="width: 300px">
									<?
								}
								else {
									?>
									<input name="payment" id="payment" maxlength=7 value="<? print $row["payment"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var payment = new LiveValidation('payment');
										payment.add(Validate.Presence);
										payment.add(Validate.Numericality);
									 </script>
									 <?
								}
							?>
							
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b>Description</b> 
							<? print getEditor($guid,  TRUE, "description", $row["description"], 10, TRUE ) ?>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Current Time Slots</h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?
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
								print "There are no activities to display." ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a time slot, any unsaved changes to this planner entry will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Name" ;
										print "</th>" ;
										print "<th>" ;
											print "Time" ;
										print "</th>" ;
										print "<th>" ;
											print "Location</span>" ;
										print "</th>" ;
										print "<th>" ;
											print "Action" ;
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
												print $row["name"] ;
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
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit_slot_deleteProcess.php?address=" . $_GET["q"] . "&gibbonActivitySlotID=" . $row["gibbonActivitySlotID"] . "&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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
							<h3>New Time Slots</h3>
						</td>
					</tr>
					
					<script type="text/javascript">
						/* Resource 1 Option Control */
						$(document).ready(function(){
							$("#slot1InternalRow").css("display","none");
							$("#slot1ExternalRow").css("display","none");
							$("#slot1ButtonRow").css("display","none");
							
							$(".slot1Location").click(function(){
								if ($('input[name=slot1Location]:checked').val() == "External" ) {
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
								if ($('input[name=slot2Location]:checked').val() == "External" ) {
									$("#slot2InternalRow").css("display","none");
									$("#slot2ExternalRow").slideDown("fast", $("#slot2ExternalRow").css("display","table-row")); 
								} else {
									$("#slot2ExternalRow").css("display","none");
									$("#slot2InternalRow").slideDown("fast", $("#slot2InternalRow").css("display","table-row")); 
								}
							 });
						});
					</script>
						
					<?
					for ($i=1; $i<3; $i++) {
						?>
						<tr id="slot<? print $i ?>Row">
							<td colspan=2> 
								<h4>Slot <? print $i ?></h4>
							</td>
						</tr>
						<tr id="slot<? print $i ?>DayRow">
							<td> 
								<b>Slot <? print $i ?> Day</b><br/>
							</td>
							<td class="right">
								<select name="gibbonDaysOfWeekID<? print $i ?>" id="gibbonDaysOfWeekID<? print $i ?>" style="width: 302px">
									<option value=""></option>
									<?
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonDaysOfWeek ORDER BY sequenceNumber" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='" . $rowSelect["gibbonDaysOfWeekID"] . "'>" . $rowSelect["name"] . "</option>" ; 
									}
									?>
								</select>
							</td>
						</tr>
						<tr id="slot<? print $i ?>StartRow">
							<td> 
								<b>Slot <? print $i ?> Start Time</b><br/>
								<span style="font-size: 90%"><i>Format: hh:mm</i></span>
							</td>
							<td class="right">
								<input name="timeStart<? print $i ?>" id="timeStart<? print $i ?>" maxlength=5 value="<? print substr($row["timeStart"],0,5) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									$(function() {
										var availableTags = [
											<?
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
										$( "#timeStart<? print $i ?>" ).autocomplete({source: availableTags});
									});
								</script>
							</td>
						</tr>
						<tr id="slot<? print $i ?>EndRow">
							<td> 
								<b>Slot <? print $i ?> End Time</b><br/>
								<span style="font-size: 90%"><i>Format: hh:mm</i></span>
							</td>
							<td class="right">
								<input name="timeEnd<? print $i ?>" id="timeEnd<? print $i ?>" maxlength=5 value="<? print substr($row["timeEnd"],0,5) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									$(function() {
										var availableTags = [
											<?
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
										$( "#timeEnd<? print $i ?>" ).autocomplete({source: availableTags});
									});
								</script>
							</td>
						</tr>
						<tr id="slot<? print $i ?>LocationRow">
							<td> 
								<b>Slot <? print $i ?> Location</b><br/>
							</td>
							<td class="right">
								<input type="radio" name="slot<? print $i ?>Location" value="Internal" class="slot<? print $i ?>Location" /> Internal
								<input type="radio" name="slot<? print $i ?>Location" value="External" class="slot<? print $i ?>Location" /> External
							</td>
						</tr>
						<tr id="slot<? print $i ?>InternalRow">
							<td> 
								
							</td>
							<td class="right">
								<select name="gibbonSpaceID<? print $i ?>" id="gibbonSpaceID<? print $i ?>" style="width: 302px">
									<option value=""></option>
									<?
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
						<tr id="slot<? print $i ?>ExternalRow">
							<td> 
								
							</td>
							<td class="right">
								<input name="location<? print $i ?>External" id="location<? print $i ?>External" maxlength=50 value="" type="text" style="width: 300px">
							</td>
						</tr>
						<tr id="slot<? print $i ?>ButtonRow">
							<td> 
							</td>
							<td class="right">
								<input class="buttonAsLink" id="slot<? print $i ?>Button" type="button" value="Add Another Slot">
								<a href=""></a>
							</td>
						</tr>
						<?
					}
					?>
							
					<tr class='break'>
						<td colspan=2> 
							<h3>Current Staff</h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?
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
								print "There are no guests to display." ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a guest, any unsaved changes to this planner entry will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Name" ;
										print "</th>" ;
										print "<th>" ;
											print "Role" ;
										print "</th>" ;
										print "<th>" ;
											print "Action" ;
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
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonActivityStaffID=" . $row["gibbonActivityStaffID"] . "&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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
							<h3>New Staff</h3>
						</td>
					</tr>
					<tr>
					<td> 
						<b>Staff</b><br/>
						<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
					</td>
					<td class="right">
						<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
							<?
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["surname"]), htmlPrep($rowSelect["preferredName"]), "Staff", true, true) . "</option>" ;
							}
							?>
						</select>
					</td>
					<tr>
						<td> 
							<b>Role</b><br/>
						</td>
						<td class="right">
							<select name="role" id="role" style="width: 302px">
								<option value="Organiser">Organiser</option>
								<option value="Coach">Coach</option>
								<option value="Assistant">Assistant</option>
								<option value="Other">Other</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="reset" value="Reset"> <input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>