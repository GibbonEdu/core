<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Messenger\MessengerGateway;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
    //Acess denied
    $page->addError(__("You do not have access to this action."));
}
else {
    //Get action with highest precendence
    $highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
    if ($highestAction==FALSE) {
        $page->addError(__("The highest grouped action cannot be determined."));
    }
    else {
        $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? null;
        $search = $_GET['search'] ?? null;
        $confirmationMode = $_GET['confirmationMode'] ?? 'One';

        $page->breadcrumbs
            ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
            ->add(__('View Send Report'));

        $nonConfirm = 0;
        $noConfirm = 0;
        $yesConfirm = 0;


        $data = array('gibbonMessengerID' => $gibbonMessengerID);
        $sql = "SELECT gibbonMessenger.* FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() < 1) {
            $page->addError(__('The specified record cannot be found.'));
        }
        else {
            $values = $result->fetch();
            
            ?>

            <script type='text/javascript'>
                $(function() {
                    $( "#tabs" ).tabs({
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

            $page->return->addReturns(['error2' => 'Some elements of your request failed, but others were successful.']);

            // Create a reusable confirmation closure
            $icon = '<img src="./themes/'.$session->get('gibbonThemeName').'/img/%1$s"/>';
            $confirmationIndicator = function($recipient, $emailReceipt = false) use ($icon) {
                if ($emailReceipt == 'N') return '';
                if (empty($recipient['key'])) return __('N/A');
                return sprintf($icon, $recipient['confirmed'] == 'Y'? 'iconTick.png' : 'iconCross.png').' '.Format::small(Format::yesNo($recipient['confirmed']));
            };

            $sender = false;
            if ($values['gibbonPersonID'] == $session->get('gibbonPersonID') || $highestAction == 'Manage Messages_all')  {
                $sender = true;
            }

            if ($highestAction != 'Manage Messages_all' && $values['gibbonPersonID'] != $session->get('gibbonPersonID') && $values['enableSharingLink'] == 'N') {
                $page->addError(__("You do not have access to this action."));
                return;
            }

            if ($sender && $values['email'] == 'Y' && $values['emailReceipt'] == 'Y') {
                $alertText = __('Email read receipts have been enabled for this message. You can use the Resend action along with the checkboxes next to recipients who have not yet confirmed to send a reminder to these users.').' '.__('Recipients who may not have received the original email due to a delivery issue are highlighted in orange.');

                if (!empty($values['emailReceiptText'])) {
                    $alertText .= '<br/><br/><b>'.__('Receipt Confirmation Text') . '</b>: '.$values['emailReceiptText'];
                }

                echo Format::alert($alertText, 'success');
            } elseif ($sender && $values['email'] == 'Y' && $values['emailReceipt'] == 'N') {
                echo Format::alert(__('Email read receipts have not been enabled for this message, however you can still use the Resend action to manually send messages.').' '.__('Recipients who may not have received the original email due to a delivery issue are highlighted in orange.'), 'message');
            }

            echo '<h2>';
            echo __('Report Data');
            echo '</h2>';

            // CONFIRMATION MODE
            if ($values['email'] == 'Y' && $values['emailReceipt'] == 'Y') {
                // Determine the target categories that are active
                $messengerGateway = $container->get(MessengerGateway::class);
                $targets = $messengerGateway->selectMessageTargetsByID($gibbonMessengerID)->fetchAll();

                $parents = $students = $staff = false;

                foreach ($targets as $target) {
                    $parents = $target['parents'] == 'Y' ? true : $parents;
                    $students = $target['students'] == 'Y' ? true : $students;
                    $staff = $target['staff'] == 'Y' ? true : $staff;
                }

                // Auto-submitting form to select the confirmation mode
                $form = Form::create('filters', $session->get('absoluteURL') . '/index.php', 'get');

                $form->addHiddenValue('q', '/modules/Messenger/messenger_manage_report.php');
                $form->addHiddenValue('gibbonMessengerID', $gibbonMessengerID);
                $form->addHiddenValue('search', $search);
                $form->addHiddenValue('sidebar', 'true');

                $form->setClass('noIntBorder fullWidth auto-submit pb-1');

                $row = $form->addRow();
                    $row->addLabel('subjectLabel', __('Message'));
                    $row->addTextField('subject')->readonly()->setValue($values['subject']);

                $confirmationOptions = [];
                if ($parents) {
                    $confirmationOptions['One'] = __('At Least One Parent');
                    $confirmationOptions['Both'] = __('Both Parents');
                }
                if ($parents && $students) {
                    $confirmationOptions['All'] = __('Student and Parent');
                }
                $confirmationOptions['Any'] = __('Any Recipient');

                $row = $form->addRow();
                    $row->addLabel('confirmationMode', __('Confirmation Required By'));
                    $row->addSelect('confirmationMode')->fromArray($confirmationOptions)->selected($confirmationMode);

                if ($values['enableSharingLink'] == 'Y'  && $values['gibbonPersonID'] == $session->get('gibbonPersonID')) {
                    $linkURL = Url::fromModuleRoute('Messenger', 'messenger_manage_report')->withQueryParams(['gibbonMessengerID' => $gibbonMessengerID])->withAbsoluteUrl(true);
                    $row = $form->addRow();
                        $row->addLabel('sharingLink', __('Shareable Send Report'))->description(__('You can copy this link to share it with other users.'));
                        $row->addTextField('sharingLink')->setValue(urldecode($linkURL));
                    }

                echo $form->getOutput();
            }

            echo "<div id='tabs' style='margin: 20px 0'>";
                //Tab links
                echo '<ul>';
                echo "<li><a href='#tabs1'>".__('By Form Group').'</a></li>';
                echo "<li><a href='#tabs2'>".__('By Recipient').'</a></li>';
                echo '</ul>';

                //Tab content
                echo "<div id='tabs1'>";

                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
                        $sql = "SELECT gibbonFormGroup.nameShort AS formGroup, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFamilyChild.gibbonFamilyID, parent1.email AS parent1email, parent1.surname AS parent1surname, parent1.preferredName AS parent1preferredName, parent1.gibbonPersonID AS parent1gibbonPersonID, parent2.email AS parent2email, parent2.surname AS parent2surname, parent2.preferredName AS parent2preferredName, parent2.gibbonPersonID AS parent2gibbonPersonID
                            FROM gibbonPerson
                            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                            LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND parent1Fam.contactPriority=1)
                            LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full' AND NOT parent1.surname IS NULL)
                            LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND parent2Fam.contactPriority=2 AND parent2Fam.contactEmail='Y')
                            LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full' AND NOT parent2.surname IS NULL)
                            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonPerson.status='Full'
                            AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)
                            GROUP BY gibbonPerson.gibbonPersonID
                            ORDER BY formGroup, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFamilyChild.gibbonFamilyID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);

                    if ($result->rowCount() < 1) {
                        echo $page->getBlankSlate();
                    } else {
                        //Store receipt for this message data in an array

                            $dataReceipts = array('gibbonMessengerID' => $gibbonMessengerID);
                            $sqlReceipts = "SELECT gibbonPersonID, gibbonMessengerReceiptID, confirmed, sent, `key`, gibbonPersonIDListStudent FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID";
                            $resultReceipts = $connection2->prepare($sqlReceipts);
                            $resultReceipts->execute($dataReceipts);
                        $receipts = $resultReceipts->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

                        $form = BulkActionForm::create('resendByRecipient', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/messenger_manage_report_processBulk.php?gibbonMessengerID='.$gibbonMessengerID.'&search='.$search);
                        $form->addHiddenValue('address', $session->get('address'));

                        if ($sender) {
                            $row = $form->addBulkActionRow(array('resend' => __('Resend')))->addClass('flex justify-end');
                            $row->addSubmit(__('Go'));
                        }

                        $formGroups = $result->fetchAll(\PDO::FETCH_GROUP);
                        $countTotal = 0;

                        // Merge gibbonPersonIDListStudent into $receipts as an array
                        $receipts = array_map(function ($item) {
                            $item['gibbonPersonIDListStudent'] = (empty($item['gibbonPersonIDListStudent'])) ? null : explode(',', $item['gibbonPersonIDListStudent']);
                            return $item;
                        }, $receipts);

                        foreach ($formGroups as $formGroupName => $recipients) {
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

                            // Skip this form group if there's no involved individuals
                            if (empty($recipients)) continue;

                            $form->addRow()->addHeading($formGroupName);
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
                                } else {
                                    $studentComplete = false;
                                }

                                $parentReceived = (isset($receipts[$recipient['parent1gibbonPersonID']]) || isset($receipts[$recipient['parent2gibbonPersonID']]));
                                if ($parentReceived) {
                                    $parentComplete = ((isset($receipts[$recipient['parent1gibbonPersonID']]) && $receipts[$recipient['parent1gibbonPersonID']]['confirmed'] == "Y") || (isset($receipts[$recipient['parent2gibbonPersonID']]) && $receipts[$recipient['parent2gibbonPersonID']]['confirmed'] == "Y"));
                                    $bothParentsComplete = ((isset($receipts[$recipient['parent1gibbonPersonID']]) && $receipts[$recipient['parent1gibbonPersonID']]['confirmed'] == "Y") && (!isset($receipts[$recipient['parent2gibbonPersonID']]) || $receipts[$recipient['parent2gibbonPersonID']]['confirmed'] == "Y"));
                                }
                                else {
                                    $parentComplete = false;
                                    $bothParentsComplete = false;
                                }
                                $class = $values['emailReceipt'] == 'Y' ? 'error' : '';
                                
                                if ($confirmationMode == 'All' && $studentComplete && $parentComplete) {
                                    $class = 'current';
                                } elseif ($confirmationMode == 'One' && $parentComplete) {
                                    $class = 'current';
                                } elseif ($confirmationMode == 'Both' && $bothParentsComplete) {
                                    $class = 'current';
                                } elseif ($confirmationMode == 'Any' && ($studentComplete || $parentComplete)) {
                                    $class = 'current';
                                }

                                $row = $table->addRow()->setClass($class);
                                    $row->addContent($countTotal);
                                    $row->addContent($count);

                                    $studentReceipt = isset($receipts[$recipient['gibbonPersonID']])? $receipts[$recipient['gibbonPersonID']] : null;
                                    $col = $row->addColumn()->addClass(!empty($studentReceipt) && $studentReceipt['confirmed'] != 'Y' && $studentReceipt['sent'] != 'Y' ? 'bg-orange-300' : '');
                                        $col->addContent(!empty($studentName)? $studentName : __('N/A'));
                                        $col->addContent($confirmationIndicator($studentReceipt, $values['emailReceipt']));
                                        $col->onlyIf($sender == true && !empty($studentReceipt) && ($studentReceipt['confirmed'] == 'N' || $values['emailReceipt'] == 'N'))
                                            ->addCheckbox('gibbonMessengerReceiptIDs[]')
                                            ->setValue($studentReceipt['gibbonMessengerReceiptID'] ?? '')
                                            ->setClass('')
                                            ->alignLeft();

                                    $parent1Receipt = isset($receipts[$recipient['parent1gibbonPersonID']])? $receipts[$recipient['parent1gibbonPersonID']] : null;
                                    $col = $row->addColumn()->addClass(!empty($parent1Receipt) && $parent1Receipt['confirmed'] != 'Y' && $parent1Receipt['sent'] != 'Y' ? 'bg-orange-300' : '');
                                        $col->addContent(!empty($recipient['parent1surname'])? $parent1Name : __('N/A'));
                                        $col->addContent($confirmationIndicator($parent1Receipt, $values['emailReceipt']));
                                        $col->onlyIf($sender == true && !empty($parent1Receipt) && ($parent1Receipt['confirmed'] == 'N' || $values['emailReceipt'] == 'N'))
                                            ->addCheckbox('gibbonMessengerReceiptIDs[]')
                                            ->setValue($parent1Receipt['gibbonMessengerReceiptID'] ?? '')
                                            ->setClass('')
                                            ->alignLeft();

                                    $parent2Receipt = isset($receipts[$recipient['parent2gibbonPersonID']])? $receipts[$recipient['parent2gibbonPersonID']] : null;
                                    $col = $row->addColumn()->addClass(!empty($parent2Receipt) && $parent2Receipt['confirmed'] != 'Y' && $parent2Receipt['sent'] != 'Y' ? 'bg-orange-300' : '');
                                        $col->addContent(!empty($recipient['parent2surname'])? $parent2Name : __('N/A'));
                                        $col->addContent($confirmationIndicator($parent2Receipt, $values['emailReceipt']));
                                        $col->onlyIf($sender == true && !empty($parent2Receipt) && ($parent2Receipt['confirmed'] == 'N' || $values['emailReceipt'] == 'N'))
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

                        $form = BulkActionForm::create('resendByRecipient', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/messenger_manage_report_processBulk.php?gibbonMessengerID='.$gibbonMessengerID.'&search='.$search);

                        $form->addHiddenValue('address', $session->get('address'));

                        if ($sender) {
                            $row = $form->addBulkActionRow(array('resend' => __('Resend')))->addClass('flex justify-end');;
                            $row->addSubmit(__('Go'));
                        }

                        $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                        $header = $table->addHeaderRow();
                            $header->addContent();
                            $header->addContent(__('Recipient'));
                            $header->addContent(__('Role'));
                            $header->addContent(__('Contact Type'));
                            $header->addContent(__('Contact Detail'));
                            $header->addContent(__('Sent'));
                            $header->addContent(__('Receipt Confirmed'));
                            $header->addContent(__('Timestamp'));
                            if ($sender == true) {
                                $header->addCheckAll();
                            }


                        $recipients = $result->fetchAll();
                        $recipientIDs = array_column($recipients, 'gibbonPersonID');

                        foreach ($recipients as $count => $recipient) {
                            $row = $table->addRow()->addClass($recipient['confirmed'] != 'Y' && $recipient['sent'] != 'Y' ? 'warning' : '');
                                $row->addContent($count+1);
                                $row->addContent(($recipient['preferredName'] != '' && $recipient['surname'] != '') ? Format::name('', $recipient['preferredName'], $recipient['surname'], 'Student', true) : __('N/A'));
                                $row->addContent($recipient['roleCategory']);
                                $row->addContent($recipient['contactType']);
                                $row->addContent($recipient['contactDetail']);
                                $row->addContent(Format::yesNo($recipient['sent']));
                                $row->addContent($confirmationIndicator($recipient));
                                $row->addContent(!empty($recipient['confirmedTimestamp']) ? Format::date(substr($recipient['confirmedTimestamp'],0,10)).' '.substr($recipient['confirmedTimestamp'],11,5) : '');

                                if ($sender == true && $recipient['contactType'] == 'Email') {
                                    $row->onlyIf($recipient['confirmed'] == 'N' || $values['emailReceipt'] == 'N')
                                        ->addCheckbox('gibbonMessengerReceiptIDs[]')
                                        ->setValue($recipient['gibbonMessengerReceiptID'])
                                        ->addClass('bulkCheckbox')
                                        ->alignCenter();

                                    $row->onlyIf($recipient['confirmed'] != 'N' && $values['emailReceipt'] == 'Y')->addContent();
                                } else {
                                    $row->addContent();
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
