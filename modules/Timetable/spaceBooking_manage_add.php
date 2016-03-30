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


if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceBooking_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/spaceBooking_manage.php'>" . __($guid, 'Manage Facility Bookings') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Facility Booking') . "</div>" ;
		print "</div>" ;
	
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;	
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
	
		$step=NULL ;
		if (isset($_GET["step"])) {
			$step=$_GET["step"] ;
		}
		if ($step!=1 AND $step!=2) {
			$step=1 ;
		}
	
		//Step 1
		if ($step==1) {
			print "<h2>" ;
				print __($guid, "Step 1 - Choose Facility") ;
			print "</h2>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_add.php&step=2" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print __($guid, 'Facility') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="foreignKeyID" id="foreignKeyID" style="width: 302px">
								<option value='Please select...'><?php print __($guid, 'Please select...') ?></option>
								<optgroup label='--<?php print __($guid, "Facilities") ?>--'/>" ;
									<?php
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonSpace ORDER by name" ; 
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='gibbonSpaceID-" . $rowSelect["gibbonSpaceID"] . "'>" . $rowSelect["name"] . "</option>" ; 
									}
									?>
								</optgroup>
								<optgroup label='--<?php print __($guid, "Library") ?>--'/>" ;
									<?php
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonLibraryItem WHERE bookable='Y' ORDER by name" ; 
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='gibbonLibraryItemID-" . $rowSelect["gibbonLibraryItemID"] . "'>" . $rowSelect["name"] . "</option>" ; 
									}
									?>
								</optgroup>
							</select>
							<script type="text/javascript">
								var gibbonSpaceID=new LiveValidation('gibbonSpaceID');
								gibbonSpaceID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Date') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
						</td>
						<td class="right">
							<input name="date" id="date" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
								date.add(Validate.Presence);
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
							<b><?php print __($guid, 'Start Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Format: hh:mm (24hr)') ?><br/></i></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'End Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Format: hh:mm (24hr)') ?><br/></i></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeEnd=new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							</script>
						</td>
					</tr>
					<script type="text/javascript">
						/* Homework Control */
						$(document).ready(function(){
							$("#repeatDailyRow").css("display","none");
							$("#repeatWeeklyRow").css("display","none");
							repeatDaily.disable();
							repeatWeekly.disable();
							
							//Response to clicking on homework control
							$(".repeat").click(function(){
								if ($('input[name=repeat]:checked').val()=="Daily" ) {
									repeatDaily.enable();
									repeatWeekly.disable();
									$("#repeatDailyRow").slideDown("fast", $("#repeatDailyRow").css("display","table-row")); 
									$("#repeatWeeklyRow").css("display","none");
								} else if ($('input[name=repeat]:checked').val()=="Weekly" ) {
									repeatWeekly.enable();
									repeatDaily.disable();
									$("#repeatWeeklyRow").slideDown("fast", $("#repeatWeeklyRow").css("display","table-row")); 
									$("#repeatDailyRow").css("display","none");
								} else {
									repeatWeekly.disable();
									repeatDaily.disable();
									$("#repeatWeeklyRow").css("display","none");
									$("#repeatDailyRow").css("display","none");
								}
							 });
						});
					</script>
					
					<tr id="repeatRow">
						<td> 
							<b><?php print __($guid, 'Repeat?') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input checked type="radio" name="repeat" value="No" class="repeat" /> <?php print __($guid, 'No') ?>
							<input type="radio" name="repeat" value="Daily" class="repeat" /> <?php print __($guid, 'Daily') ?>
							<input type="radio" name="repeat" value="Weekly" class="repeat" /> <?php print __($guid, 'Weekly') ?>
						</td>
					</tr>
					<tr id="repeatDailyRow">
						<td> 
							<b><?php print __($guid, 'Repeat Daily') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Repeat daily for this many days.') . "<br/>" . __($guid, 'Does not include non-school days.') ?></i></span>
						</td>
						<td class="right">
							<input name="repeatDaily" id="repeatDaily" maxlength=2 value="2" type="text" style="width: 300px">
							<script type="text/javascript">
								var repeatDaily=new LiveValidation('repeatDaily');
							 	repeatDaily.add(Validate.Presence);
							 	repeatDaily.add( Validate.Numericality, { onlyInteger: true } );
							 	repeatDaily.add( Validate.Numericality, { minimum: 2, maximum: 20 } );
							</script>
						</td>
					</tr>
					<tr id="repeatWeeklyRow">
						<td> 
							<b><?php print __($guid, 'Repeat Weekly') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Repeat weekly for this many days.') . "<br/>" . __($guid, 'Does not include non-school days.') ?></i></span>
						</td>
						<td class="right">
							<input name="repeatWeekly" id="repeatWeekly" maxlength=2 value="2" type="text" style="width: 300px">
							<script type="text/javascript">
								var repeatWeekly=new LiveValidation('repeatWeekly');
							 	repeatWeekly.add(Validate.Presence);
							 	repeatWeekly.add( Validate.Numericality, { onlyInteger: true } );
							 	repeatWeekly.add( Validate.Numericality, { minimum: 2, maximum: 20 } );
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
		else if ($step==2) {
			print "<h2>" ;
				print __($guid, "Step 2 - Availability Check") ;
			print "</h2>" ;
			
			$foreignKey=NULL ;
			$foreignKeyID=NULL ;
			if (isset($_POST["foreignKeyID"])) {
				if (substr($_POST["foreignKeyID"], 0, 13)=="gibbonSpaceID") { //It's a facility
					$foreignKey="gibbonSpaceID" ;
					$foreignKeyID=substr($_POST["foreignKeyID"], 14) ;
				}
				else if (substr($_POST["foreignKeyID"], 0, 19)=="gibbonLibraryItemID") { //It's a library item
					$foreignKey="gibbonLibraryItemID" ;
					$foreignKeyID=substr($_POST["foreignKeyID"], 20) ;
				}
			}
			$date=dateConvert($guid, $_POST["date"]) ;
			$timeStart=$_POST["timeStart"] ;
			$timeEnd=$_POST["timeEnd"] ;
			$repeat=$_POST["repeat"] ;
			$repeatDaily=NULL ;
			$repeatWeekly=NULL ;
			if ($repeat=="Daily") {
				$repeatDaily=$_POST["repeatDaily"] ;
			}
			else if ($repeat=="Weekly") {
				$repeatWeekly=$_POST["repeatWeekly"] ;
			}
			
			
			//Check for required fields
			if ($foreignKey==NULL OR $foreignKeyID==NULL OR $foreignKey=="" OR $foreignKeyID=="" OR $date=="" OR $timeStart=="" OR $timeEnd=="" OR $repeat=="") {
				print "<div class='error'>" ;
					print __($guid, "Your request failed because your inputs were invalid.") ;
				print "</div>" ;
			}
			else {
				try {
					if ($foreignKey=="gibbonSpaceID") {
						$dataSelect=array("gibbonSpace"=>$foreignKeyID); 
						$sqlSelect="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpace" ;
					} 
					else if ($foreignKey=="gibbonLibraryItemID") {
						$dataSelect=array("gibbonLibraryItemID"=>$foreignKeyID); 
						$sqlSelect="SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
					}
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" ;
						print __($guid, "Your request failed due to a database error.") ;
					print "</div>" ;
				}
			
				if ($resultSelect->rowCount()!=1) {
					print "<div class='error'>" ;
						print __($guid, "Your request failed due to a database error.") ;
					print "</div>" ;
				}
				else {
					$rowSelect=$resultSelect->fetch() ;
					
					$available=FALSE ;
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_addProcess.php" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<?php
							if ($repeat=="No") {
								?>
								<tr>
									<td colspan=2>
										<?php
										$available=isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd) ;
										if ($available==TRUE) {
											?>
											<tr class='current'>
												<td> 
													<b><?php print dateConvertBack($guid, $date) ?></b><br/>
													<span style="font-size: 90%"><i><?php print __($guid, 'Available') ?></i></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php print $date ?>'>
												</td>
											</tr>
											<?php
										}
										else {
											?>
											<tr class='error'>
												<td> 
													<b><?php print dateConvertBack($guid, $date) ?></b><br/>
													<span style="font-size: 90%"><i><?php print __($guid, 'Not Available') ?></i></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php print $date ?>'>
												</td>
											</tr>
											<?php
										}
										?>
									</td>
								</tr>
								<?php
							}
							else if ($repeat=="Daily" AND $repeatDaily>=2 AND $repeatDaily<=20) { //CREATE DAILY REPEATS
								$continue=TRUE ;
								$failCount=0 ;
								$successCount=0 ;
								$count=0 ;
								while ($continue) {
									$dateTemp=date('Y-m-d', strtotime($date)+(86400*$count)) ;
									if (isSchoolOpen($guid,$dateTemp, $connection2)) {
										$available=TRUE ;
										$successCount++ ;
										$failCount=0 ;
										if ($successCount>=$repeatDaily) {
											$continue=FALSE ;
										}
										//Print days
										if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd)==TRUE) {
											?>
											<tr class='current'>
												<td> 
													<b><?php print dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php print $dateTemp ?>'>
												</td>
											</tr>
											<?php
										}
										else {
											?>
											<tr class='error'>
												<td> 
													<b><?php print dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span style="font-size: 90%"><i><?php print __($guid, 'Not Available') ?></i></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php print $dateTemp ?>'>
												</td>
											</tr>
											<?php
										}
									}
									else {
										$failCount++ ;
										if ($failCount>100) {
											$continue=FALSE ;
										}
									}
									$count++ ;
								}
							}
							else if ($repeat=="Weekly" AND $repeatWeekly>=2 AND $repeatWeekly<=20) {
								$continue=TRUE ;
								$failCount=0 ;
								$successCount=0 ;
								$count=0 ;
								while ($continue) {
									$dateTemp=date('Y-m-d', strtotime($date)+(86400*7*$count)) ;
									if (isSchoolOpen($guid,$dateTemp, $connection2)) {
										$available=TRUE ;
										$successCount++ ;
										$failCount=0 ;
										if ($successCount>=$repeatWeekly) {
											$continue=FALSE ;
										}
										//Print days
										if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd)==TRUE) {
											?>
											<tr class='current'>
												<td> 
													<b><?php print dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php print $dateTemp ?>'>
												</td>
											</tr>
											<?php
										}
										else {
											?>
											<tr class='error'>
												<td> 
													<b><?php print dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span style="font-size: 90%"><i><?php print __($guid, 'Not Available') ?></i></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php print $dateTemp ?>'>
												</td>
											</tr>
											<?php
										}
									}
									else {
										$failCount++ ;
										if ($failCount>100) {
											$continue=FALSE ;
										}
									}
									$count++ ;
								}
							}
							else {
								print "<div class='error'>" ;
									print __($guid, "Your request failed because your inputs were invalid.") ;
								print "</div>" ;
							}
							?>
						
							<tr>
								<td colspan=2 class="right">
									<?php
									if ($available==TRUE) {
										?>
										<input type="hidden" name="foreignKey" value="<?php print $foreignKey ; ?>">
										<input type="hidden" name="foreignKeyID" value="<?php print $foreignKeyID ; ?>">
										<input type="hidden" name="date" value="<?php print $date ; ?>">
										<input type="hidden" name="timeStart" value="<?php print $timeStart ; ?>">
										<input type="hidden" name="timeEnd" value="<?php print $timeEnd ; ?>">
										<input type="hidden" name="repeat" value="<?php print $repeat ; ?>">
										<input type="hidden" name="repeatDaily" value="<?php print $repeatDaily ; ?>">
										<input type="hidden" name="repeatWeekly" value="<?php print $repeatWeekly ; ?>">
										<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
										<?php
									}
									else {
										print "<div class='error'>" ;
											print __($guid, 'There are no sessions available, and so this form cannot be submitted.') ;
										print "</div>" ;
									}
									?>
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