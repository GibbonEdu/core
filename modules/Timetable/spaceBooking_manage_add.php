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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/spaceBooking_manage.php'>" . _('Manage Space Bookings') . "</a> > </div><div class='trailEnd'>" . _('Add Space Booking') . "</div>" ;
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
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
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
				print _("Step 1 - Choose Space") ;
			print "</h2>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_add.php&step=2" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print _('Space') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" style="width: 302px">
								<option value='Please select...'><?php print _('Please select...') ?></option>
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonSpace ORDER by name" ; 
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonSpaceID"] . "'>" . $rowSelect["name"] . "</option>" ; 
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonSpaceID=new LiveValidation('gibbonSpaceID');
								gibbonSpaceID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Date') ?> *</b><br/>
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
							<b><?php print _('Start Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
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
							<b><?php print _('End Time') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Format: hh:mm (24hr)') ?><br/></i></span>
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
					<tr>
						<td> 
							<b><?php print _('Repeat?') ?> *</b><br/>
						</td>
						<td class="right">
							<input readonly name="repeat" id="repeat" value="Coming Soon" type="text" style="width: 300px">
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
		else if ($step==2) {
			print "<h2>" ;
				print _("Step 2 - Availability Check") ;
			print "</h2>" ;
			
			$gibbonSpaceID=NULL ;
			if (isset($_POST["gibbonSpaceID"])) {
				$gibbonSpaceID=$_POST["gibbonSpaceID"] ;
			}
			$date=dateConvert($guid, $_POST["date"]) ;
			$timeStart=$_POST["timeStart"] ;
			$timeEnd=$_POST["timeEnd"] ;
			
			try {
				$dataSelect=array("gibbonSpace"=>$gibbonSpaceID); 
				$sqlSelect="SELECT * FROM gibbonSpace WHERE gibbonSpace.gibbonSpaceID=:gibbonSpace" ; 
				$resultSelect=$connection2->prepare($sqlSelect);
				$resultSelect->execute($dataSelect);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" ;
					print _("Your request failed due to a database error.") ;
				print "</div>" ;
			}
			
			if ($resultSelect->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("Your request failed due to a database error.") ;
				print "</div>" ;
			}
			else {
				$rowSelect=$resultSelect->fetch() ;
				//Check for required fields
				if ($gibbonSpaceID=="" OR $date=="" OR $timeStart=="" OR $timeEnd=="") {
					print "<div class='error'>" ;
						print _("Your request failed because your inputs were invalid.") ;
					print "</div>" ;
				}
				else {
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/spaceBooking_manage_addProcess.php" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td colspan=2>
									<?php
									$available=isSpaceFree($guid, $connection2, $gibbonSpaceID, $date, $timeStart, $timeEnd) ;
									if ($available==TRUE) {
										print "<div class='success'>" ;
											print _('The selected space is available for all of the specified time. Click submit below to complete your booking, before someone else beats you to it.') ;
										print "</div>" ;
									}
									else {
										print "<div class='error'>" ;
											print _('The selected space is not available for some or all of the specified time. Please try again.') ;
										print "</div>" ;
									}
									?>
								</td>
							</tr>
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input type="hidden" name="gibbonSpaceID" value="<?php print $gibbonSpaceID ; ?>">
									<input type="hidden" name="date" value="<?php print $date ; ?>">
									<input type="hidden" name="timeStart" value="<?php print $timeStart ; ?>">
									<input type="hidden" name="timeEnd" value="<?php print $timeEnd ; ?>">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<?php
									if ($available==TRUE) {
										?>
										<input type="submit" value="<?php print _("Submit") ; ?>">
										<?php
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