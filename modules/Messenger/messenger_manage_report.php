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

use Gibbon\Forms\Form;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
        $gibbonMessengerID = isset($_GET['gibbonMessengerID']) ? $_GET['gibbonMessengerID'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;

        $page->breadcrumbs
            ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
            ->add(__('View Send Report'));

		echo '<h2>';
		echo __('Report Data');
		echo '</h2>';

		$nonConfirm = 0;
		$noConfirm = 0;
		$yesConfirm = 0;

		
			$data = array('gibbonMessengerID' => $gibbonMessengerID);
			$sql = "SELECT gibbonMessenger.* FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID";
			$result = $connection2->prepare($sql);
			$result->execute($data);

		if ($result->rowCount() < 1) {
			echo "<div class='error'>";
			echo __('The specified record cannot be found.');
			echo '</div>';
		}
		else {
			$row = $result->fetch();

			if ($row['emailReceiptText'] != '') {
				echo '<p>';
				echo "<b>".__('Receipt Confirmation Text') . "</b>: ".$row['emailReceiptText'];
				echo '</p>';
			}
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

			if (isset($_GET['return'])) {
				returnProcess($guid, $_GET['return'], null, array('error2' => 'Some elements of your request failed, but others were successful.'));
			}

			// Create a reusable confirmation closure
			$icon = '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/%1$s"/>';
			$confirmationIndicator = function($recipient) use ($icon) {
				if (empty($recipient['key'])) return __('N/A');
				return sprintf($icon, $recipient['confirmed'] == 'Y'? 'iconTick.png' : 'iconCross.png');
			};

			$sender = false;
			if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID'] || $highestAction == 'Manage Messages_all') {
				$sender = true;
			}

			echo "<div id='tabs' style='margin: 20px 0'>";
				//Tab links
				echo '<ul>';
				echo "<li><a href='#tabs1'>".__('By Roll Group').'</a></li>';
				echo "<li><a href='#tabs2'>".__('By Recipient').'</a></li>';
				echo '</ul>';

				//Tab content
				echo "<div id='tabs1'>";
					
						$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
						$sql = "SELECT gibbonRollGroup.nameShort AS rollGroup, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFamilyChild.gibbonFamilyID, parent1.email AS parent1email, parent1.surname AS parent1surname, parent1.preferredName AS parent1preferredName, parent1.gibbonPersonID AS parent1gibbonPersonID, parent2.email AS parent2email, parent2.surname AS parent2surname, parent2.preferredName AS parent2preferredName, parent2.gibbonPersonID AS parent2gibbonPersonID
							FROM gibbonPerson
							JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
							JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
							LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
							LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND parent1Fam.contactPriority=1)
							LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full' AND NOT parent1.surname IS NULL)
							LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND parent2Fam.contactPriority=2 AND parent2Fam.contactEmail='Y')
							LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full' AND NOT parent2.surname IS NULL)
							WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
							AND gibbonPerson.status='Full'
							AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)
							GROUP BY gibbonPerson.gibbonPersonID
							ORDER BY rollGroup, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFamilyChild.gibbonFamilyID";
						$result = $connection2->prepare($sql);
						$result->execute($data);

					if ($result->rowCount() < 1) {
						echo "<div class='error'>";
						echo __('There are no records to display.');
						echo '</div>';
					} else {
						//Store receipt for this message data in an array
						
							$dataReceipts = array('gibbonMessengerID' => $gibbonMessengerID);
							$sqlReceipts = "SELECT gibbonPersonID, gibbonMessengerReceiptID, confirmed, `key`, gibbonPersonIDListStudent FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID";
							$resultReceipts = $connection2->prepare($sqlReceipts);
							$resultReceipts->execute($dataReceipts);
						$receipts = $resultReceipts->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

						$form = BulkActionForm::create('resendByRecipient', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/messenger_manage_report_processBulk.php?gibbonMessengerID='.$gibbonMessengerID.'&search='.$search);
						$form->addHiddenValue('address', $_SESSION[$guid]['address']);

						$row = $form->addBulkActionRow(array('resend' => __('Resend')))->addClass('flex justify-end');
							$row->addSubmit(__('Go'));

						$rollGroups = $result->fetchAll(\PDO::FETCH_GROUP);
						$countTotal = 0;

						// Merge gibbonPersonIDListStudent into $receipts as an array
                        $receipts = array_map(function ($item) {
                            $item['gibbonPersonIDListStudent'] = (empty($item['gibbonPersonIDListStudent'])) ? null : explode(',', $item['gibbonPersonIDListStudent']);
                            return $item;
                        }, $receipts);

						foreach ($rollGroups as $rollGroupName => $recipients) {
							$count = 0;

							// Filter the array for only those individuals involved in the message (student or parent)
							$recipients = array_filter($recipients, function($recipient) use (&$receipts) {
                                if (array_key_exists($recipient['gibbonPersonID'], $receipts)) {
                                    return true;
                                }

                                if (array_key_exists($recipient['parent1gibbonPersonID'], $receipts)
                                && (is_null($receipts[$recipient['parent1gibbonPersonID']]['gibbonPersonIDListStudent']) || in_array($recipient['gibbonPersonID'], $receipts[$recipient['parent1gibbonPersonID']]['gibbonPersonIDListStudent']))) {
                                        return true;
                                }

                                if (array_key_exists($recipient['parent2gibbonPersonID'], $receipts)
                                && (is_null($receipts[$recipient['parent2gibbonPersonID']]['gibbonPersonIDListStudent']) || in_array($recipient['gibbonPersonID'], $receipts[$recipient['parent2gibbonPersonID']]['gibbonPersonIDListStudent']))) {
                                        return true;
                                }

                                return false;
							});

							//print_r($recipients);exit;

							// Skip this roll group if there's no involved individuals
							if (empty($recipients)) continue;

							$form->addRow()->addHeading($rollGroupName);
							$table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

							$header = $table->addHeaderRow();
								$header->addContent(__('Total Count'));
								$header->addContent(__('Form Count'));
								$header->addContent(__('Student'))->addClass('mediumWidth');
								$header->addContent(__('Parent 1'))->addClass('mediumWidth');
								$header->addContent(__('Parent 2'))->addClass('mediumWidth');

							foreach ($recipients as $recipient) {
								// print_r($recipient);
								// echo "<br/><br/>";

								$countTotal++;
								$count++;

								$studentName = Format::name('', $recipient['preferredName'], $recipient['surname'], 'Student', true);
								$parent1Name = Format::name('', $recipient['parent1preferredName'], $recipient['parent1surname'], 'Parent', true);
								$parent2Name = Format::name('', $recipient['parent2preferredName'], $recipient['parent2surname'], 'Parent', true);

								//Tests for row completion, to set colour
								$studentReceived = isset($receipts[$recipient['gibbonPersonID']]);
								if ($studentReceived) {
									$studentComplete = ($receipts[$recipient['gibbonPersonID']]['confirmed'] == "Y");
								}
								else {
									$studentComplete = true;
								}
								$parentReceived = (isset($receipts[$recipient['parent1gibbonPersonID']]) || isset($receipts[$recipient['parent2gibbonPersonID']]));
								if ($parentReceived) {
									$parentComplete = ((isset($receipts[$recipient['parent1gibbonPersonID']]) && $receipts[$recipient['parent1gibbonPersonID']]['confirmed'] == "Y") || (isset($receipts[$recipient['parent2gibbonPersonID']]) && $receipts[$recipient['parent2gibbonPersonID']]['confirmed'] == "Y"));
								}
								else {
									$parentComplete = true;
								}
								$class = 'error';
								if ($studentComplete && $parentComplete) {
									$class = 'current';
								}

								$row = $table->addRow()->setClass($class);
									$row->addContent($countTotal);
									$row->addContent($count);

									$studentReceipt = isset($receipts[$recipient['gibbonPersonID']])? $receipts[$recipient['gibbonPersonID']] : null;
									$col = $row->addColumn();
										$col->addContent(!empty($studentName)? $studentName : __('N/A'));
										$col->addContent($confirmationIndicator($studentReceipt));
										$col->onlyIf($sender == true && !empty($studentReceipt) && $studentReceipt['confirmed'] == 'N')
											->addCheckbox('gibbonMessengerReceiptIDs[]')
											->setValue($studentReceipt['gibbonMessengerReceiptID'] ?? '')
                                            ->setClass('')
                                            ->alignLeft();

									$parent1Receipt = isset($receipts[$recipient['parent1gibbonPersonID']])? $receipts[$recipient['parent1gibbonPersonID']] : null;
									$col = $row->addColumn();
										$col->addContent(!empty($recipient['parent1surname'])? $parent1Name : __('N/A'));
										$col->addContent($confirmationIndicator($parent1Receipt));
										$col->onlyIf($sender == true && !empty($parent1Receipt) && $parent1Receipt['confirmed'] == 'N')
											->addCheckbox('gibbonMessengerReceiptIDs[]')
											->setValue($parent1Receipt['gibbonMessengerReceiptID'] ?? '')
                                            ->setClass('')
                                            ->alignLeft();

									$parent2Receipt = isset($receipts[$recipient['parent2gibbonPersonID']])? $receipts[$recipient['parent2gibbonPersonID']] : null;
									$col = $row->addColumn();
										$col->addContent(!empty($recipient['parent2surname'])? $parent2Name : __('N/A'));
										$col->addContent($confirmationIndicator($parent2Receipt));
										$col->onlyIf($sender == true && !empty($parent2Receipt) && $parent2Receipt['confirmed'] == 'N')
											->addCheckbox('gibbonMessengerReceiptIDs[]')
											->setValue($parent2Receipt['gibbonMessengerReceiptID'] ?? '')
                                            ->setClass('')
                                            ->alignLeft();
							}
						}

						if ($countTotal == 0) {
							$table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');
							$table->addRow()->addTableCell(__('There are no records to display.'))->colSpan(8);
						}

						echo $form->getOutput();
					}
				echo "</div>";
				echo "<div id='tabs2'>";
					if (!is_null($gibbonMessengerID)) {
						
							$data = array('gibbonMessengerID' => $gibbonMessengerID);
							$sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonMessenger.*, gibbonMessengerReceipt.*, gibbonRole.category as roleCategory
								FROM gibbonMessengerReceipt
								JOIN gibbonMessenger ON (gibbonMessengerReceipt.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
								LEFT JOIN gibbonPerson ON (gibbonMessengerReceipt.gibbonPersonID=gibbonPerson.gibbonPersonID)
								LEFT JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
								WHERE gibbonMessengerReceipt.gibbonMessengerID=:gibbonMessengerID ORDER BY FIELD(confirmed, 'Y','N',NULL), confirmedTimestamp, surname, preferredName, contactType";
							$result = $connection2->prepare($sql);
							$result->execute($data);

						$form = BulkActionForm::create('resendByRecipient', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/messenger_manage_report_processBulk.php?gibbonMessengerID='.$gibbonMessengerID.'&search='.$search);

						$form->addHiddenValue('address', $_SESSION[$guid]['address']);

						$row = $form->addBulkActionRow(array('resend' => __('Resend')))->addClass('flex justify-end');;
							$row->addSubmit(__('Go'));

						$table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

						$header = $table->addHeaderRow();
							$header->addContent();
							$header->addContent(__('Recipient'));
							$header->addContent(__('Role'));
							$header->addContent(__('Contact Type'));
							$header->addContent(__('Contact Detail'));
							$header->addContent(__('Receipt Confirmed'));
							$header->addContent(__('Timestamp'));
							if ($sender == true) {
								$header->addCheckAll();
							}


						$recipients = $result->fetchAll();
						$recipientIDs = array_column($recipients, 'gibbonPersonID');

						foreach ($recipients as $count => $recipient) {
							$row = $table->addRow();
								$row->addContent($count+1);
								$row->addContent(($recipient['preferredName'] != '' && $recipient['surname'] != '') ? Format::name('', $recipient['preferredName'], $recipient['surname'], 'Student', true) : __('N/A'));
								$row->addContent($recipient['roleCategory']);
								$row->addContent($recipient['contactType']);
								$row->addContent($recipient['contactDetail']);
								$row->addContent($confirmationIndicator($recipient));
								$row->addContent(dateConvertBack($guid, substr($recipient['confirmedTimestamp'],0,10)).' '.substr($recipient['confirmedTimestamp'],11,5));

								if ($sender == true) {
									$row->onlyIf($recipient['confirmed'] == 'N')
										->addCheckbox('gibbonMessengerReceiptIDs[]')
										->setValue($recipient['gibbonMessengerReceiptID'])
										->setClass('textCenter');

									$row->onlyIf($recipient['confirmed'] != 'N')->addContent();
								}

							if (is_null($recipient['key'])) $nonConfirm++;
							else if ($recipient['confirmed'] == 'Y') $yesConfirm++;
							else if ($recipient['confirmed'] == 'N') $noConfirm++;
						}

						if (count($recipients) == 0) {
							$table->addRow()->addTableCell(__('There are no records to display.'))->colSpan(8);
						} else {
							$sendReport = '<b>'.__('Total Messages:')." ".count($recipients)."</b><br/>";
							$sendReport .= "<span>".__('Messages not eligible for confirmation of receipt:')." <b>$nonConfirm</b><br/>";
							$sendReport .= "<span>".__('Messages confirmed:').' <b>'.$yesConfirm.'</b><br/>';
							$sendReport .= "<span>".__('Messages not yet confirmed:').' <b>'.$noConfirm.'</b><br/>';

							$form->addRow()->addClass('right')->addAlert($sendReport, 'success');
						}

						echo $form->getOutput();
					}
				echo "</div>";
			}
		echo "</div>";
	}
}
