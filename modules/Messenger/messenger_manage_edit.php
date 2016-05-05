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

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage_edit.php') == false) {
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
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/messenger_manage.php&search=$search'>".__($guid, 'Manage Messages')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Message').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonMessengerID = $_GET['gibbonMessengerID'];
        if ($gibbonMessengerID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Messages_all') {
                    $data = array('gibbonMessengerID' => $gibbonMessengerID);
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID';
                } else {
                    $data = array('gibbonMessengerID' => $gibbonMessengerID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessenger.gibbonPersonID=:gibbonPersonID';
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();
                ?>
				<div class='warning'>
					<b><u><?php echo __($guid, 'Note') ?></u></b>: <?php echo __($guid, 'Changes made here do not apply to emails and SMS messages (which have already been sent), but only to message wall messages.') ?>
				</div>

				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/messenger_manage_editProcess.php?gibbonMessengerID=$gibbonMessengerID&search=$search&address=".$_GET['q'] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Delivery Mode') ?></h3>
							</td>
						</tr>
						<?php
                        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byEmail')) {
                            ?>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Email') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Deliver this message to user\'s primary email account?') ?><br/></span>
								</td>
								<td class="right">
									<?php
                                    if ($row['email'] == 'Y') {
                                        echo "<img title='".__($guid, 'Sent by email.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                                    } else {
                                        echo "<img title='".__($guid, 'Not sent by email.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                                    }
                            ?>
								</td>
							</tr>
							<?php

                        }
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byMessageWall')) {
							?>
							<script type="text/javascript">
								$(document).ready(function(){
									$(".messageWall").click(function(){
										if ($('input[name=messageWall]:checked').val()=="Y" ) {
											$("#messageWallRow").slideDown("fast", $("#messageWallRow").css("display","table-row"));
										} else {
											$("#messageWallRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Message Wall') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Place this message on user\'s message wall?') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($row['messageWall'] == 'Y') { echo 'checked'; } ?> type="radio" name="messageWall" class="messageWall" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($row['messageWall'] == 'N') { echo 'checked'; } ?> type="radio" name="messageWall" class="messageWall" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<tr id="messageWallRow" <?php if ($row['messageWall'] == 'N') { echo "style='display: none'"; } ?>>
								<td>
									<b><?php echo __($guid, 'Publication Dates') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Select up to three individual dates.') ?></br><?php echo __($guid, 'Format:').' ';
									if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
										echo 'dd/mm/yyyy';
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormat'];
									}
									?>.<br/></span>
								</td>
								<td class="right">
									<input name="date1" id="date1" maxlength=10 value="<?php echo dateConvertBack($guid, $row['messageWall_date1']) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var date1=new LiveValidation('date1');
										date1.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
                    					?>." } );
									</script>
									<script type="text/javascript">
										$(function() {
											$( "#date1" ).datepicker();
										});
									</script>
									<br/>
									<input name="date2" id="date2" maxlength=10 value="<?php echo dateConvertBack($guid, $row['messageWall_date2']) ?>" type="text" style="width: 300px; margin-top: 3px">
									<script type="text/javascript">
										var date2=new LiveValidation('date2');
										date2.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
                    					?>." } );
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#date2" ).datepicker();
										});
									</script>
									<br/>
									<input name="date3" id="date3" maxlength=10 value="<?php echo dateConvertBack($guid, $row['messageWall_date3']) ?>" type="text" style="width: 300px; margin-top: 3px">
									<script type="text/javascript">
										var date3=new LiveValidation('date3');
										date3.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
										?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
                    					?>." } );
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#date3" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<?php

						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_bySMS')) {
							?>
							<tr>
								<td>
									<b><?php echo __($guid, 'SMS') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Deliver this message to user\'s mobile phone?') ?><br/></span>
								</td>
								<td class="right">
									<?php
                                    if ($row['sms'] == 'Y') {
                                        echo "<img title='".__($guid, 'Sent by sms.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                                    } else {
                                        echo "<img title='".__($guid, 'Not sent by sms.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                                    }
                   		 			?>
								</td>
							</tr>
							<?php

						}
						?>


						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Message Details') ?></h3>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Subject') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input name="subject" id="subject" maxlength=30 value="<?php echo htmlPrep($row['subject']) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var subject=new LiveValidation('subject');
									subject.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<b><?php echo __($guid, 'Body') ?> *</b>
								<?php echo getEditor($guid,  true, 'body', $row['body'], 20, true, true, false, true, 'purpose=Mass%20Mailer%20Attachment') ?>
							</td>
						</tr>

						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Targets') ?></h3>
							</td>
						</tr>
						<?php
                        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_role')) {
                            //Role
                            try {
                                $dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
                                $sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Role'";
                                $resultTarget = $connection2->prepare($sqlTarget);
                                $resultTarget->execute($dataTarget);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            ?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#roleRow").css("display","none");
									<?php } ?>
									$(".role").click(function(){
										if ($('input[name=role]:checked').val()=="Y" ) {
											$("#roleRow").slideDown("fast", $("#roleRow").css("display","table-row"));
										} else {
											$("#roleRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Role') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Users of a certain type.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="role" class="role" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="role" class="role" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
                            while ($rowTarget = $resultTarget->fetch()) {
                                $selectedAll .= str_pad($rowTarget['id'], 3, '0', STR_PAD_LEFT).',';
                            }
                            $selectedAll = substr($selectedAll, 0, -1);
                            ?>
							<tr id="roleRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Roles') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="roles[]" id="roles[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT * FROM gibbonRole ORDER BY name';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonRoleID'], 3, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonRoleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).' ('.htmlPrep(__($guid, $rowSelect['category'])).')</option>';
										}
										?>
									</select>
								</td>
							</tr>

							<?php
                            //Role Category
                            try {
                                $dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
                                $sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Role Category'";
                                $resultTarget = $connection2->prepare($sqlTarget);
                                $resultTarget->execute($dataTarget);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            ?>
							<script type="text/javascript">
								/* Role Category Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#roleCategoryRow").css("display","none");
									<?php } ?>
									$(".roleCategory").click(function(){
										if ($('input[name=roleCategory]:checked').val()=="Y" ) {
											$("#roleCategoryRow").slideDown("fast", $("#roleCategoryRow").css("display","table-row"));
										} else {
											$("#roleCategoryRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Role Category') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Users of a certain type.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="roleCategory" class="roleCategory" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="roleCategory" class="roleCategory" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
                            while ($rowTarget = $resultTarget->fetch()) {
                                $selectedAll .= $rowTarget['id'].',';
                            }
                            $selectedAll = substr($selectedAll, 0, -1);
                            ?>
							<tr id="roleCategoryRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Role Categories') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="roleCategories[]" id="roleCategories[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT DISTINCT category FROM gibbonRole ORDER BY category';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if (is_numeric(strpos($selectedAll, $rowSelect['category']))) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['category']."'>".htmlPrep(__($guid, $rowSelect['category'])).'</option>';
									}
									?>
									</select>
								</td>
							</tr>
							<?php

                        }
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_yearGroups_any')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Year Group'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}

							?>
							<script type="text/javascript">
								/* yearGroup Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#yearGroupRow").css("display","none");
										$("#yearGroupRow2").css("display","none");
										$("#yearGroupRow3").css("display","none");
										$("#yearGroupRow4").css("display","none");
									<?php } ?>
									$(".yearGroup").click(function(){
										if ($('input[name=yearGroup]:checked').val()=="Y" ) {
											$("#yearGroupRow").slideDown("fast", $("#yearGroupRow").css("display","table-row"));
											$("#yearGroupRow2").slideDown("fast", $("#yearGroupRow2").css("display","table-row"));
											$("#yearGroupRow3").slideDown("fast", $("#yearGroupRow3").css("display","table-row"));
											$("#yearGroupRow4").slideDown("fast", $("#yearGroupRow4").css("display","table-row"));
										} else {
											$("#yearGroupRow").css("display","none");
											$("#yearGroupRow2").css("display","none");
											$("#yearGroupRow3").css("display","none");
											$("#yearGroupRow4").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Year Group') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Students in year; all staff.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="yearGroup" class="yearGroup" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="yearGroup" class="yearGroup" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							$staff = true;
							$students = true;
							$parents = true;
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 3, '0', STR_PAD_LEFT).',';
								if ($rowTarget['staff'] == 'N') {
									$staff = false;
								}
								if ($rowTarget['students'] == 'N') {
									$students = false;
								}
								if ($rowTarget['parents'] == 'N') {
									$parents = false;
								}
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="yearGroupRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Year Groups') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="yearGroups[]" id="yearGroups[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonYearGroupID'], 3, '0', STR_PAD_LEFT)))) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>
									</select>
								</td>
							</tr>
							<tr id="yearGroupRow3">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include staff?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="yearGroupsStaff" id="yearGroupsStaff" class="standardWidth">
										<?php
                                        $selected = '';
										if ($staff == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<tr id="yearGroupRow4">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include students?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="yearGroupsStudents" id="yearGroupsStudents" class="standardWidth">
										<?php
                                        $selected = '';
										if ($students == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<?php
                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_yearGroups_parents')) {
                                ?>
								<tr id="yearGroupRow2">
									<td class='hiddenReveal'>
										<b><?php echo __($guid, 'Include parents?') ?></b><br/>
									</td>
									<td class="hiddenReveal right">
										<select name="yearGroupsParents" id="yearGroupsParents" class="standardWidth">
											<?php
                                            $selected = '';
                                if ($parents == false) {
                                    $selected = 'selected';
                                }
                                echo "<option value='Y'>Yes</option>";
                                echo "<option $selected value='N'>No</option>";
                                ?>
										</select>
									</td>
								</tr>
								<?php

                            }
						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_rollGroups_my') or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_rollGroups_any')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='RolL Group'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* rollGroup Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#rollGroupRow").css("display","none");
										$("#rollGroupRow2").css("display","none");
										$("#rollGroupRow3").css("display","none");
										$("#rollGroupRow4").css("display","none");
									<?php } ?>
									$(".rollGroup").click(function(){
										if ($('input[name=rollGroup]:checked').val()=="Y" ) {
											$("#rollGroupRow").slideDown("fast", $("#rollGroupRow").css("display","table-row"));
											$("#rollGroupRow2").slideDown("fast", $("#rollGroupRow2").css("display","table-row"));
											$("#rollGroupRow3").slideDown("fast", $("#rollGroupRow3").css("display","table-row"));
											$("#rollGroupRow4").slideDown("fast", $("#rollGroupRow4").css("display","table-row"));
										} else {
											$("#rollGroupRow").css("display","none");
											$("#rollGroupRow2").css("display","none");
											$("#rollGroupRow3").css("display","none");
											$("#rollGroupRow4").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Roll Group') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Tutees and tutors.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="rollGroup" class="rollGroup" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="rollGroup" class="rollGroup" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							$staff = true;
							$students = true;
							$parents = true;
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 5, '0', STR_PAD_LEFT).',';
								if ($rowTarget['staff'] == 'N') {
									$staff = false;
								}
								if ($rowTarget['students'] == 'N') {
									$students = false;
								}
								if ($rowTarget['parents'] == 'N') {
									$parents = false;
								}
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="rollGroupRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Roll Groups') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="rollGroups[]" id="rollGroups[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_rollGroups_any')) {
                                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                $sqlSelect = 'SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                            } else {
                                                if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Staff') {
                                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                    $sqlSelect = 'SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonID1 OR gibbonPersonIDTutor2=:gibbonPersonID2 OR gibbonPersonIDTutor3=:gibbonPersonID3) AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                                }
                                                if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Student') {
                                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                    $sqlSelect = 'SELECT * FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                                }
                                            }
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonRollGroupID'], 5, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr id="rollGroupRow3">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include staff?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="rollGroupsStaff" id="rollGroupsStaff" class="standardWidth">
										<?php
                                        $selected = '';
										if ($staff == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<tr id="rollGroupRow4">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include student?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="rollGroupsStudents" id="rollGroupsStudents" class="standardWidth">
										<?php
                                        $selected = '';
										if ($students == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<?php
                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_rollGroups_parents')) {
                                ?>
								<tr id="rollGroupRow2">
									<td class='hiddenReveal'>
										<b><?php echo __($guid, 'Include parents?') ?></b><br/>
									</td>
									<td class="hiddenReveal right">
										<select name="rollGroupsParents" id="rollGroupsParents" class="standardWidth">
											<?php
                                            $selected = '';
											if ($parents == false) {
												$selected = 'selected';
											}
											echo "<option value='Y'>Yes</option>";
											echo "<option $selected value='N'>No</option>";
											?>
										</select>
									</td>
								</tr>
								<?php

                            }
						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_courses_my') or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_courses_any')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Course'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* course Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#courseRow").css("display","none");
										$("#courseRow2").css("display","none");
										$("#courseRow3").css("display","none");
										$("#courseRow4").css("display","none");
									<?php } ?>
									$(".course").click(function(){
										if ($('input[name=course]:checked').val()=="Y" ) {
											$("#courseRow").slideDown("fast", $("#courseRow").css("display","table-row"));
											$("#courseRow2").slideDown("fast", $("#courseRow2").css("display","table-row"));
											$("#courseRow3").slideDown("fast", $("#courseRow3").css("display","table-row"));
											$("#courseRow4").slideDown("fast", $("#courseRow4").css("display","table-row"));
										} else {
											$("#courseRow").css("display","none");
											$("#courseRow2").css("display","none");
											$("#courseRow3").css("display","none");
											$("#courseRow4").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Course') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Members of a course of study.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="course" class="course" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="course" class="course" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							$staff = true;
							$students = true;
							$parents = true;
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 8, '0', STR_PAD_LEFT).',';
								if ($rowTarget['staff'] == 'N') {
									$staff = false;
								}
								if ($rowTarget['students'] == 'N') {
									$students = false;
								}
								if ($rowTarget['parents'] == 'N') {
									$parents = false;
								}
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="courseRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Courses') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="courses[]" id="courses[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_courses_any')) {
                                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                $sqlSelect = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort';
                                            } else {
                                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlSelect = "SELECT gibbonCourse.* FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY name";
                                            }
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonCourseID'], 8, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonCourseID']."'>".htmlPrep($rowSelect['nameShort']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr id="courseRow3">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include staff?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="coursesStaff" id="coursesStaff" class="standardWidth">
										<?php
                                        $selected = '';
										if ($staff == true) {
											$selected = 'selected';
										}
										echo "<option value='N'>".__($guid, 'No').'</option>';
										echo "<option $selected value='Y'>Yes</option>"; ?>
									</select>
								</td>
							</tr>
							<tr id="courseRow4">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include students?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="coursesStudents" id="coursesStudents" class="standardWidth">
										<?php
                                        $selected = '';
										if ($students == true) {
											$selected = 'selected';
										}
										echo "<option value='N'>".__($guid, 'No').'</option>';
										echo "<option $selected value='Y'>Yes</option>"; ?>
									</select>
								</td>
							</tr>
							<?php
                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_courses_parents')) {
                                ?>
								<tr id="courseRow2">
									<td class='hiddenReveal'>
										<b><?php echo __($guid, 'Include parents?') ?></b><br/>
									</td>
									<td class="hiddenReveal right">
										<select name="coursesParents" id="coursesParents" class="standardWidth">
											<?php
                                            $selected = '';
											if ($parents == true) {
												$selected = 'selected';
											}
											echo "<option value='N'>".__($guid, 'No').'</option>';
											echo "<option $selected value='Y'>Yes</option>";
											?>
										</select>
									</td>
								</tr>
								<?php

                            }
						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_classes_my') or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_classes_any')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Class'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* class Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#classRow").css("display","none");
										$("#classRow2").css("display","none");
										$("#classRow3").css("display","none");
										$("#classRow4").css("display","none");
									<?php } ?>
									$(".class").click(function(){
										if ($('input[name=class]:checked').val()=="Y" ) {
											$("#classRow").slideDown("fast", $("#classRow").css("display","table-row"));
											$("#classRow2").slideDown("fast", $("#classRow2").css("display","table-row"));
											$("#classRow3").slideDown("fast", $("#classRow3").css("display","table-row"));
											$("#classRow4").slideDown("fast", $("#classRow4").css("display","table-row"));
										} else {
											$("#classRow").css("display","none");
											$("#classRow2").css("display","none");
											$("#classRow3").css("display","none");
											$("#classRow4").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Class') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Members of a class within a course.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="class" class="class" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="class" class="class" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							$staff = true;
							$students = true;
							$parents = true;
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 8, '0', STR_PAD_LEFT).',';
								if ($rowTarget['staff'] == 'N') {
									$staff = false;
								}
								if ($rowTarget['students'] == 'N') {
									$students = false;
								}
								if ($rowTarget['parents'] == 'N') {
									$parents = false;
								}
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="classRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Classes') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="classes[]" id="classes[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_classes_any')) {
                                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                                            } else {
                                                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY course, class";
                                            }
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonCourseClassID'], 8, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr id="classRow3">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include staff?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="classesStaff" id="classesStaff" class="standardWidth">
										<?php
                                        $selected = '';
										if ($staff == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<tr id="classRow4">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include students?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="classesStudents" id="classesStudents" class="standardWidth">
										<?php
                                        $selected = '';
										if ($students == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<?php
                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_classes_parents')) {
                                ?>
								<tr id="classRow2">
									<td class='hiddenReveal'>
										<b><?php echo __($guid, 'Include parents?') ?></b><br/>
									</td>
									<td class="hiddenReveal right">
										<select name="classesParents" id="classesParents" class="standardWidth">
											<?php
                                            $selected = '';
                                if ($parents == false) {
                                    $selected = 'selected';
                                }
                                echo "<option value='Y'>Yes</option>";
                                echo "<option $selected value='N'>No</option>";
                                ?>
										</select>
									</td>
								</tr>
								<?php

                            }
						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_activities_my') or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_activities_any')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Activity'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* activity Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#activitiesRow").css("display","none");
										$("#activitiesRow2").css("display","none");
										$("#activitiesRow3").css("display","none");
										$("#activitiesRow4").css("display","none");
									<?php } ?>
									$(".activity").click(function(){
										if ($('input[name=activity]:checked').val()=="Y" ) {
											$("#activitiesRow").slideDown("fast", $("#activitiesRow").css("display","table-row"));
											$("#activitiesRow2").slideDown("fast", $("#activitiesRow2").css("display","table-row"));
											$("#activitiesRow3").slideDown("fast", $("#activitiesRow3").css("display","table-row"));
											$("#activitiesRow4").slideDown("fast", $("#activitiesRow4").css("display","table-row"));
										} else {
											$("#activitiesRow").css("display","none");
											$("#activitiesRow2").css("display","none");
											$("#activitiesRow3").css("display","none");
											$("#activitiesRow4").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Activity') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Members of an activity.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="activity" class="activity" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="activity" class="activity" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							$staff = true;
							$students = true;
							$parents = true;
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 8, '0', STR_PAD_LEFT).',';
								if ($rowTarget['staff'] == 'N') {
									$staff = false;
								}
								if ($rowTarget['students'] == 'N') {
									$students = false;
								}
								if ($rowTarget['parents'] == 'N') {
									$parents = false;
								}
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="activitiesRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Activities') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="activities[]" id="activities[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_activities_any')) {
                                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                $sqlSelect = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                                            } else {
                                                if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Staff') {
                                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                    $sqlSelect = "SELECT * FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                                                }
                                                if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Student') {
                                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                    $sqlSelect = "SELECT * FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Accepted' AND active='Y' ORDER BY name";
                                                }
                                            }
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonActivityID'], 8, '0', STR_PAD_LEFT)))) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['gibbonActivityID']."'>".htmlPrep($rowSelect['name']).'</option>';
									}
									?>
									</select>
								</td>
							</tr>
							<tr id="activitiesRow3">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include staff?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="activitiesStaff" id="activitiesStaff" class="standardWidth">
										<?php
                                        $selected = '';
										if ($staff == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<tr id="activitiesRow4">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Include students?') ?></b><br/>
								</td>
								<td class="hiddenReveal right">
									<select name="activitiesStudents" id="activitiesStudents" class="standardWidth">
										<?php
                                        $selected = '';
										if ($students == false) {
											$selected = 'selected';
										}
										echo "<option value='Y'>Yes</option>";
										echo "<option $selected value='N'>No</option>"; ?>
									</select>
								</td>
							</tr>
							<?php
                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_activities_parents')) {
                                ?>
								<tr id="activitiesRow2">
									<td class='hiddenReveal'>
										<b><?php echo __($guid, 'Include parents?') ?></b><br/>
									</td>
									<td class="hiddenReveal right">
										<select name="activitiesParents" id="activitiesParents" class="standardWidth">
											<?php
                                            $selected = '';
                                if ($parents == false) {
                                    $selected = 'selected';
                                }
                                echo "<option value='Y'>Yes</option>";
                                echo "<option $selected value='N'>No</option>";
                                ?>
										</select>
									</td>
								</tr>
								<?php

                            }
						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_applicants')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Applicants'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#applicantsRow").css("display","none");
									<?php } ?>
									$(".applicants").click(function(){
										if ($('input[name=applicants]:checked').val()=="Y" ) {
											$("#applicantsRow").slideDown("fast", $("#applicantsRow").css("display","table-row"));
										} else {
											$("#applicantsRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Applicants') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Applicants from a given year.').'<br/>'.__($guid, 'Does not apply to the message wall.') ?></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="applicants" class="applicants" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="applicants" class="applicants" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 3, '0', STR_PAD_LEFT).',';
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="applicantsRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Years') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="applicantList[]" id="applicantList[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = 'SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber DESC';
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonSchoolYearID'], 3, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<?php

						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_houses_all') or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_houses_my')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Houses'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#housesRow").css("display","none");
									<?php } ?>
									$(".houses").click(function(){
										if ($('input[name=houses]:checked').val()=="Y" ) {
											$("#housesRow").slideDown("fast", $("#housesRow").css("display","table-row"));
										} else {
											$("#housesRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Houses') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Houses for competitions, etc.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="houses" class="houses" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="houses" class="houses" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 3, '0', STR_PAD_LEFT).',';
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="housesRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Houses') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="houseList[]" id="houseList[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_houses_all')) {
                                                $dataSelect = array();
                                                $sqlSelect = 'SELECT * FROM gibbonHouse ORDER BY name';
                                            } elseif (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_houses_my')) {
                                                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlSelect = 'SELECT gibbonHouse.gibbonHouseID, name FROM gibbonHouse JOIN gibbonPerson ON (gibbonHouse.gibbonHouseID=gibbonPerson.gibbonHouseID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name';
                                            }

                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonHouseID'], 3, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonHouseID']."'>".htmlPrep($rowSelect['name']).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<?php

						}
						if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_individuals')) {
							try {
								$dataTarget = array('gibbonMessengerID' => $gibbonMessengerID);
								$sqlTarget = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID AND type='Individuals'";
								$resultTarget = $connection2->prepare($sqlTarget);
								$resultTarget->execute($dataTarget);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							?>
							<script type="text/javascript">
								/* Role Control */
								$(document).ready(function(){
									<?php if ($resultTarget->rowCount() <= 0) { ?>
										$("#individualsRow").css("display","none");
									<?php } ?>
									$(".individuals").click(function(){
										if ($('input[name=individuals]:checked').val()=="Y" ) {
											$("#individualsRow").slideDown("fast", $("#individualsRow").css("display","table-row"));
										} else {
											$("#individualsRow").css("display","none");
										}
									 });
								});
							</script>
							<tr>
								<td>
									<b><?php echo __($guid, 'Indviduals') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Individuals from the whole school.') ?><br/></span>
								</td>
								<td class="right">
									<input <?php if ($resultTarget->rowCount() > 0) { echo 'checked'; } ?> type="radio" name="individuals" class="individuals" value="Y"/> <?php echo __($guid, 'Yes') ?>
									<input <?php if ($resultTarget->rowCount() <= 0) { echo 'checked'; } ?> type="radio" name="individuals" class="individuals" value="N"/> <?php echo __($guid, 'No') ?>
								</td>
							</tr>
							<?php
                            $selectedAll = '';
							while ($rowTarget = $resultTarget->fetch()) {
								$selectedAll .= str_pad($rowTarget['id'], 10, '0', STR_PAD_LEFT).',';
							}
							$selectedAll = substr($selectedAll, 0, -1); ?>
							<tr id="individualsRow">
								<td class='hiddenReveal'>
									<b><?php echo __($guid, 'Select Individuals') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
								</td>
								<td class="hiddenReveal right">
									<select name="individualList[]" id="individualList[]" multiple style="width: 302px; height: 100px">
										<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = "SELECT gibbonPersonID, preferredName, surname FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
										while ($rowSelect = $resultSelect->fetch()) {
											$selected = '';
											if (is_numeric(strpos($selectedAll, str_pad($rowSelect['gibbonPersonID'], 10, '0', STR_PAD_LEFT)))) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', $rowSelect['preferredName'], $rowSelect['surname'], 'Student', true).'</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<?php

							}
							?>

						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
							</td>
							<td class="right">
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
        }
    }
}
?>
