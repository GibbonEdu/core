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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs->add(__('Update Medical Data'));

        if ($highestAction == 'Update Medical Data_any') {
            echo '<p>';
            echo __('This page allows a user to request selected medical data updates for any student.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __('This page allows any adult with data access permission to request medical data updates for any member of their family.');
            echo '</p>';
        }

        $customResponces = array();

        $success0 = __('Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
            $success0 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
        }
        $customResponces['success0'] = $success0;

        $page->return->addReturns($customResponces);

        echo '<h2>';
        echo __('Choose User');
        echo '</h2>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
		}

		$gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

		$form = Form::create('selectFamily', $session->get('absoluteURL').'/index.php', 'get');
		$form->addHiddenValue('q', '/modules/'.$session->get('module').'/data_medical.php');

		if ($highestAction == 'Update Medical Data_any') {
			$data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, username, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName";
		} else {
			$data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamily.name as familyName, child.surname, child.preferredName, child.gibbonPersonID
					FROM gibbonFamilyAdult
					JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
					LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
					LEFT JOIN gibbonPerson AS child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID)
					WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
					AND gibbonFamilyAdult.childDataAccess='Y' AND child.status='Full'
					ORDER BY gibbonFamily.name, child.surname, child.preferredName";
		}

		$result = $pdo->executeQuery($data, $sql);
		$resultSet = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
		$people = array_reduce($resultSet, function($carry, $person) use ($highestAction) {
			$value = $person['gibbonPersonID'];
			$carry[$value] = Format::name('', htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Student', true);
			if ($highestAction == 'Update Medical Data_any') {
				$carry[$value] .= ' ('.$person['username'].')';
			}
			return $carry;
		}, array());

		$row = $form->addRow();
			$row->addLabel('gibbonPersonID', __('Person'));
			$row->addSelect('gibbonPersonID')
                ->fromArray($people)
                ->required()
                ->selected($gibbonPersonID)
				->placeholder();

		$row = $form->addRow();
            $row->addSubmit();

		echo $form->getOutput();


        if ($gibbonPersonID != '') {
            echo '<h2>';
            echo __('Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Medical Data_any') {

                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                $checkCount = $resultSelect->rowCount();
            } else {

                    $dataCheck = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                while ($rowCheck = $resultCheck->fetch()) {

                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = '(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)';
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                    }
                }
            }
            if ($checkCount < 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Get user's data

                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    $page->addError(__('The specified record cannot be found.'));
                } else {
                    //Check if there is already a pending form for this user
                    $existing = false;
                    $proceed = false;

                        $dataForm = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                        $sqlForm = "SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonID2 AND status='Pending'";
                        $resultForm = $connection2->prepare($sqlForm);
                        $resultForm->execute($dataForm);
                    if ($resultForm->rowCount() > 1) {
                        $page->addError(__('Your request failed due to a database error.'));
                    } elseif ($resultForm->rowCount() == 1) {
                        $existing = true;
                        echo "<div class='warning'>";
                        echo __('You have already submitted a form, which is awaiting processing by an administrator. If you wish to make changes, please edit the data below, but remember your data will not appear in the system until it has been processed.');
                        echo '</div>';
                        $proceed = true;
                    } else {
                        //Get user's data

                        $dataForm = array('gibbonPersonID' => $gibbonPersonID);
                        $sqlForm = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
                        $resultForm = $connection2->prepare($sqlForm);
                        $resultForm->execute($dataForm);

                        if ($result->rowCount() == 1) {
                            $proceed = true;
                        }
                    }

                    if ($proceed == true) {
						$values = $resultForm->fetch();

						$form = Form::create('updateFamily', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_medicalProcess.php?gibbonPersonID='.$gibbonPersonID);
						$form->setFactory(DatabaseFormFactory::create($pdo));

						$form->addHiddenValue('address', $session->get('address'));
						$form->addHiddenValue('gibbonPersonMedicalID', $values['gibbonPersonMedicalID'] ?? '');
						$form->addHiddenValue('existing', $values['gibbonPersonMedicalUpdateID'] ?? 'N');

                        $form->addRow()->addHeading('General Information', __('General Information'));
                        
						$row = $form->addRow();
							$row->addLabel('longTermMedication', __('Long-Term Medication?'));
							$row->addYesNo('longTermMedication')->placeholder();

						$form->toggleVisibilityByClass('longTermMedicationDetails')->onSelect('longTermMedication')->when('Y');

						$row = $form->addRow()->addClass('longTermMedicationDetails');
							$row->addLabel('longTermMedicationDetails', __('Medication Details'));
							$row->addTextArea('longTermMedicationDetails')->setRows(5);

                        $row = $form->addRow();
							$row->addLabel('comment', __('Comment'));
							$row->addTextArea('comment')->setRows(6);

                        // CUSTOM FIELDS
                        $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Medical Form', ['dataUpdater' => 1], $values['fields'] ?? '');

                        

						// EXISTING CONDITIONS
						$count = 0;
						if (!empty($values['gibbonPersonMedicalID']) or $existing == true) {

                            if ($existing == true) {
                                $medicalUpdateGateway = $container->get(MedicalUpdateGateway::class);
                                $conditions = $medicalUpdateGateway->selectMedicalConditionUpdatesByID($values['gibbonPersonMedicalUpdateID'])->fetchAll();
                            } else {
                                $medicalGateway = $container->get(MedicalGateway::class);
                                $conditions = $medicalGateway->selectMedicalConditionsByID($values['gibbonPersonMedicalID'])->fetchAll();
                            }

                            foreach ($conditions as $rowCond) {
								$form->addHiddenValue('gibbonPersonMedicalConditionID'.$count, $rowCond['gibbonPersonMedicalConditionID']);
								$form->addHiddenValue('gibbonPersonMedicalConditionUpdateID'.$count, $existing ? $rowCond['gibbonPersonMedicalConditionUpdateID'] : 0);

								$form->addRow()->addHeading(__('Medical Condition').' '.($count+1) );

								$sql = "SELECT name AS value, name FROM gibbonMedicalCondition ORDER BY name";
								$row = $form->addRow();
									$row->addLabel('name'.$count, __('Condition Name'));
									$row->addSelect('name'.$count)->fromQuery($pdo, $sql)->required()->placeholder()->selected($rowCond['name']);

								$row = $form->addRow();
									$row->addLabel('gibbonAlertLevelID'.$count, __('Risk'));
									$row->addSelectAlert('gibbonAlertLevelID'.$count)->required()->selected($rowCond['gibbonAlertLevelID']);

								$row = $form->addRow();
									$row->addLabel('triggers'.$count, __('Triggers'));
									$row->addTextField('triggers'.$count)->maxLength(255)->setValue($rowCond['triggers']);

								$row = $form->addRow();
									$row->addLabel('reaction'.$count, __('Reaction'));
									$row->addTextField('reaction'.$count)->maxLength(255)->setValue($rowCond['reaction']);

								$row = $form->addRow();
									$row->addLabel('response'.$count, __('Response'));
									$row->addTextField('response'.$count)->maxLength(255)->setValue($rowCond['response']);

								$row = $form->addRow();
									$row->addLabel('medication'.$count, __('Medication'));
									$row->addTextField('medication'.$count)->maxLength(255)->setValue($rowCond['medication']);

								$row = $form->addRow();
									$row->addLabel('lastEpisode'.$count, __('Last Episode Date'));
									$row->addDate('lastEpisode'.$count)->setValue(Format::date($rowCond['lastEpisode']) );

								$row = $form->addRow();
									$row->addLabel('lastEpisodeTreatment'.$count, __('Last Episode Treatment'));
									$row->addTextField('lastEpisodeTreatment'.$count)->maxLength(255)->setValue($rowCond['lastEpisodeTreatment']);

								$row = $form->addRow();
									$row->addLabel('commentCond'.$count, __('Comment'));
                                    $row->addTextArea('commentCond'.$count)->setValue($rowCond['comment']);

                                $row = $form->addRow();
                                    $row->addLabel('attachment'.$count, __('Attachment'))
                                        ->description(__('Additional details about this medical condition. Attachments are only visible to users who manage medical data.'));
                                    $row->addFileUpload('attachment'.$count)
                                        ->setAttachment('attachment', $session->get('absoluteURL'), $rowCond['attachment'] ?? '');

								$count++;
							}

							$form->addHiddenValue('count', $count);
						}

						// ADD NEW CONDITION
						$form->addRow()->addHeading('Add Medical Condition', __('Add Medical Condition'));

						$form->toggleVisibilityByClass('addConditionRow')->onCheckbox('addCondition')->when('Yes');

                        if ($medicalConditionIntro = $container->get(SettingGateway::class)->getSettingByScope('Students', 'medicalConditionIntro')) {
                            $row = $form->addRow();
                                $row->addContent($medicalConditionIntro);
                        }

						$row = $form->addRow();
							$row->addCheckbox('addCondition')->setValue('Yes')->description(__('Check the box to add a new medical condition'));

						$sql = "SELECT name AS value, name FROM gibbonMedicalCondition ORDER BY name";
						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('name', __('Condition Name'));
							$row->addSelect('name')->fromQuery($pdo, $sql)->required()->placeholder();

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('gibbonAlertLevelID', __('Risk'));
							$row->addSelectAlert('gibbonAlertLevelID')->required();

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('triggers', __('Triggers'));
							$row->addTextField('triggers')->maxLength(255);

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('reaction', __('Reaction'));
							$row->addTextField('reaction')->maxLength(255);

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('response', __('Response'));
							$row->addTextField('response')->maxLength(255);

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('medication', __('Medication'));
							$row->addTextField('medication')->maxLength(255);

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('lastEpisode', __('Last Episode Date'));
							$row->addDate('lastEpisode');

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('lastEpisodeTreatment', __('Last Episode Treatment'));
							$row->addTextField('lastEpisodeTreatment')->maxLength(255);

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('commentCond', __('Comment'));
                            $row->addTextArea('commentCond');

                        $row = $form->addRow()->addClass('addConditionRow');
                            $row->addLabel('attachment', __('Attachment'))
                                ->description(__('Additional details about this medical condition. Attachments are only visible to users who manage medical data.'));
                            $row->addFileUpload('attachment');

						$row = $form->addRow();
							$row->addFooter();
							$row->addSubmit();

						$form->loadAllValuesFrom($values);

						echo $form->getOutput();
                    }
                }
            }
        }
    }
}
