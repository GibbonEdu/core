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
//Outcomes is the result set of a mysql query of all outcomes from the unit the class belongs to
function makeBlock($guid, $connection2, $i, $mode = 'masterAdd', $title = '', $type = '', $length = '', $contents = '', $complete = 'N', $gibbonUnitBlockID = '', $gibbonUnitClassBlockID = '', $teachersNotes = '', $outerBlock = true, $unitOutcomes = null, $gibbonOutcomeIDList = null)
{
    if ($outerBlock) {
        echo "<div id='blockOuter$i' class='blockOuter'>";
    }
    if ($mode != 'embed') {
        ?>
		<style>
			.sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
			.sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			html>body .sortable li { min-height: 58px; line-height: 1.2em; }
			.sortable .ui-state-highlight { margin-bottom: 5px; min-height: 72px; line-height: 1.2em; width: 100%; }
		</style>

		<script type='text/javascript'>
			$(function() {
				$( ".sortable" ).sortable({
					placeholder: "ui-state-highlight"
				});

				$( ".sortable" ).bind( "sortstart", function(event, ui) {
					$("#blockInner<?php echo $i ?>").css("display","none") ;
					$("#block<?php echo $i ?>").css("height","72px") ;
					$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
					tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					$(".sortable").sortable( "refresh" ) ;
					$(".sortable").sortable( "refreshPositions" ) ;
				});
			});

		</script>
		<script type='text/javascript'>
			$(document).ready(function(){
				$("#blockInner<?php echo $i ?>").css("display","none");
				$("#block<?php echo $i ?>").css("height","72px")

				//Block contents control
				$('#show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php echo $i ?>").is(":visible")) {
						$("#blockInner<?php echo $i ?>").css("display","none");
						$("#block<?php echo $i ?>").css("height","72px")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					} else {
						$("#blockInner<?php echo $i ?>").slideDown("fast", $("#blockInner<?php echo $i ?>").css("display","table-row"));
						$("#block<?php echo $i ?>").css("height","auto")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'teachersNotes<?php echo $i ?>') ;
					}
				});

				<?php if ($mode == 'masterAdd') { ?>
					var titleClick<?php echo $i ?>=false ;
					$('#title<?php echo $i ?>').focus(function() {
						if (titleClick<?php echo $i ?>==false) {
							$('#title<?php echo $i ?>').css("color", "#000") ;
							$('#title<?php echo $i ?>').val("") ;
							titleClick<?php echo $i ?>=true ;
						}
					});

					var typeClick<?php echo $i ?>=false ;
					$('#type<?php echo $i ?>').focus(function() {
						if (typeClick<?php echo $i ?>==false) {
							$('#type<?php echo $i ?>').css("color", "#000") ;
							$('#type<?php echo $i ?>').val("") ;
							typeClick<?php echo $i ?>=true ;
						}
					});

					var lengthClick<?php echo $i ?>=false ;
					$('#length<?php echo $i ?>').focus(function() {
						if (lengthClick<?php echo $i ?>==false) {
							$('#length<?php echo $i ?>').css("color", "#000") ;
							$('#length<?php echo $i ?>').val("") ;
							lengthClick<?php echo $i ?>=true ;
						}
					});
				<?php
}
        ?>

				$('#delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("<?php echo __($guid, 'Are you sure you want to delete this record?') ?>")) {
						$('#block<?php echo $i ?>').fadeOut(600, function(){ $('#block<?php echo $i ?>').remove(); });
					}
				});

				$('#star<?php echo $i ?>').unbind('click').click(function() {
					$("#starBox<?php echo $i ?>").load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Planner/units_edit_starAjax.php",{"gibbonPersonID": "<?php echo $_SESSION[$guid]['gibbonPersonID'] ?>", "gibbonUnitBlockID": "<?php echo $gibbonUnitBlockID ?>", "action": "star", "i": "<?php echo $i ?>" }) ;
				});

				$('#unstar<?php echo $i ?>').unbind('click').click(function() {
					$("#starBox<?php echo $i ?>").load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Planner/units_edit_starAjax.php",{"gibbonPersonID": "<?php echo $_SESSION[$guid]['gibbonPersonID'] ?>", "gibbonUnitBlockID": "<?php echo $gibbonUnitBlockID ?>", "action": "unstar", "i": "<?php echo $i ?>" }) ;
				});
			});
		</script>
		<?php

    }
    ?>
	<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php echo $i ?>" style='padding: 0px'>
		<table class='blank' cellspacing='0' style='width: 100%'>
			<tr>
				<td style='width: 50%'>
					<input name='order[]' type='hidden' value='<?php echo $i ?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=100 id='title<?php echo $i ?>' name='title<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode == 'masterAdd') { echo sprintf(__($guid, 'Block %1$s'), $i);
					} else {
						echo htmlPrep($title);
					}
					?>'><br/>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=50 id='type<?php echo $i ?>' name='type<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php if ($mode == 'masterAdd') { echo __($guid, 'type (e.g. discussion, outcome)');
					} else {
						echo htmlPrep($type);
					}
					?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=3 id='length<?php echo $i ?>' name='length<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode == 'masterAdd') { echo __($guid, 'length (min)');
					} else {
						echo htmlPrep($length);
					}
						?>'>
				</td>
				<td style='text-align: right; width: 50%'>
					<div style='margin-bottom: 5px'>
						<?php
                        if ($mode == 'masterEdit') {
                            //Check if starred
                            try {
                                $dataCheck = array('gibbonUnitBlockID' => $gibbonUnitBlockID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlCheck = 'SELECT * FROM gibbonUnitBlockStar WHERE gibbonPersonID=:gibbonPersonID AND gibbonUnitBlockID=:gibbonUnitBlockID';
                                $resultCheck = $connection2->prepare($sqlCheck);
                                $resultCheck->execute($dataCheck);
                            } catch (PDOException $e) {
                            }
                            if ($resultCheck->rowCount() == 1) {
                                echo "<div style='float: right; margin-top: -2px' id='starBox$i'><img id='unstar$i' title='".__($guid, 'Unstar')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'/></div> ";
                            } else {
                                echo "<div style='float: right; margin-top: -2px' id='starBox$i'><img id='star$i' title='".__($guid, 'Star')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'/></div> ";
                            }
                        }
						if ($mode != 'plannerEdit' and $mode != 'embed') {
							echo "<img style='margin-top: 2px' id='delete$i' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/> ";
						}
						if ($mode == 'workingEdit') {
							//Check that block is still connected to master (poor design in original smart units means that they might be disconnected, and so copyback will not work.
                            try {
                                $dataCheck = array('gibbonUnitBlockID' => $gibbonUnitBlockID, 'gibbonUnitClassBlockID' => $gibbonUnitClassBlockID);
                                $sqlCheck = 'SELECT * FROM gibbonUnitBlock JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) LEFT JOIN gibbonUnitBlockStar ON (gibbonUnitBlockStar.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID';
                                $resultCheck = $connection2->prepare($sqlCheck);
                                $resultCheck->execute($dataCheck);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
							if ($resultCheck->rowCount() == 1) {
								$rowCheck = $resultCheck->fetch();
								if (is_null($rowCheck['gibbonUnitBlockStarID'])) {
									echo "<a onclick='return confirm(\"".__($guid, 'Are you sure you want to leave this page? Any unsaved changes will be lost.')."\")' style='margin-right: 2px; font-weight: normal; font-style: normal; color: #fff' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/units_edit_working_copyback.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonCourseClassID='.$_GET['gibbonCourseClassID'].'&gibbonUnitID='.$_GET['gibbonUnitID']."&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=".$_GET['gibbonUnitClassID']."'><img id='copyback$i' title='Copy Back' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copyback.png'/></a>";
								} else {
									echo "<img style='margin-left: -2px; margin-right: 2px' title='".__($guid, 'This is a Star Block')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'/> ";
								}
							}
						}
						if ($mode != 'embed') {
							echo "<div title='".__($guid, 'Show/Hide Details')."' id='show$i' style='margin-right: 3px; margin-top: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\"); background-repeat: no-repeat'></div></br>";
						}
						?>
					</div>
					<?php
                    if ($mode == 'plannerEdit') {
                        echo '</br>';
                    }
					if ($mode != 'embed') {
						?>
						<div style='margin-right: 5px'>Complete? <input id='complete<?php echo $i ?>' name='complete<?php echo $i ?>' style='margin-right: 2px' type="checkbox" <?php if ($mode == 'masterAdd' or $mode == 'masterEdit') { echo 'disabled';
					} else {
						if ($complete == 'Y') {
							echo 'checked';
						}
					}
						?>></div>
					<?php

					}
					?>
					<input type='hidden' name='gibbonUnitBlockID<?php echo $i ?>' value='<?php echo $gibbonUnitBlockID ?>'>
					<input type='hidden' name='gibbonUnitClassBlockID<?php echo $i ?>' value='<?php echo $gibbonUnitClassBlockID ?>'>
				</td>
			</tr>
			<tr id="blockInner<?php echo $i ?>">
				<td colspan=2 style='vertical-align: top'>
					<?php
                    if ($mode == 'masterAdd') {
                        $contents = getSettingByScope($connection2, 'Planner', 'smartBlockTemplate');
                    }
    				echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Block Contents').'</div>';
                    //Block Contents
                    if ($mode != 'embed') {
                        echo getEditor($guid, false, "contents$i", $contents, 20, true, false, false, true);
                    } else {
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important'><p>$contents</p></div>";
                    }

                    //Teacher's Notes
                    if ($mode != 'embed') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Teacher\'s Notes').'</div>';
                        echo getEditor($guid, false, "teachersNotes$i", $teachersNotes, 20, true, false, false, true);
                    } elseif ($teachersNotes != '') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Teacher\'s Notes').'</div>';
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important; background-color: #F6CECB'><p>$teachersNotes</p></div>";
                    }

                    //Outcomes
                    if ($mode == 'masterAdd') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Outcomes').'</div>';
                        echo "<div class='warning'>".__($guid, 'After creating this unit, you will be able to edit the unit and assign unit outcomes to individual blocks. These will then become lesson outcomes when you deploy a unit.').'</div>';
                    } elseif ($mode == 'masterEdit' or $mode == 'workingDeploy' or $mode == 'workingEdit' or $mode == 'plannerEdit') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Outcomes').'</div>';
                        if (count($unitOutcomes) < 1) {
                            echo "<div class='warning'>".__($guid, 'There are no records to display.').'</div>';
                        } else {
                            echo "<table cellspacing='0' style='width:100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo __($guid, 'Scope');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Category');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Name');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Include');
                            echo '</th>';
                            echo '</tr>';

                            foreach ($unitOutcomes as $unitOutcome) {
                                //COLOR ROW BY STATUS!
                                    echo '<tr>';
                                echo "<td style='padding: 5px!important'>";
                                echo '<b>'.$unitOutcome['scope'].'</b><br/>';
                                if ($unitOutcome['scope'] == 'Learning Area' and $unitOutcome['department'] != '') {
                                    echo "<span style='font-size: 75%; font-style: italic'>".$unitOutcome['department'].'</span>';
                                }
                                echo '</td>';
                                echo "<td style='padding: 5px!important'>";
                                echo $unitOutcome['category'];
                                echo '</td>';
                                echo "<td style='padding: 5px!important'>";
                                echo $unitOutcome['name'];
                                echo '</td>';
                                echo "<td style='padding: 5px!important'>";
                                $checked = '';
                                if (strpos($gibbonOutcomeIDList, $unitOutcome['gibbonOutcomeID']) !== false) {
                                    $checked = 'checked';
                                }
                                echo "<input $checked type='checkbox' name='outcomes".$i."[]' value='".$unitOutcome['gibbonOutcomeID']."' />";
                                echo '</td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                        }
                    }
   				 	?>
				</td>
			</tr>
		</table>
	</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}

function getThread($guid, $connection2, $gibbonPlannerEntryID, $parent, $level, $self, $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role, $links = true, $narrow = false)
{
    $output = '';

    try {
        if ($parent == null) {
            $dataDiscuss = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
            $sqlDiscuss = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo IS NULL ORDER BY timestamp';
        } else {
            $dataDiscuss = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPlannerEntryDiscussIDReplyTo' => $parent, 'gibbonPlannerEntryDiscussID' => $self);
            $sqlDiscuss = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo AND gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID ORDER BY timestamp';
        }
        $resultDiscuss = $connection2->prepare($sqlDiscuss);
        $resultDiscuss->execute($dataDiscuss);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($level == 0 and $resultDiscuss->rowCount() == 0) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'There are no records to display.');
        $output .= '</div>';
    } else {
        while ($rowDiscuss = $resultDiscuss->fetch()) {
            $classExtra = '';
            if ($level == 0) {
                $classExtra = 'chatBoxFirst';
            }
            $output .= "<a name='".$rowDiscuss['gibbonPlannerEntryDiscussID']."'></a>";
            $width = (752 - ($level * 15));
            if ($narrow) {
                $width = (705 - ($level * 15));
            }
            $output .= "<table class='noIntBorder chatBox $classExtra' cellspacing='0' style='width: ".$width.'px; margin-left: '.($level * 15)."px'>";
            $output .= '<tr>';
            $output .= '<td><i>'.formatName($rowDiscuss['title'], $rowDiscuss['preferredName'], $rowDiscuss['surname'], $rowDiscuss['category']).' '.__($guid, 'said').'</i>:</td>';
            $output .= "<td style='text-align: right'><i>".__($guid, 'Posted at').' <b>'.substr($rowDiscuss['timestamp'], 11, 5).'</b> on <b>'.dateConvertBack($guid, substr($rowDiscuss['timestamp'], 0, 10)).'</b></i></td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= "<td style='max-width: ".(700 - ($level * 15))."px;' colspan=2><b>".$rowDiscuss['comment'].'</b></td>';
            $output .= '</tr>';
            $output .= '<tr>';
            if ($links == true) {
                $output .= "<td style='text-align: right' colspan=2><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&replyTo=".$rowDiscuss['gibbonPlannerEntryDiscussID']."&search=$search'>Reply</a> ";
                if ($role == 'Teacher') {
                    $output .= " | <a href='".$_SESSION[$guid]['absoluteURL']."/modules/Planner/planner_view_full_post_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$search&gibbonPlannerEntryDiscussID=".$rowDiscuss['gibbonPlannerEntryDiscussID']."'>Delete</a>";
                }
                $output .= '</td>';
            }
            $output .= '</tr>';
            $output .= '</table>';

            //Get any replies
            $replies = true;
            try {
                $dataReplies = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPlannerEntryDiscussIDReplyTo' => $rowDiscuss['gibbonPlannerEntryDiscussID']);
                $sqlReplies = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo ORDER BY timestamp';
                $resultReplies = $connection2->prepare($sqlReplies);
                $resultReplies->execute($dataReplies);
            } catch (PDOException $e) {
                $replies = false;
                $output .= print "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($replies) {
                while ($rowReplies = $resultReplies->fetch()) {
                    $output .= getThread($guid, $connection2, $gibbonPlannerEntryID, $rowDiscuss['gibbonPlannerEntryDiscussID'], ($level + 1), $rowReplies['gibbonPlannerEntryDiscussID'], $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role, $links);
                }
            }
        }
    }

    return $output;
}

function sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp = '', $gibbonCourseClassID = '')
{
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $output = "<div class='error'>";
        $output .= __($guid, 'The highest grouped action cannot be determined.');
        $output .= '</div>';
    } else {
        //Show date picker in sidebar
        $output = "<h2 class='sidebar'>";
        $output .= __($guid, 'Choose A Date');
        $output .= '</h2>';

        //Count back to first Monday before first day
        $startDayStamp = $todayStamp;
        while (date('D', $startDayStamp) != 'Mon') {
            $startDayStamp = $startDayStamp - 86400;
        }

        //Count forward 6 weeks after start day
        $endDayStamp = $startDayStamp + (86400 * 41);

        //Check which days are school days
        $days = array();
        $days['Mon'] = 'Y';
        $days['Tue'] = 'Y';
        $days['Wed'] = 'Y';
        $days['Thu'] = 'Y';
        $days['Fri'] = 'Y';
        $days['Sat'] = 'Y';
        $days['Sun'] = 'Y';

        try {
            $dataDays = array();
            $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'";
            $resultDays = $connection2->prepare($sqlDays);
            $resultDays->execute($dataDays);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($rowDays = $resultDays->fetch()) {
            if ($rowDays['nameShort'] == 'Mon') {
                $days['Mon'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Tue') {
                $days['Tue'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Wed') {
                $days['Wed'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Thu') {
                $days['Thu'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Fri') {
                $days['Fri'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Sat') {
                $days['Sat'] = 'N';
            } elseif ($rowDays['nameShort'] == 'Sun') {
                $days['Sun'] = 'N';
            }
        }

        $count = 1;

        $output .= "<table class='mini' cellspacing='0' style='width: 250px; margin-bottom: 0px'>";
        $output .= "<tr class='head'>";
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Mon');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Tue');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Wed');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Thu');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Fri');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Sat');
        $output .= '</th>';
        $output .= "<th style='width: 35px; text-align: center'>";
        $output .= __($guid, 'Sun');
        $output .= '</th>';
        $output .= '</tr>';

        for ($i = $startDayStamp;$i <= $endDayStamp;$i = $i + 86400) {
            if (date('D', $i) == 'Mon') {
                $output .= "<tr style='height: 25px'>";
            }

            if ($days[date('D', $i)] == 'N' or isSchoolOpen($guid, date('Y-m-d', $i), $connection2) == false) {
                $output .= "<td style='text-align: center; background-color: #bbbbbb; font-size: 10px; color: #858586'>";
                if ($i == $dateStamp) {
                    $output .= "<span style='border: 1px solid #ffffff; padding: 0px 2px 0px 1px'>".date('d', $i).'</span><br/>';
                    $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                } else {
                    $output .= date('d', $i).'<br/>';
                    $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                }
                $output .= '</td>';
            } else {
                $output .= "<td style='text-align: center; background-color: #eeeeee; font-size: 10px'>";
                if ($i == $dateStamp) {
                    if ($i == $todayStamp) {
                        $output .= "<a style='color: #6B99CE; font-weight: bold; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=".date('Y-m-d', $i)."'>";
                        $output .= "<span style='border: 1px solid #cc0000; padding: 0px 2px 0px 1px'>".date('d', $i).'</span><br/>';
                        $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                        $output .= '</a>';
                    } else {
                        $output .= "<a style='text-decoration: none' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=".date('Y-m-d', $i)."'>";
                        $output .= "<span style='border: 1px solid #cc0000; padding: 0px 2px 0px 1px'>".date('d', $i).'</span><br/>';
                        $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                        $output .= '</a>';
                    }
                } else {
                    if ($i == $todayStamp) {
                        $output .= "<a style='color: #6B99CE; font-weight: bold; text-decoration: none' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=".date('Y-m-d', $i)."'>";
                        $output .= date('d', $i).'<br/>';
                        $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                        $output .= '</a>';
                    } else {
                        $output .= "<a style='text-decoration: none' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&search=$gibbonPersonID&date=".date('Y-m-d', $i)."'>";
                        $output .= date('d', $i).'<br/>';
                        $output .= "<span style='font-size: 65%'>".date('M', $i).'</span>';
                        $output .= '</a>';
                    }
                }
                $output .= '</td>';
            }

            if (date('D', $i) == 'Sun') {
                $output .= '</tr>';
            }
            ++$count;
        }
        $output .= '</table>';

        $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
        $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 200px; margin: 0px 0px'>";
        $output .= '<tr>';
        $output .= "<td style='width: 200px'>";
        $output .= "<input name='q' id='q' type='hidden' value='/modules/Planner/planner.php'>";
        $output .= "<input name='search' id='search' type='hidden' value='$gibbonPersonID'>";
        if ($dateStamp == '') {
            $dateHuman = '';
        } else {
            $dateHuman = date($_SESSION[$guid]['i18n']['dateFormatPHP'], $dateStamp);
        }
        $output .= "<input name='dateHuman' id='dateHuman' maxlength=20 type='text' value='$dateHuman' style='width:161px'>";
        $output .= "<script type='text/javascript'>";
        $output .= '$(function() {';
        $output .= "$('#dateHuman').datepicker();";
        $output .= '});';
        $output .= '</script>';
        $output .= '</td>';
        $output .= "<td class='right'>";
        $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</form>';

        //Show class picker in sidebar
        $output .= '<h2>';
        $output .= __($guid, 'Choose A Class');
        $output .= '</h2>';

        $selectCount = 0;
        $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
        $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>";
        $output .= '<tr>';
        $output .= "<td style='width: 190px'>";
        $output .= "<input name='q' id='q' type='hidden' value='/modules/Planner/planner.php'>";
        $output .= "<input name='search' id='search' type='hidden' value='$gibbonPersonID'>";
        $output .= "<input name='viewBy' id='viewBy' type='hidden' value='class'>";
        $output .= "<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:161px'>";

        $output .= "<option value=''></option>";
        try {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class';
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
            $output .= "<optgroup label='--".__($guid, 'My Classes')."--'>";
        }
        while ($rowSelect = $resultSelect->fetch()) {
            $selected = '';
            if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                $selected = 'selected';
                ++$selectCount;
            }
            $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
        }
        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
            $output .= '</optgroup>';
        }
        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
            try {
                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            $output .= "<optgroup label='--".__($guid, 'All Classes')."--'>";
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                    $selected = 'selected';
                    ++$selectCount;
                }
                $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
            }
            $output .= '</optgroup>';
        }
        $output .= '</select>';
        $output .= '</td>';
        $output .= "<td class='right'>";
        $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</form>';

        if ($_GET['q'] != '/modules/Planner/planner_deadlines.php') {
            //Show upcoming deadlines
            $output .= '<h2>';
            $output .= __($guid, 'Homework & Deadlines');
            $output .= '</h2>';

            try {
                if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'dateTime' => date('Y-m-d H:i:s'), 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s'));
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND role='Student' AND viewableParents='Y' AND homeworkDueDateTime>:dateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime";
                } elseif ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewMyClasses') {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'dateTime' => date('Y-m-d H:i:s'), 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'timeEnd' => date('H:i:s'));
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>:dateTime AND ((date<:date1) OR (date=:date2 AND timeEnd<=:timeEnd)) ORDER BY homeworkDueDateTime";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() < 1) {
                $output .= "<div class='success'>";
                $output .= __($guid, 'No upcoming deadlines!');
                $output .= '</div>';
            } else {
                $output .= '<ol>';
                $count = 0;
                while ($row = $result->fetch()) {
                    if ($count < 5) {
                        $diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
                        $style = "style='padding-right: 3px;'";
                        if ($diff < 2) {
                            $style = "style='padding-right: 3px; border-right: 10px solid #cc0000'";
                        } elseif ($diff < 4) {
                            $style = "style='padding-right: 3px; border-right: 10px solid #D87718'";
                        }
                        $output .= "<li $style>";
                        $output .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$row['gibbonPlannerEntryID'].'&viewBy=date&date='.$row['date']."&width=1000&height=550'>".$row['course'].'.'.$row['class'].'</a><br/>';
                        $output .= "<span style='font-style: italic'>Due at ".substr($row['homeworkDueDateTime'], 11, 5).' on '.dateConvertBack($guid, substr($row['homeworkDueDateTime'], 0, 10));
                        $output .= '</li>';
                    }
                    ++$count;
                }
                $output .= '</ol>';
            }

            $output .= "<p style='padding-top: 15px; text-align: right'>";
            $output .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_deadlines.php&search=$gibbonPersonID'>View Homework</a>";
            $output .= '</p>';
        }
    }

    $_SESSION[$guid]['sidebarExtraPosition'] = 'bottom';

    return $output;
}

function sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID)
{
    $output = '';
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $output = "<div class='error'>";
        $output .= __($guid, 'The highest grouped action cannot be determined.');
        $output .= '</div>';
    } else {
        //Show class picker in sidebar
        $output .= '<h2>';
        $output .= __($guid, 'Choose A Course');
        $output .= '</h2>';

        $selectCount = 0;
        $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
        $output .= "<table class='mini' cellspacing='0' style='width: 100%; margin: 0px 0px'>";
        $output .= '<tr>';
        $output .= "<td style='width: 190px'>";
        $output .= "<input name='q' id='q' type='hidden' value='/modules/Planner/units.php'>";
        $output .= "<input name='gibbonSchoolYearID' id='gibbonSchoolYearID' type='hidden' value='$gibbonSchoolYearID'>";
        $output .= "<select name='gibbonCourseID' id='gibbonCourseID' style='width:161px'>";
        $output .= "<option value=''></option>";
        try {
            if ($highestAction == 'Unit Planner_all') {
                $dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sqlSelect = 'SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort';
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort";
            }
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        while ($rowSelect = $resultSelect->fetch()) {
            $selected = '';
            if ($rowSelect['gibbonCourseID'] == $gibbonCourseID) {
                $selected = 'selected';
                ++$selectCount;
            }
            $output .= "<option $selected value='".$rowSelect['gibbonCourseID']."'>".htmlPrep($rowSelect['course']).' ('.htmlPrep($rowSelect['year']).')</option>';
        }
        $output .= '</select>';
        $output .= '</td>';
        $output .= "<td class='right'>";
        $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</form>';
    }

    $_SESSION[$guid]['sidebarExtraPosition'] = 'bottom';

    return $output;
}

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
function makeBlockOutcome($guid,  $i, $type = '', $gibbonOutcomeID = '', $title = '', $category = '', $contents = '', $id = '', $outerBlock = true, $allowOutcomeEditing = 'Y')
{
    if ($outerBlock) {
        echo "<div id='".$type."blockOuter$i' class='blockOuter'>";
    }
    ?>
		<script>
			$(function() {
				$( "#<?php echo $type ?>" ).sortable({
					placeholder: "<?php echo $type ?>-ui-state-highlight"
				});

				$( "#<?php echo $type ?>" ).bind( "sortstart", function(event, ui) {
					$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
					$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>").sortable( "refreshPositions" ) ;
				});

				$( "#<?php echo $type ?>" ).bind( "sortstop", function(event, ui) {
					//This line has been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
				$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;

				//Block contents control
				$('#<?php echo $type ?>show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#<?php echo $type ?>BlockInner<?php echo $i ?>").is(":visible")) {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					} else {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").slideDown("fast", $("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","table-row"));
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","auto")
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					}
				});

				$('#<?php echo $type ?>delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').fadeOut(600, function(){ $('#<?php echo $type ?><?php echo $i ?>'); });
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').remove();
						<?php echo $type ?>Used[<?php echo $type ?>Used.indexOf("<?php echo $gibbonOutcomeID ?>")]="x" ;
					}
				});

			});
		</script>
		<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="<?php echo $type ?>Block<?php echo $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 50%'>
						<input name='<?php echo $type ?>order[]' type='hidden' value='<?php echo $i ?>'>
						<input name='<?php echo $type ?>gibbonOutcomeID<?php echo $i ?>' type='hidden' value='<?php echo $gibbonOutcomeID ?>'>
						<input readonly maxlength=100 id='<?php echo $type ?>title<?php echo $i ?>' name='<?php echo $type ?>title<?php echo $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php echo $title; ?>'><br/>
						<input readonly maxlength=100 id='<?php echo $type ?>category<?php echo $i ?>' name='<?php echo $type ?>category<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php echo $category; ?>'>
						<script type="text/javascript">
							if($('#<?php echo $type ?>category<?php echo $i ?>').val()=="") {
								$('#<?php echo $type ?>category<?php echo $i ?>').css("border","none") ;
							}
						</script>
					</td>
					<td style='text-align: right; width: 50%'>
						<div style='margin-bottom: 25px'>
							<?php
                            echo "<img id='".$type."delete$i' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/> ";
   	 						echo "<div id='".$type."show$i' title='".__($guid, 'Show/Hide Details')."' style='margin-right: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\"); background-repeat: no-repeat'></div>"; ?>
						</div>
						<input type='hidden' name='id<?php echo $i ?>' value='<?php echo $id ?>'>
					</td>
				</tr>
				<tr id="<?php echo $type ?>BlockInner<?php echo $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php
                            if ($allowOutcomeEditing == 'Y') {
                                echo getEditor($guid, false, $type.'contents'.$i, $contents, 20, false, false, false, true);
                            } else {
                                echo "<div style='padding: 5px'>$contents</div>";
                                echo "<input type='hidden' name='".$type.'contents'.$i."' value='".htmlPrep($contents)."'/>";
                            }
   				 			?>
					</td>
				</tr>
			</table>
		</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}
?>
