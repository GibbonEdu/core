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

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
//Mode can be masterAdd, masterEdit, workingDeploy, workingEdit, plannerEdit, embed
function makeBlock($guid, $connection2, $i, $mode="masterAdd", $title="", $type="", $length="", $contents="", $complete="N", $gibbonUnitBlockID="", $gibbonUnitClassBlockID="", $teachersNotes="", $outerBlock=TRUE) {	
	if ($outerBlock) {
		print "<div id='blockOuter$i' class='blockOuter'>" ;
	}
	if ($mode!="embed") {
		?>
		<script>
			$(function() {
				$( "#sortable" ).sortable({
					placeholder: "ui-state-highlight"
				});
			
				$( "#sortable" ).bind( "sortstart", function(event, ui) { 
					$("#blockInner<?php print $i ?>").css("display","none") ;
					$("#block<?php print $i ?>").css("height","72px") ;
					$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)"); 
					tinyMCE.execCommand('mceRemoveControl', false, 'contents<?php print $i ?>') ;
					tinyMCE.execCommand('mceRemoveControl', false, 'teachersNotes<?php print $i ?>') ;
					$("#sortable").sortable( "refreshPositions" ) ;
				});
			
				$( "#sortable" ).bind( "sortstop", function(event, ui) {
					//These two lines have been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddControl', false, 'contents<?php print $i ?>') ;
					//tinyMCE.execCommand('mceAddControl', false, 'teachersNotes<?php print $i ?>') ;
					$("#block<?php print $i ?>").css("height","72px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#blockInner<?php print $i ?>").css("display","none");
				$("#block<?php print $i ?>").css("height","72px")
			
				//Block contents control
				$('#show<?php print $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php print $i ?>").is(":visible")) {
						$("#blockInner<?php print $i ?>").css("display","none");
						$("#block<?php print $i ?>").css("height","72px")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)"); 
						tinyMCE.execCommand('mceRemoveControl', false, 'contents<?php print $i ?>') ;
						tinyMCE.execCommand('mceRemoveControl', false, 'teachersNotes<?php print $i ?>') ;
					} else {
						$("#blockInner<?php print $i ?>").slideDown("fast", $("#blockInner<?php print $i ?>").css("display","table-row")); 
						$("#block<?php print $i ?>").css("height","auto")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\'"?>)"); 
						tinyMCE.execCommand('mceRemoveControl', false, 'contents<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddControl', false, 'contents<?php print $i ?>') ;
						tinyMCE.execCommand('mceRemoveControl', false, 'teachersNotes<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddControl', false, 'teachersNotes<?php print $i ?>') ;
					}
				});
			
				<?php if ($mode=="masterAdd") { ?>
					var titleClick<?php print $i ?>=false ;
					$('#title<?php print $i ?>').focus(function() {
						if (titleClick<?php print $i ?>==false) {
							$('#title<?php print $i ?>').css("color", "#000") ;
							$('#title<?php print $i ?>').val("") ;
							titleClick<?php print $i ?>=true ;
						}
					});
				
					var typeClick<?php print $i ?>=false ;
					$('#type<?php print $i ?>').focus(function() {
						if (typeClick<?php print $i ?>==false) {
							$('#type<?php print $i ?>').css("color", "#000") ;
							$('#type<?php print $i ?>').val("") ;
							typeClick<?php print $i ?>=true ;
						}
					});
				
					var lengthClick<?php print $i ?>=false ;
					$('#length<?php print $i ?>').focus(function() {
						if (lengthClick<?php print $i ?>==false) {
							$('#length<?php print $i ?>').css("color", "#000") ;
							$('#length<?php print $i ?>').val("") ;
							lengthClick<?php print $i ?>=true ;
						}
					});
				<?php } ?>
			
				$('#delete<?php print $i ?>').unbind('click').click(function() {
					if (confirm("<?php print _('Are you sure you want to delete this record?') ?>")) {
						$('#blockOuter<?php print $i ?>').fadeOut(600, function(){ $('#block<?php print $i ?>').remove(); });
					}
				});
			});
		</script>
		<?php
	}
	?>
	<div class='hiddenReaveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php print $i ?>" style='padding: 0px'>
		<table class='blank' cellspacing='0' style='width: 100%'>
			<tr>
				<td style='width: 50%'>
					<input name='order[]' type='hidden' value='<?php print $i ?>'>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=100 id='title<?php print $i ?>' name='title<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode=="masterAdd") { print sprintf(_('Block %1$s'), $i) ;} else { print htmlPrep($title) ;} ?>'><br/>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=50 id='type<?php print $i ?>' name='type<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php if ($mode=="masterAdd") { print _("type (e.g. discussion, outcome)") ;} else { print htmlPrep($type) ;} ?>'>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=3 id='length<?php print $i ?>' name='length<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode=="masterAdd") { print _("length (min)") ;} else { print htmlPrep($length) ;} ?>'>
				</td>
				<td style='text-align: right; width: 50%'>
					<div style='margin-bottom: 5px'>
						<?php
						if ($mode!="plannerEdit" AND $mode!="embed") {
							print "<img id='delete$i' title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/> " ;
						}
						if ($mode=="workingEdit") {
							//Check that block is still connected to master (poor design in original smart units means that they might be disconnected, and so copyback will not work.
							try {
								$dataCheck=array("gibbonUnitBlockID"=>$gibbonUnitBlockID, "gibbonUnitClassBlockID"=>$gibbonUnitClassBlockID); 
								$sqlCheck="SELECT * FROM gibbonUnitBlock JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID" ;
								$resultCheck=$connection2->prepare($sqlCheck);
								$resultCheck->execute($dataCheck);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultCheck->rowCount()==1) {
								print "<a onclick='return confirm(\"" . _('Are you sure you want to leave this page? Any unsaved changes will be lost.') . "\")' style='font-weight: normal; font-style: normal; color: #fff' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units_edit_working_copyback.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=" . $_GET["gibbonUnitClassID"] . "'><img id='copyback$i' title='Copy Back' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copyback.png'/></a>" ;
							}
						}
						if ($mode!="embed") {
							print "<div title='" . _('Show/Hide Details') . "' id='show$i' style='margin-top: -1px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\")'></div></br>" ;
						}
						?>
					</div>
					<?php
					if ($mode=="plannerEdit") {
						print "</br>" ;
					}
					if ($mode!="embed") {
						?>
						<div style='margin-right: 5px'>Complete? <input id='complete<?php print $i ?>' name='complete<?php print $i ?>' style='margin-right: 2px' type="checkbox" <?php if ($mode=="masterAdd" OR $mode=="masterEdit") { print "disabled" ; } else { if ($complete=="Y") { print "checked" ; }}?>></div>
						<?php
					}
					?>
					<input type='hidden' name='gibbonUnitBlockID<?php print $i ?>' value='<?php print $gibbonUnitBlockID ?>'>
					<input type='hidden' name='gibbonUnitClassBlockID<?php print $i ?>' value='<?php print $gibbonUnitClassBlockID ?>'>
				</td>
			</tr>
			<tr id="blockInner<?php print $i ?>">
				<td colspan=2 style='vertical-align: top'>
					<?php 
					if ($mode=="masterAdd") { 
						$contents=getSettingByScope($connection2, "Planner", "smartBlockTemplate" ) ; 
					}
					print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>" . _('Block Contents') . "</div>" ;
					if ($mode!="embed") {
						print getEditor($guid, FALSE, "contents$i", $contents, 20, true, false, false, true) ;
					}
					else {
						print "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important'><p>$contents</p></div>" ;
					}
					if ($mode!="embed") {
						print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>" . _('Teacher\'s Notes') . "</div>" ;
						print getEditor($guid, FALSE, "teachersNotes$i", $teachersNotes, 20, true, false, false, true) ;
					}
					else if ($teachersNotes!="") {
						print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>Teacher's Notes</div>" ;
						print "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important; background-color: #F6CECB'><p>$teachersNotes</p></div>" ;
					}
					?>
				</td>
			</tr>
		</table>
	</div>
	<?php
	if ($outerBlock) {
		print "</div>" ;
	}
}

function getThread($guid, $connection2, $gibbonPlannerEntryID, $parent, $level, $self, $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role) {
	$output="" ;
	
	try {
		if ($parent==NULL) {
			$dataDiscuss=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
			$sqlDiscuss="SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo IS NULL ORDER BY timestamp" ;
		}
		else {
			$dataDiscuss=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPlannerEntryDiscussIDReplyTo"=>$parent, "gibbonPlannerEntryDiscussID"=>$self); 
			$sqlDiscuss="SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo AND gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID ORDER BY timestamp" ;
		}
		$resultDiscuss=$connection2->prepare($sqlDiscuss);
		$resultDiscuss->execute($dataDiscuss);
	}
	catch(PDOException $e) { 
		$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($level==0 AND $resultDiscuss->rowCount()==0) {
		$output.="<div class='error'>" ;
			$output.=_("There are no records to display.") ;
		$output.="</div>" ;
	}
	else {
		 while ($rowDiscuss=$resultDiscuss->fetch()) {
			if ($level==0) {
				$border="2px solid #333" ;
				$margintop="25px" ; 
			}
			else {
				$border="2px solid #333" ;
				$margintop="0px" ;
			}
			$output.="<a name='" . $rowDiscuss["gibbonPlannerEntryDiscussID"] . "'></a>" ; 
			$output.="<table cellspacing='0' style='width: " . (755-($level*15)) . "px ; padding: 1px 3px; margin-bottom: -2px; margin-top: $margintop; margin-left: " . ($level*15) . "px; border: $border ; background-color: #f9f9f9'>" ;
				$output.="<tr>" ;
					$output.="<td style='color: #777'><i>". formatName($rowDiscuss["title"], $rowDiscuss["preferredName"], $rowDiscuss["surname"], $rowDiscuss["category"]) . " " . _('said') . "</i>:</td>" ;
					$output.="<td style='color: #777; text-align: right'><i>" . _('Posted at') . " <b>" . substr($rowDiscuss["timestamp"],11,5) . "</b> on <b>" . dateConvertBack($guid, substr($rowDiscuss["timestamp"],0,10)) . "</b></i></td>" ;
				$output.="</tr>" ;
				$output.="<tr>" ;
					$borderleft="4px solid #1B9F13" ;
					if ($rowDiscuss["timestamp"]>=$_SESSION[$guid]["lastTimestamp"]) {
						$borderleft="4px solid #c00" ;
					}
					$output.="<td style='padding: 1px 4px; border-left: $borderleft' colspan=2><b>" . $rowDiscuss["comment"] . "</b></td>" ;
				$output.="</tr>" ;
				$output.="<tr>" ;
					$output.="<td style='text-align: right' colspan=2><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&replyTo=" . $rowDiscuss["gibbonPlannerEntryDiscussID"] . "&search=$search'>Reply</a> " ;						
					if ($role=="Teacher") {
						$output.=" | <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/planner_view_full_post_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$search&gibbonPlannerEntryDiscussID=" . $rowDiscuss["gibbonPlannerEntryDiscussID"] . "'>Delete</a>" ;
					}
					$output.="</td>" ;
				$output.="</tr>" ;
				
				
			$output.="</table>" ; 
			
			//Get any replies
			$replies=true ;
			try {
				$dataReplies=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPlannerEntryDiscussIDReplyTo"=>$rowDiscuss["gibbonPlannerEntryDiscussID"]); 
				$sqlReplies="SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo ORDER BY timestamp" ;
				$resultReplies=$connection2->prepare($sqlReplies);
				$resultReplies->execute($dataReplies);
			}
			catch(PDOException $e) { 
				$replies=false ;
				$output.=print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($replies) {
				while ($rowReplies=$resultReplies->fetch()) {
					$output.=getThread($guid, $connection2, $gibbonPlannerEntryID, $rowDiscuss["gibbonPlannerEntryDiscussID"], ($level+1), $rowReplies["gibbonPlannerEntryDiscussID"], $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role) ;
				}
			}
		}
	}
	
	return $output ;
}

function sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp="", $gibbonCourseClassID="") {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		$output="<div class='error'>" ;
		$output.=_("The highest grouped action cannot be determined.") ;
		$output.="</div>" ;
	}
	else {
		//Show date picker in sidebar
		$output="<h2 class='sidebar'>" ;
		$output.=_("Choose A Date") ;
		$output.="</h2>" ;
			
		//Count back to first Monday before first day
		$startDayStamp=$todayStamp;
		while (date("D",$startDayStamp)!="Mon") {
			$startDayStamp=$startDayStamp-86400 ;
		}
		
		//Count forward 6 weeks after start day
		$endDayStamp=$startDayStamp+(86400*41) ;
		
		//Check which days are school days
		$days=array() ;
		$days["Mon"]="Y" ;
		$days["Tue"]="Y" ;
		$days["Wed"]="Y" ;
		$days["Thu"]="Y" ;
		$days["Fri"]="Y" ;
		$days["Sat"]="Y" ;
		$days["Sun"]="Y" ;
		
		try {
			$dataDays=array(); 
			$sqlDays="SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'" ;
			$resultDays=$connection2->prepare($sqlDays);
			$resultDays->execute($dataDays);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		while ($rowDays=$resultDays->fetch()) {
			if ($rowDays["nameShort"]=="Mon") {
				$days["Mon"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Tue") {
				$days["Tue"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Wed") {
				$days["Wed"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Thu") {
				$days["Thu"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Fri") {
				$days["Fri"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Sat") {
				$days["Sat"]="N" ;
			}
			else if ($rowDays["nameShort"]=="Sun") {
				$days["Sun"]="N" ;
			}
		}
		
		$count=1;
		
		$output.="<table class='mini' cellspacing='0' style='width: 250px; margin-bottom: 0px'>" ;
			$output.="<tr class='head'>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Mon") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Tue") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Wed") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Thu") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Fri") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Sat") ;
				$output.="</th>" ;
				$output.="<th style='width: 35px; text-align: center'>" ;
					$output.=_("Sun") ;
				$output.="</th>" ;
			$output.="</tr>" ;
			
			for ($i=$startDayStamp;$i<=$endDayStamp;$i=$i+86400) {
				if (date("D",$i)=="Mon") {
					$output.="<tr style='height: 25px'>" ;
				}
				
				if ($days[date("D",$i)]=="N" OR isSchoolOpen($guid, date("Y-m-d", $i), $connection2)==FALSE) {
					$output.="<td style='text-align: center; background-color: #bbbbbb; font-size: 10px; color: #858586'>" ;
						if ($i==$dateStamp) {
							$output.="<span style='border: 1px solid #ffffff; padding: 0px 2px 0px 1px'>" . date("d", $i) . "</span><br/>" ;
							$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
						}
						else {
							$output.=date("d", $i) . "<br/>" ;
							$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
						}
					$output.="</td>" ;
				}
				else {
					$output.="<td style='text-align: center; background-color: #eeeeee; font-size: 10px'>" ;
						if ($i==$dateStamp) {
							if ($i==$todayStamp) {
								$output.="<a style='color: #6B99CE; font-weight: bold; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=" . date("Y-m-d", $i) . "'>" ;
								$output.="<span style='border: 1px solid #cc0000; padding: 0px 2px 0px 1px'>" . date("d", $i) . "</span><br/>" ;
								$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
								$output.="</a>" ;
							}
							else {
								$output.="<a style='text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=" . date("Y-m-d", $i) . "'>" ;
								$output.="<span style='border: 1px solid #cc0000; padding: 0px 2px 0px 1px'>" . date("d", $i) . "</span><br/>" ;
								$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
								$output.="</a>" ;
							}
						}
						else {
							if ($i==$todayStamp) {
								$output.="<a style='color: #6B99CE; font-weight: bold; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=" . date("Y-m-d", $i) . "'>" ;
								$output.=date("d", $i) . "<br/>" ;
								$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
								$output.="</a>" ;
							}
							else {
								$output.="<a style='text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=" . date("Y-m-d", $i) . "'>" ;
								$output.=date("d", $i) . "<br/>" ;
								$output.="<span style='font-size: 65%'>" . date("M", $i) . "</span>" ;
								$output.="</a>" ;
							}
							
						}
					$output.="</td>" ;
				}
				
				if (date("D",$i)=="Sun") {
					$output.="</tr>" ;
				}
				$count++ ;
			}
		$output.="</table>" ;
		
		$output.="<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
			$output.="<table class='smallIntBorder' cellspacing='0' style='width: 200px; margin: 0px 0px'>" ;	
				$output.="<tr>" ;
					$output.="<td style='width: 200px'>" ; 
						$output.="<input name='q' id='q' type='hidden' value='/modules/Planner/planner.php'>" ;
						$output.="<input name='search' id='search' type='hidden' value='$gibbonPersonID'>" ;
						if ($dateStamp=="") {
							$dateHuman="" ;
						}
						else {
							$dateHuman=date("d/m/Y", $dateStamp) ;
						}
						$output.="<input name='dateHuman' id='dateHuman' maxlength=20 type='text' value='$dateHuman' style='width:161px'>" ;
						$output.="<script type='text/javascript'>" ;
							$output.="$(function() {" ;
								$output.="$('#dateHuman').datepicker();" ;
							$output.="});" ;
						$output.="</script>" ;
					$output.="</td>" ;
					$output.="<td class='right'>" ;
						$output.="<input type='submit' value='" . _('Go') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
		
		
		//Show class picker in sidebar
		$output.="<h2>" ;
		$output.=_("Choose A Class") ;
		$output.="</h2>" ;
		
		$selectCount=0 ;
		$output.="<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
			$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;	
				$output.="<tr>" ;
					$output.="<td style='width: 190px'>" ; 
						$output.="<input name='q' id='q' type='hidden' value='/modules/Planner/planner.php'>" ;
						$output.="<input name='search' id='search' type='hidden' value='$gibbonPersonID'>" ;
						$output.="<input name='viewBy' id='viewBy' type='hidden' value='class'>" ;
						$output.="<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:161px'>" ;
							
							$output.="<option value=''></option>" ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
								$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
								$output.="<optgroup label='--" . _('My Classes') . "--'>" ;
							}
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID AND $selectCount==0) {
									$selected="selected" ;
									$selectCount++ ;
								}
								$output.="<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}
							if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
								$output.="</optgroup>" ;
							}
							if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								$output.="<optgroup label='--" . _('All Classes') . "--'>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID AND $selectCount==0) {
										$selected="selected" ;
										$selectCount++ ;
									}
									$output.="<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
								}
								$output.="</optgroup>" ;
							}
						 $output.="</select>" ;
					$output.="</td>" ;
					$output.="<td class='right'>" ;
						$output.="<input type='submit' value='" . _('Go') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
	
	
		if ($_GET["q"]!="/modules/Planner/planner_deadlines.php") {
			//Show upcoming deadlines
			$output.="<h2>" ;
			$output.=_("Homework & Deadlines") ;
			$output.="</h2>" ;
			
			
			try {
				if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "dateTime"=>date("Y-m-d H:i:s"), "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "timeEnd"=>date("H:i:s")); 
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND role='Student' AND viewableParents='Y' AND homeworkDueDateTime>:dateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime" ;
				}
				else if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewMyClasses") {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "dateTime"=>date("Y-m-d H:i:s"), "date1"=>date("Y-m-d"), "date2"=>date("Y-m-d"), "timeEnd"=>date("H:i:s")); 
					$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>:dateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime" ;
				}	
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()<1) {
				$output.="<div class='success'>" ;
					$output.=_("No upcoming deadlines!") ;
				$output.="</div>" ;
			}
			else {
				$output.="<ol>" ;
				$count=0 ;
				while ($row=$result->fetch()) {
					if ($count<5) {
						$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
						$style="style='padding-right: 3px;'" ;
						if ($diff<2) {
							$style="style='padding-right: 3px; border-right: 10px solid #cc0000'" ;	
						}
						else if ($diff<4) {
							$style="style='padding-right: 3px; border-right: 10px solid #D87718'" ;	
						}
						$output.="<li $style>" ;
						$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=date&date=" . $row["date"] . "&width=1000&height=550'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
						$output.="<span style='font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
						$output.="</li>" ;
					}
					$count++ ;
				}
				$output.="</ol>" ;
			}
			
			$output.="<p style='padding-top: 15px; text-align: right'>" ;
			$output.="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&search=$gibbonPersonID'>View Homework</a>" ;
			$output.="</p>" ;
		}
	}
		
	$_SESSION[$guid]["sidebarExtraPosition"]="bottom" ;
	return $output ;
}

function sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) {
	$output="" ;
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		$output="<div class='error'>" ;
		$output.=_("The highest grouped action cannot be determined.") ;
		$output.="</div>" ;
	}
	else {
		//Show class picker in sidebar
		$output.="<h2>" ;
		$output.=_("Choose A Course") ;
		$output.="</h2>" ;
		
		$selectCount=0 ;
		$output.="<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
			$output.="<table class='mini' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;	
				$output.="<tr>" ;
					$output.="<td style='width: 190px'>" ; 
						$output.="<input name='q' id='q' type='hidden' value='/modules/Planner/units.php'>" ;
						$output.="<input name='gibbonSchoolYearID' id='gibbonSchoolYearID' type='hidden' value='$gibbonSchoolYearID'>" ;
						$output.="<select name='gibbonCourseID' id='gibbonCourseID' style='width:161px'>" ;
							$output.="<option value=''></option>" ;
							try {
								if ($highestAction=="Manage Units_all") {
									$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
									$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort" ;
								}
								else if ($highestAction=="Manage Units_learningAreas") {
									$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort" ;
								}
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonCourseID"]==$gibbonCourseID) {
									$selected="selected" ;
									$selectCount++ ;
								}
								$output.="<option $selected value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["course"]) . " (" . htmlPrep($rowSelect["year"]) . ")</option>" ;
							}
						 $output.="</select>" ;
					$output.="</td>" ;
					$output.="<td class='right'>" ;
						$output.="<input type='submit' value='" . _('Go') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
	}
	
	$_SESSION[$guid]["sidebarExtraPosition"]="bottom" ;
	return $output ;
}

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
function makeBlockOutcome($guid,  $i, $type="", $gibbonOutcomeID="", $title="", $category="", $contents="", $id="", $outerBlock=TRUE, $allowOutcomeEditing="Y") {	
	if ($outerBlock) {
		print "<div id='" . $type . "blockOuter$i'>" ;
	}
	?>
		<script>
			$(function() {
				$( "#<?php print $type ?>" ).sortable({
					placeholder: "<?php print $type ?>-ui-state-highlight"
				});
				
				$( "#<?php print $type ?>" ).bind( "sortstart", function(event, ui) { 
					$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
					$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
					$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)");  
					tinyMCE.execCommand('mceRemoveControl', false, '<?php print $type ?>contents<?php print $i ?>') ;
					$("#<?php print $type ?>").sortable( "refreshPositions" ) ;
				});
				
				$( "#<?php print $type ?>" ).bind( "sortstop", function(event, ui) {
					//This line has been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddControl', false, '<?php print $type ?>contents<?php print $i ?>') ;
					$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
				$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
				
				//Block contents control
				$('#<?php print $type ?>show<?php print $i ?>').unbind('click').click(function() {
					if ($("#<?php print $type ?>BlockInner<?php print $i ?>").is(":visible")) {
						$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
						$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
						$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)");  
						tinyMCE.execCommand('mceRemoveControl', false, '<?php print $type ?>contents<?php print $i ?>') ;
					} else {
						$("#<?php print $type ?>BlockInner<?php print $i ?>").slideDown("fast", $("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","table-row")); 
						$("#<?php print $type ?>Block<?php print $i ?>").css("height","auto")
						$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\'"?>)");  
						tinyMCE.execCommand('mceRemoveControl', false, '<?php print $type ?>contents<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddControl', false, '<?php print $type ?>contents<?php print $i ?>') ;
					}
				});
				
				$('#<?php print $type ?>delete<?php print $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#<?php print $type ?>blockOuter<?php print $i ?>').fadeOut(600, function(){ $('#<?php print $type ?><?php print $i ?>'); });
						$('#<?php print $type ?>blockOuter<?php print $i ?>').remove();
						<?php print $type ?>Used[<?php print $type ?>Used.indexOf("<?php print $gibbonOutcomeID ?>")]="x" ;
					}
				});
				
			});
		</script>
		<div class='hiddenReaveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="<?php print $type ?>Block<?php print $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 50%'>
						<input name='<?php print $type ?>order[]' type='hidden' value='<?php print $i ?>'>
						<input name='<?php print $type ?>gibbonOutcomeID<?php print $i ?>' type='hidden' value='<?php print $gibbonOutcomeID ?>'>
						<input readonly maxlength=100 id='<?php print $type ?>title<?php print $i ?>' name='<?php print $type ?>title<?php print $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php print $title ; ?>'><br/>
						<input readonly maxlength=100 id='<?php print $type ?>category<?php print $i ?>' name='<?php print $type ?>category<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php print $category ; ?>'>
						<script type="text/javascript">
							if($('#<?php print $type ?>category<?php print $i ?>').val()=="") {
								$('#<?php print $type ?>category<?php print $i ?>').css("border","none") ;
							}
						</script>
					</td>
					<td style='text-align: right; width: 50%'>
						<div style='margin-bottom: 25px'>
							<?php
							print "<img id='" . $type  . "delete$i' title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/> " ;
							print "<div id='" . $type . "show$i' title='" . _('Show/Hide Details') . "' style='margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\")'></div>" ;
							?>
						</div>
						<input type='hidden' name='id<?php print $i ?>' value='<?php print $id ?>'>
					</td>
				</tr>
				<tr id="<?php print $type ?>BlockInner<?php print $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php 
							if ($allowOutcomeEditing=="Y") {
								print getEditor($guid, FALSE, $type . "contents" . $i, $contents, 20, false, false, false, true) ;
							}
							else {
								print "<div style='padding: 5px'>$contents</div>" ;
								print "<input type='hidden' name='" . $type . "contents" . $i . "' value='" . htmlPrep($contents) . "'/>" ;
							}
						?>
					</td>
				</tr>
			</table>
		</div>
	<?php
	if ($outerBlock) {
		print "</div>" ;
	}
}
?>
