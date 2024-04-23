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
use Gibbon\Data\Validator;
use Gibbon\Domain\Rubrics\RubricGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Search & Filters
$search = $_GET['search'] ?? '';

$filter2 = $_GET['filter2'] ?? '';


$gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_edit.php&gibbonRubricID=$gibbonRubricID&sidebar=false&search=$search&filter2=$filter2";

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if gibbonRubricID specified
            if ($gibbonRubricID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    if ($highestAction == 'Manage Rubrics_viewEditAll') {
                        $result = $container->get(RubricGateway::class)->selectBy(['gibbonRubricID' => $gibbonRubricID]);
                    } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                        $result = $container->get(RubricGateway::class)->selectLARubricsByStaffAndDepartment($gibbonRubricID, $session->get('gibbonPersonID'));
                    }
                } catch (PDOException $e) {
                    $URL .= '&columnDeleteReturn=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $partialFail = false;

                    //Add in all cells
                    $cells = $_POST['cell'] ?? [];
                    for ($i = 0; $i < count($cells); ++$i) {
                        if ($_POST['gibbonRubricColumnID'][$i] == '' or $_POST['gibbonRubricRowID'][$i] == '') {
                            $partialFail = true;
                        } else {
                            //CELL DOES NOT EXIST YET
                            if ($_POST['gibbonRubricCellID'][$i] == '') {
                                try {
                                    $data = ['gibbonRubricID' => $gibbonRubricID, 'gibbonRubricColumnID' => $_POST['gibbonRubricColumnID'][$i], 'gibbonRubricRowID' => $_POST['gibbonRubricRowID'][$i], 'contents' => $_POST['cell'][$i]];
                                    $sql = 'INSERT INTO gibbonRubricCell SET gibbonRubricID=:gibbonRubricID, gibbonRubricColumnID=:gibbonRubricColumnID, gibbonRubricRowID=:gibbonRubricRowID, contents=:contents';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                            //CELL EXISTS
                            else {
                                try {
                                    $data = ['gibbonRubricID' => $gibbonRubricID, 'gibbonRubricColumnID' => $_POST['gibbonRubricColumnID'][$i], 'gibbonRubricRowID' => $_POST['gibbonRubricRowID'][$i], 'contents' => $_POST['cell'][$i], 'gibbonRubricCellID' => $_POST['gibbonRubricCellID'][$i]];
                                    $sql = 'UPDATE gibbonRubricCell SET gibbonRubricID=:gibbonRubricID, gibbonRubricColumnID=:gibbonRubricColumnID, gibbonRubricRowID=:gibbonRubricRowID, contents=:contents WHERE gibbonRubricCellID=:gibbonRubricCellID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                        }
                    }

                    //Deal with partial fail and success
                    if ($partialFail == true) {
                        $URL .= '&return=error5';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
