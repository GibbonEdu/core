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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/spaceBooking_manage.php'>".__($guid, 'Manage Facility Bookings')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Facility Booking').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1) {
            echo '<h2>';
            echo __($guid, 'Step 1 - Choose Facility');
            echo '</h2>';
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/spaceBooking_manage_add.php&step=2' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php echo __($guid, 'Facility') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="foreignKeyID" id="foreignKeyID" class="standardWidth">
								<option value='Please select...'><?php echo __($guid, 'Please select...') ?></option>
								<optgroup label='--<?php echo __($guid, 'Facilities') ?>--'/>" ;
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='gibbonSpaceID-".$rowSelect['gibbonSpaceID']."'>".$rowSelect['name'].'</option>';
            }
            ?>
								</optgroup>
								<optgroup label='--<?php echo __($guid, 'Library') ?>--'/>" ;
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = "SELECT * FROM gibbonLibraryItem WHERE bookable='Y' ORDER BY name";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='gibbonLibraryItemID-".$rowSelect['gibbonLibraryItemID']."'>".$rowSelect['name'].'</option>';
            }
            ?>
								</optgroup>
							</select>
							<script type="text/javascript">
								var gibbonSpaceID=new LiveValidation('gibbonSpaceID');
								gibbonSpaceID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
						</td>
						<td class="right">
							<input name="date" id="date" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
    echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
} else {
    echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
}
            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
    echo 'dd/mm/yyyy';
} else {
    echo $_SESSION[$guid]['i18n']['dateFormat'];
}
            ?>." } ); 
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
							<b><?php echo __($guid, 'Start Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'End Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="" type="text" class="standardWidth">
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
							<b><?php echo __($guid, 'Repeat?') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input checked type="radio" name="repeat" value="No" class="repeat" /> <?php echo __($guid, 'No') ?>
							<input type="radio" name="repeat" value="Daily" class="repeat" /> <?php echo __($guid, 'Daily') ?>
							<input type="radio" name="repeat" value="Weekly" class="repeat" /> <?php echo __($guid, 'Weekly') ?>
						</td>
					</tr>
					<tr id="repeatDailyRow">
						<td> 
							<b><?php echo __($guid, 'Repeat Daily') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Repeat daily for this many days.').'<br/>'.__($guid, 'Does not include non-school days.') ?></span>
						</td>
						<td class="right">
							<input name="repeatDaily" id="repeatDaily" maxlength=2 value="2" type="text" class="standardWidth">
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
							<b><?php echo __($guid, 'Repeat Weekly') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Repeat weekly for this many days.').'<br/>'.__($guid, 'Does not include non-school days.') ?></span>
						</td>
						<td class="right">
							<input name="repeatWeekly" id="repeatWeekly" maxlength=2 value="2" type="text" class="standardWidth">
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
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
		<?php

        } elseif ($step == 2) {
            echo '<h2>';
            echo __($guid, 'Step 2 - Availability Check');
            echo '</h2>';

            $foreignKey = null;
            $foreignKeyID = null;
            if (isset($_POST['foreignKeyID'])) {
                if (substr($_POST['foreignKeyID'], 0, 13) == 'gibbonSpaceID') { //It's a facility
                    $foreignKey = 'gibbonSpaceID';
                    $foreignKeyID = substr($_POST['foreignKeyID'], 14);
                } elseif (substr($_POST['foreignKeyID'], 0, 19) == 'gibbonLibraryItemID') { //It's a library item
                    $foreignKey = 'gibbonLibraryItemID';
                    $foreignKeyID = substr($_POST['foreignKeyID'], 20);
                }
            }
            $date = dateConvert($guid, $_POST['date']);
            $timeStart = $_POST['timeStart'];
            $timeEnd = $_POST['timeEnd'];
            $repeat = $_POST['repeat'];
            $repeatDaily = null;
            $repeatWeekly = null;
            if ($repeat == 'Daily') {
                $repeatDaily = $_POST['repeatDaily'];
            } elseif ($repeat == 'Weekly') {
                $repeatWeekly = $_POST['repeatWeekly'];
            }

            //Check for required fields
            if ($foreignKey == null or $foreignKeyID == null or $foreignKey == '' or $foreignKeyID == '' or $date == '' or $timeStart == '' or $timeEnd == '' or $repeat == '') {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed because your inputs were invalid.');
                echo '</div>';
            } else {
                try {
                    if ($foreignKey == 'gibbonSpaceID') {
                        $dataSelect = array('gibbonSpace' => $foreignKeyID);
                        $sqlSelect = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpace';
                    } elseif ($foreignKey == 'gibbonLibraryItemID') {
                        $dataSelect = array('gibbonLibraryItemID' => $foreignKeyID);
                        $sqlSelect = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
                    }
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                }

                if ($resultSelect->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                } else {
                    $rowSelect = $resultSelect->fetch();

                    $available = false;
                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/spaceBooking_manage_addProcess.php' ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<?php
                            if ($repeat == 'No') {
                                ?>
								<tr>
									<td colspan=2>
										<?php
                                        $available = isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd);
                                if ($available == true) {
                                    ?>
											<tr class='current'>
												<td> 
													<b><?php echo dateConvertBack($guid, $date) ?></b><br/>
													<span class="emphasis small"><?php echo __($guid, 'Available') ?></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php echo $date ?>'>
												</td>
											</tr>
											<?php

                                } else {
                                    ?>
											<tr class='error'>
												<td> 
													<b><?php echo dateConvertBack($guid, $date) ?></b><br/>
													<span class="emphasis small"><?php echo __($guid, 'Not Available') ?></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php echo $date ?>'>
												</td>
											</tr>
											<?php

                                }
                                ?>
									</td>
								</tr>
								<?php

                            } elseif ($repeat == 'Daily' and $repeatDaily >= 2 and $repeatDaily <= 20) { //CREATE DAILY REPEATS
                                $continue = true;
                                $failCount = 0;
                                $successCount = 0;
                                $count = 0;
                                while ($continue) {
                                    $dateTemp = date('Y-m-d', strtotime($date) + (86400 * $count));
                                    if (isSchoolOpen($guid, $dateTemp, $connection2)) {
                                        $available = true;
                                        ++$successCount;
                                        $failCount = 0;
                                        if ($successCount >= $repeatDaily) {
                                            $continue = false;
                                        }
                                        //Print days
                                        if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd) == true) {
                                            ?>
											<tr class='current'>
												<td> 
													<b><?php echo dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span class="emphasis small"></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php echo $dateTemp ?>'>
												</td>
											</tr>
											<?php

                                        } else {
                                            ?>
											<tr class='error'>
												<td> 
													<b><?php echo dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span class="emphasis small"><?php echo __($guid, 'Not Available') ?></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php echo $dateTemp ?>'>
												</td>
											</tr>
											<?php

                                        }
                                    } else {
                                        ++$failCount;
                                        if ($failCount > 100) {
                                            $continue = false;
                                        }
                                    }
                                    ++$count;
                                }
                            } elseif ($repeat == 'Weekly' and $repeatWeekly >= 2 and $repeatWeekly <= 20) {
                                $continue = true;
                                $failCount = 0;
                                $successCount = 0;
                                $count = 0;
                                while ($continue) {
                                    $dateTemp = date('Y-m-d', strtotime($date) + (86400 * 7 * $count));
                                    if (isSchoolOpen($guid, $dateTemp, $connection2)) {
                                        $available = true;
                                        ++$successCount;
                                        $failCount = 0;
                                        if ($successCount >= $repeatWeekly) {
                                            $continue = false;
                                        }
                                        //Print days
                                        if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd) == true) {
                                            ?>
											<tr class='current'>
												<td> 
													<b><?php echo dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span class="emphasis small"></span>
												</td>
												<td class="right">
													<input checked type='checkbox' name='dates[]' value='<?php echo $dateTemp ?>'>
												</td>
											</tr>
											<?php

                                        } else {
                                            ?>
											<tr class='error'>
												<td> 
													<b><?php echo dateConvertBack($guid, $dateTemp) ?></b><br/>
													<span class="emphasis small"><?php echo __($guid, 'Not Available') ?></span>
												</td>
												<td class="right">
													<input disabled type='checkbox' name='dates[]' value='<?php echo $dateTemp ?>'>
												</td>
											</tr>
											<?php

                                        }
                                    } else {
                                        ++$failCount;
                                        if ($failCount > 100) {
                                            $continue = false;
                                        }
                                    }
                                    ++$count;
                                }
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed because your inputs were invalid.');
                                echo '</div>';
                            }
                    ?>
						
							<tr>
								<td colspan=2 class="right">
									<?php
                                    if ($available == true) {
                                        ?>
										<input type="hidden" name="foreignKey" value="<?php echo $foreignKey;
                                        ?>">
										<input type="hidden" name="foreignKeyID" value="<?php echo $foreignKeyID;
                                        ?>">
										<input type="hidden" name="date" value="<?php echo $date;
                                        ?>">
										<input type="hidden" name="timeStart" value="<?php echo $timeStart;
                                        ?>">
										<input type="hidden" name="timeEnd" value="<?php echo $timeEnd;
                                        ?>">
										<input type="hidden" name="repeat" value="<?php echo $repeat;
                                        ?>">
										<input type="hidden" name="repeatDaily" value="<?php echo $repeatDaily;
                                        ?>">
										<input type="hidden" name="repeatWeekly" value="<?php echo $repeatWeekly;
                                        ?>">
										<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
										<input type="submit" value="<?php echo __($guid, 'Submit');
                                        ?>">
										<?php

                                    } else {
                                        echo "<div class='error'>";
                                        echo __($guid, 'There are no sessions available, and so this form cannot be submitted.');
                                        echo '</div>';
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