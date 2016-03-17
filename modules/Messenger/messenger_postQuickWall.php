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

//Only include module include if it is not already included (which it may be been on the index page)
$included=FALSE ;
$includes=get_included_files() ;
foreach ($includes AS $include) {
	if (str_replace("\\","/",$include)==str_replace("\\","/",$_SESSION[$guid]["absolutePath"] . "/modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php")) {
		$included=TRUE ;
	}
}
if ($included==FALSE) {
	include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'New Quick Wall Message') . "</div>" ;
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
			$addReturnMessage=__($guid, "Your request was completed successfully, but some or all messages could not be delivered.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=__($guid, "Your request failed due to an attachment error.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully.") ;
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	print "<div class='warning'>" ;
		print __($guid, 'This page allows you to quick post a message wall entry to all users, without needing to set a range of options, making it a quick wal to post to the Message Wall.') ;
	print "</div>" ;
			
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/messenger_postQuickWallProcess.php?address=" . $_GET["q"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Delivery Mode') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Message Wall') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Place this message on user\'s message wall?') ?><br/></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="messageWall" class="messageWall" value="Y"/> <?php print __($guid, 'Yes') ?>
				</td>
			</tr>
			<tr id="messageWallRow">
				<td> 
					<b><?php print __($guid, 'Publication Dates') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, 'Select up to three individual dates.') ?></br>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>.<br/></i></span>
				</td>
				<td class="right">
					<input name="date1" id="date1" maxlength=10 value="<?php print dateConvertBack($guid, date("Y-m-d")) ; ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date1=new LiveValidation('date1');
						date1.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#date1" ).datepicker();
						});
					</script>
					<br/>
					<input name="date2" id="date2" maxlength=10 value="" type="text" style="width: 300px; margin-top: 3px">
					<script type="text/javascript">
						var date2=new LiveValidation('date2');
						date2.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#date2" ).datepicker();
						});
					</script>
					<br/>
					<input name="date3" id="date3" maxlength=10 value="" type="text" style="width: 300px; margin-top: 3px">
					<script type="text/javascript">
						var date3=new LiveValidation('date3');
						date3.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#date3" ).datepicker();
						});
					</script>
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Message Details') ?></h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Subject') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="subject" id="subject" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var subject=new LiveValidation('subject');
						subject.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php print __($guid, 'Body') ?> *</b>
					<?php 
					//Attempt to build a signature for the user
					$signature=getSignature($guid, $connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
					print getEditor($guid,  TRUE, "body", $signature, 20, true, true, false, true ) ;
					?>
				</td>
			</tr>
			
			<select name="roleCategories[]" id="roleCategories[]" multiple style="display: none">
				<?php
				try {
					$dataSelect=array(); 
					$sqlSelect="SELECT DISTINCT category FROM gibbonRole ORDER BY category" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				while ($rowSelect=$resultSelect->fetch()) {
					print "<option selected value='" . $rowSelect["category"] . "'>" . htmlPrep(__($guid, $rowSelect["category"])) . "</option>" ;
				}
				?>
			</select>
						
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>