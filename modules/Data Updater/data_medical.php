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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Medical Data').'</div>';
        echo '</div>';

        if ($highestAction == 'Update Medical Data_any') {
            echo '<p>';
            echo __($guid, 'This page allows a user to request selected medical data updates for any student.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __($guid, 'This page allows any adult with data access permission to request medical data updates for any member of their family.');
            echo '</p>';
        }

        $customResponces = array();

        $success0 = __($guid, 'Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.');
        if ($_SESSION[$guid]['organisationDBAEmail'] != '' and $_SESSION[$guid]['organisationDBAName'] != '') {
            $success0 .= ' '.sprintf(__($guid, 'Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationDBAEmail']."'>".$_SESSION[$guid]['organisationDBAName'].'</a>');
        }
        $customResponces['success0'] = $success0;

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $customResponces);
        }

        echo '<h2>';
        echo 'Choose User';
        echo '</h2>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
		}

		$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : null;

		$form = Form::create('selectFamily', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
		$form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/data_medical.php');

		if ($highestAction == 'Update Medical Data_any') {
			$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, username, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName";
		} else {
			$data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
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
			$carry[$value] = formatName('', htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Student', true);
			if ($highestAction == 'Update Medical Data_any') {
				$carry[$value] .= ' ('.$person['username'].')';
			}
			return $carry;
		}, array());

		$row = $form->addRow();
			$row->addLabel('gibbonPersonID', __('Person'));
			$row->addSelect('gibbonPersonID')
                ->fromArray($people)
                ->isRequired()
                ->selected($gibbonPersonID)
				->placeholder();

		$row = $form->addRow();
            $row->addSubmit();

		echo $form->getOutput();


        if ($gibbonPersonID != '') {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Medical Data_any') {
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                }
                $checkCount = $resultSelect->rowCount();
            } else {
                try {
                    $dataCheck = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                } catch (PDOException $e) {
                }
                while ($rowCheck = $resultCheck->fetch()) {
                    try {
                        $dataCheck2 = array('gibbonFamilyID' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = '(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID2)';
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                    }
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                    }
                }
            }
            if ($checkCount < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Get user's data
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
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
                    //Check if there is already a pending form for this user
                    $existing = false;
                    $proceed = false;
                    try {
                        $dataForm = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlForm = "SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonID2 AND status='Pending'";
                        $resultForm = $connection2->prepare($sqlForm);
                        $resultForm->execute($dataForm);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultForm->rowCount() > 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.');
                        echo '</div>';
                    } elseif ($resultForm->rowCount() == 1) {
                        $existing = true;
                        echo "<div class='warning'>";
                        echo __($guid, 'You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.');
                        echo '</div>';
                        $proceed = true;
                    } else {
                        //Get user's data
                        try {
                            $dataForm = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlForm = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
                            $resultForm = $connection2->prepare($sqlForm);
                            $resultForm->execute($dataForm);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() == 1) {
                            $proceed = true;
                        }
                    }

                    if ($proceed == true) {
						$values = $resultForm->fetch();

						$form = Form::create('updateFamily', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_medicalProcess.php?gibbonPersonID='.$gibbonPersonID);
						$form->setFactory(DatabaseFormFactory::create($pdo));

						$form->addHiddenValue('address', $_SESSION[$guid]['address']);
						$form->addHiddenValue('gibbonPersonMedicalID', $values['gibbonPersonMedicalID']);
						$form->addHiddenValue('existing', isset($values['gibbonPersonMedicalUpdateID'])? $values['gibbonPersonMedicalUpdateID'] : 'N');

						$row = $form->addRow();
							$row->addLabel('bloodType', __('Blood Type'));
							$row->addSelectBloodType('bloodType')->placeholder();

						$row = $form->addRow();
							$row->addLabel('longTermMedication', __('Long-Term Medication?'));
							$row->addYesNo('longTermMedication')->placeholder();

						$form->toggleVisibilityByClass('longTermMedicationDetails')->onSelect('longTermMedication')->when('Y');

						$row = $form->addRow()->addClass('longTermMedicationDetails');
							$row->addLabel('longTermMedicationDetails', __('Medication Details'));
							$row->addTextArea('longTermMedicationDetails')->setRows(5);

						$row = $form->addRow();
							$row->addLabel('tetanusWithin10Years', __('Tetanus Within Last 10 Years?'));
							$row->addYesNo('tetanusWithin10Years')->placeholder();

                        $row = $form->addRow();
							$row->addLabel('comment', __('Comment'));
							$row->addTextArea('comment')->setRows(6);

						// EXISTING CONDITIONS
						$count = 0;
						if ($values['gibbonPersonMedicalID'] != '' or $existing == true) {
                            try {
                                if ($existing == true) {
                                    $dataCond = array('gibbonPersonMedicalUpdateID' => $values['gibbonPersonMedicalUpdateID']);
                                    $sqlCond = 'SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID ORDER BY name';
                                } else {
                                    $dataCond = array('gibbonPersonMedicalID' => $values['gibbonPersonMedicalID']);
                                    $sqlCond = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY name';
                                }
                                $resultCond = $connection2->prepare($sqlCond);
                                $resultCond->execute($dataCond);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            while ($rowCond = $resultCond->fetch()) {
								$form->addHiddenValue('gibbonPersonMedicalConditionID'.$count, $rowCond['gibbonPersonMedicalConditionID']);
								$form->addHiddenValue('gibbonPersonMedicalConditionUpdateID'.$count, $existing ? $rowCond['gibbonPersonMedicalConditionUpdateID'] : 0);

								$form->addRow()->addHeading(__('Medical Condition').' '.($count+1) );

								$sql = "SELECT gibbonMedicalConditionID AS value, name FROM gibbonMedicalCondition ORDER BY name";
								$row = $form->addRow();
									$row->addLabel('name'.$count, __('Condition Name'));
									$row->addSelect('name'.$count)->fromQuery($pdo, $sql)->isRequired()->placeholder()->selected($rowCond['name']);

								$row = $form->addRow();
									$row->addLabel('gibbonAlertLevelID'.$count, __('Risk'));
									$row->addSelectAlert('gibbonAlertLevelID'.$count)->isRequired()->selected($rowCond['gibbonAlertLevelID']);

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
									$row->addDate('lastEpisode'.$count)->setValue(dateConvertBack($guid, $rowCond['lastEpisode']) );

								$row = $form->addRow();
									$row->addLabel('lastEpisodeTreatment'.$count, __('Last Episode Treatment'));
									$row->addTextField('lastEpisodeTreatment'.$count)->maxLength(255)->setValue($rowCond['lastEpisodeTreatment']);

								$row = $form->addRow();
									$row->addLabel('comment'.$count, __('Comment'));
									$row->addTextArea('comment'.$count)->setValue($rowCond['comment']);

								$count++;
							}

							$form->addHiddenValue('count', $count);
						}

						// ADD NEW CONDITION
						$form->addRow()->addHeading(__('Add Medical Condition'));

						$form->toggleVisibilityByClass('addConditionRow')->onCheckbox('addCondition')->when('Yes');

						$row = $form->addRow();
							$row->addCheckbox('addCondition')->setValue('Yes')->description(__('Check the box to add a new medical condition'));

						$sql = "SELECT gibbonMedicalConditionID AS value, name FROM gibbonMedicalCondition ORDER BY name";
						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('name', __('Condition Name'));
							$row->addSelect('name')->fromQuery($pdo, $sql)->isRequired()->placeholder();

						$row = $form->addRow()->addClass('addConditionRow');
							$row->addLabel('gibbonAlertLevelID', __('Risk'));
							$row->addSelectAlert('gibbonAlertLevelID')->isRequired();

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
							$row->addLabel('comment', __('Comment'));
							$row->addTextArea('comment');

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
