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

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
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
		$gibbonMessengerID=NULL ;
		if (isset($_GET["gibbonMessengerID"])) {
			$gibbonMessengerID=$_GET["gibbonMessengerID"] ;
		}
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}

		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/messenger_manage.php&search=$search'>" . __($guid, 'Manage Messages') . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Send Report') . "</div>" ;
		print "</div>" ;
		?>

		<script type='text/javascript'>
			$(function() {
				$( "#tabs" ).tabs({
					create: function( event, ui ) {
						action1.enable();
						action2.disable();
					},
					activate: function( event, ui ) {
						if (ui.newPanel.attr('id') == 'tabs1') {
							action1.enable();
							action2.disable();
						}
						else if (ui.newPanel.attr('id') == 'tabs2') {
							action1.disable();
							action2.enable();
						}
					},
					ajaxOptions: {
						error: function( xhr, status, index, anchor ) {
							$( anchor.hash ).html(
								"Couldn't load this tab." );
						}
					}
				});
			});
		</script>
		<?php

		echo "<div id='tabs' style='margin: 20px 0'>";
			//Tab links
			echo '<ul>';
			echo "<li><a href='#tabs1'>".__($guid, 'By Recipient').'</a></li>';
			echo "<li><a href='#tabs2'>".__($guid, 'By Roll Group').'</a></li>';
			echo '</ul>';

			//Tab content
			echo "<div id='tabs1'>";
				if (isset($_GET['return'])) {
			        returnProcess($guid, $_GET['return'], null, array('error2' => 'Some elements of your request failed, but others were successful.'));
			    }

				$nonConfirm = 0;
				$noConfirm = 0;
				$yesConfirm = 0;

				if (!is_null($gibbonMessengerID)) {
			        echo '<h2>';
			        echo __($guid, 'Report Data');
			        echo '</h2>';

			        try {
			            $data = array('gibbonMessengerID' => $gibbonMessengerID);
			            $sql = "SELECT gibbonMessenger.* FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID";
			            $result = $connection2->prepare($sql);
			            $result->execute($data);
			        } catch (PDOException $e) {
			            echo "<div class='error'>".$e->getMessage().'</div>';
			        }

					if ($result->rowCount() < 1) {
						echo "<div class='error'>";
			            echo __($guid, 'The specified record cannot be found.');
			            echo '</div>';
					}
					else {
						$row = $result->fetch();

						$sender = false;
						if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID'] || $highestAction == 'Manage Messages_all') {
							$sender = true;
						}

						if ($row['emailReceiptText'] != '') {
							echo '<p>';
					        echo "<b>".__($guid, 'Receipt Confirmation Text') . "</b>: ".$row['emailReceiptText'];
					        echo '</p>';
						}

						try {
				            $data = array('gibbonMessengerID' => $gibbonMessengerID);
				            $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonMessenger.*, gibbonMessengerReceipt.* FROM gibbonMessengerReceipt LEFT JOIN gibbonPerson ON (gibbonMessengerReceipt.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonMessenger ON (gibbonMessengerReceipt.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) WHERE gibbonMessengerReceipt.gibbonMessengerID=:gibbonMessengerID ORDER BY FIELD(confirmed, 'Y','N',NULL), confirmedTimestamp, surname, preferredName, contactType";
				            $result = $connection2->prepare($sql);
				            $result->execute($data);
				        } catch (PDOException $e) {
				            echo "<div class='error'>".$e->getMessage().'</div>';
				        }

						echo "<form onsubmit='return confirm(\"".__($guid, 'Are you sure you wish to process this action? It cannot be undone.')."\")' method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/messenger_manage_report_processBulk.php?gibbonMessengerID=$gibbonMessengerID&search=$search'>";
						echo "<fieldset style='border: none'>";
						if ($sender == true) {
							echo "<div class='linkTop' style='text-align: right; margin-bottom: 40px'>";
							?>
							<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
							<select name="action1" id="action1" style='width:120px; float: right; margin-right: 1px;'>
								<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
								<option value="resend"><?php echo __($guid, 'Resend') ?></option>
							</select>
							<script type="text/javascript">
								var action1=new LiveValidation('action1');
								action1.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
							<?php
							echo '</div>';
						}

						echo "<table cellspacing='0' style='width: 100%'>";
				        echo "<tr class='head'>";
				        echo '<th>';

				        echo '</th>';
				        echo '<th>';
				        echo __($guid, 'Recipient');
				        echo '</th>';
				        echo '<th>';
				        echo __($guid, 'Contact Type');
				        echo '</th>';
				        echo '<th>';
				        echo __($guid, 'Contact Detail');
				        echo '</th>';
				        echo '<th>';
				        echo __($guid, 'Receipt Confirmed');
				        echo '</th>';
				        echo '<th>';
				        echo __($guid, 'Timestamp');
				        echo '</th>';
						if ($sender == true) {
							echo '<th style=\'text-align: center\'>';
							?>
								<script type="text/javascript">
									$(function () {
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?php
								echo "<input type='checkbox' class='checkall'>";
					        echo '</th>';
						}
				        echo '</tr>';

				        $count = 0;
				        $rowNum = 'odd';
				        while ($row = $result->fetch()) {
			                if ($count % 2 == 0) {
			                    $rowNum = 'even';
			                } else {
			                    $rowNum = 'odd';
			                }
			                ++$count;

			                //COLOR ROW BY STATUS!
			                echo "<tr class=$rowNum>";
			                echo '<td>';
			                echo $count;
			                echo '</td>';
			                echo '<td>';
							if ($row['preferredName'] == '' or $row['surname'] == '')
								echo __($guid, 'N/A');
							else
			                	echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
			                echo '</td>';
			                echo '<td>';
			                echo $row['contactType'];
			                echo '</td>';
			                echo '<td>';
			                echo $row['contactDetail'];
			                echo '</td>';
			                echo '<td>';
			                if (is_null($row['key'])) {
								echo __($guid, 'N/A');
								$nonConfirm ++;
							}
							else {
								if ($row['confirmed'] == 'Y') {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
									$yesConfirm ++;
								}
								else {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
									$noConfirm ++;
								}
							}

			                echo '</td>';
							echo '<td>';
							echo dateConvertBack($guid, substr($row['confirmedTimestamp'],0,10))." ".substr($row['confirmedTimestamp'],11,5)."<br/>" ;
			                echo '</td>';
			                if ($sender == true) {
								echo '<td style=\'text-align: center\'>';
								if ($row['confirmed'] == 'N') {
									echo "<input type='checkbox' name='gibbonMessengerReceiptIDs[]' value='".$row['gibbonMessengerReceiptID']."'>";
								}
								echo '</td>';
							}
			                echo '</tr>';
			            }
				        if ($count < 1) {
				            echo "<tr class=$rowNum>";
							if ($sender == true)
								echo '<td colspan=7>';
							else
								echo '<td colspan=6>';
				            echo __($guid, 'There are no records to display.');
				            echo '</td>';
				            echo '</tr>';
				        }
						else {
							echo '<tr>';
							if ($sender == true)
								echo "<td class='right' colspan=7>";
							else
								echo "<td class='right' colspan=6>";
							echo "<div class='success'>";
							echo '<b>'.__($guid, 'Total Messages:')." $count</b><br/>";
							echo "<span>".__($guid, 'Messages not eligible for confirmation of receipt:')." <b>$nonConfirm</b><br/>";
							echo "<span>".__($guid, 'Messages confirmed:').' <b>'.$yesConfirm.'</b><br/>';
							echo "<span>".__($guid, 'Messages not yet confirmed:').' <b>'.$noConfirm.'</b><br/>';
							echo '</div>';
							echo '</td>';
							echo '</tr>';
						}
						echo '</fieldset>';
				        echo '</table>';
					}
				}
			echo "</div>";
			echo "<div id='tabs2'>";
				try {
					$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
					$sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonFamily.gibbonFamilyID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' ORDER BY rollGroup, surname, preferredName";
					$result = $connection2->prepare($sql);
					$result->execute($data);
				} catch (PDOException $e) {
					echo "<div class='error'>".$e->getMessage().'</div>';
				}
				if ($result->rowCount() < 1) {
					echo "<div class='error'>";
					echo __($guid, 'There are no records to display.');
					echo '</div>';
				} else {
					//Store receipt for this message data in an array
					try {
						$dataReceipts = array('gibbonMessengerID' => $gibbonMessengerID);
						$sqlReceipts = "SELECT * FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID";
						$resultReceipts = $connection2->prepare($sqlReceipts);
						$resultReceipts->execute($dataReceipts);
					} catch (PDOException $e) {}
					$receipts = $resultReceipts->fetchAll();

					echo "<form onsubmit='return confirm(\"".__($guid, 'Are you sure you wish to process this action? It cannot be undone.')."\")' method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/messenger_manage_report_processBulk.php?gibbonMessengerID=$gibbonMessengerID&search=$search'>";
					echo "<fieldset style='border: none'>";
					if ($sender == true) {
						echo "<div class='linkTop' style='text-align: right; margin-bottom: 40px'>";
						?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
						<select name="action2" id="action2" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
							<option value="resend"><?php echo __($guid, 'Resend') ?></option>
						</select>
						<script type="text/javascript">
							var action2=new LiveValidation('action2');
							action2.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
						<?php
						echo '</div>';
					}

					$currentRollGroup = '';
					$lastRollGroup = '';
					$count = 0;
					$countTotal = 0;
					$rowNum = 'odd';
					while ($row = $result->fetch()) {
						$currentRollGroup = $row['rollGroup'];

						//SPLIT INTO ROLL GROUPS
						if ($currentRollGroup != $lastRollGroup) {
							if ($lastRollGroup != '') {
								echo '</table>';
							}
							echo '<h2>'.$row['rollGroup'].'</h2>';
							$count = 0;
							$rowNum = 'odd';
							echo "<table cellspacing='0' style='width: 100%'>";
							echo "<tr class='head'>";
							echo '<th>';
							echo __($guid, 'Total Count');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Form Count');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Student');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Parent 1');
							echo '</th>';
							echo '<th>';
							echo __($guid, 'Parent 2');
							echo '</th>';
							echo '</tr>';
						}
						$lastRollGroup = $row['rollGroup'];

						//PUMP OUT STUDENT DATA
						//Check for older siblings
						try {
							$dataParent = array('gibbonPersonID' => $row['gibbonPersonID']);
							$sqlParent = "SELECT parent1.email AS parent1email, parent1.surname AS parent1surname, parent1.preferredName AS parent1preferredName, parent1.gibbonPersonID AS parent1gibbonPersonID, parent2.email AS parent2email, parent2.surname AS parent2surname, parent2.preferredName AS parent2preferredName, parent2.gibbonPersonID AS parent2gibbonPersonID
							FROM gibbonFamilyChild
							LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
							LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent1Fam.contactPriority=1)
							LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full' AND NOT parent1.surname IS NULL)
							LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent2Fam.contactPriority=2 AND parent2Fam.contactEmail='Y')
							LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full' AND NOT parent2.surname IS NULL)
							WHERE gibbonFamilyChild.gibbonPersonID=:gibbonPersonID
							ORDER BY gibbonFamilyChild.gibbonFamilyID
							LIMIT 0, 1";
							$resultParent = $connection2->prepare($sqlParent);
							$resultParent->execute($dataParent);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}

						if ($count % 2 == 0) {
							$rowNum = 'even';
						} else {
							$rowNum = 'odd';
						}
						echo "<tr class=$rowNum>";
						echo "<td style='width: 8%'>";
						echo $countTotal + 1;
						echo '</td>';
						echo "<td style='width: 8%'>";
						echo $count + 1;
						echo '</td>';
						echo "<td style='width: 29%'>";
						echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
						echo '</td>';
						if ($resultParent->rowCount() != 1) {
							echo "<td style='width: 27%'>";
								echo __($guid, 'N/A');
							echo '</td>';
							echo "<td style='width: 27%'>";
								echo __($guid, 'N/A');
							echo '</td>';
						} else {
							$rowParent = $resultParent->fetch();
							echo "<td style='width: 27%'>";
								$confirmed = null;
								$gibbonMessengerReceiptID = null;
								foreach ($receipts as $receipt) {
									if ($receipt['gibbonPersonID'] == $rowParent['parent1gibbonPersonID']) {
										if ($receipt['confirmed'] == 'N') {
											$confirmed = 'N';
											$gibbonMessengerReceiptID = $receipt['gibbonMessengerReceiptID'];
										}
										if ($receipt['confirmed'] == 'Y') {
											$confirmed = 'Y';
										}
									}
								}
								if ($rowParent['parent1preferredName'] != '' and $rowParent['parent1surname'] != '') {
									echo formatName('', $rowParent['parent1preferredName'], $rowParent['parent1surname'], 'Student', true)."<br/>";
								}
								if (is_null($confirmed)) {
									echo __($guid, 'N/A');
								}
								else if ($confirmed == 'N') {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/><br/>";
									if ($sender == true) {
										echo "<input type='checkbox' name='gibbonMessengerReceiptIDs[]' value='".$gibbonMessengerReceiptID."'>";
									}
								}
								else if ($confirmed == 'Y') {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
								}
							echo '</td>';
							echo "<td style='width: 27%'>";
								$confirmed = null;
								$gibbonMessengerReceiptID = null;
								foreach ($receipts as $receipt) {
									if ($receipt['gibbonPersonID'] == $rowParent['parent2gibbonPersonID']) {
										if ($receipt['confirmed'] == 'N') {
											$confirmed = 'N';
											$gibbonMessengerReceiptID = $receipt['gibbonMessengerReceiptID'];
										}
										if ($receipt['confirmed'] == 'Y') {
											$confirmed = 'Y';
										}
									}
								}
								if ($rowParent['parent2preferredName'] != '' and $rowParent['parent2surname'] !='') {
									echo formatName('', $rowParent['parent2preferredName'], $rowParent['parent2surname'], 'Student', true)."<br/>";
								}
								if (is_null($confirmed)) {
									echo __($guid, 'N/A');
								}
								else if ($confirmed == 'N') {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/><br/>";
									if ($sender == true) {
										echo "<input type='checkbox' name='gibbonMessengerReceiptIDs[]' value='".$gibbonMessengerReceiptID."'>";
									}
								}
								else if ($confirmed == 'Y') {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
								}
							echo '</td>';
						}
						++$count;
						++$countTotal;
					}
					echo '</table>';
				}
			echo "</div>";
		echo "</div>";
	}
}
?>
