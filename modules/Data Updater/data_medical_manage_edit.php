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

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Data Updater/data_medical_manage.php'>".__($guid, 'Medical Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonPersonMedicalUpdateID = $_GET['gibbonPersonMedicalUpdateID'];
    if ($gibbonPersonMedicalUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = 'SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $row = $result->fetch();

            $formExists = false;
            $gibbonPersonID = $row['gibbonPersonID'];
            $formOK = true;
            try {
                $data2 = array('gibbonPersonID' => $gibbonPersonID);
                $sql2 = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
                $result2 = $connection2->prepare($sql2);
                $result2->execute($data2);
            } catch (PDOException $e) {
                $formOK = false;
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($formOK == true) {
                if ($result2->rowCount() == 1) {
                    $formExists = true;
                    $row2 = $result2->fetch();
                }

                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/data_medical_manage_editProcess.php?gibbonPersonMedicalUpdateID=$gibbonPersonMedicalUpdateID" ?>">
					<?php

                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Field');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Current Value');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'New Value');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Accept');
                echo '</th>';
                echo '</tr>';
                echo "<tr class='break'>";
                echo '<td colspan=4> ';
                echo '<h3>'.__($guid, 'Basic Information').'</h3>';
                echo '</td>';
                echo '</tr>';

                $rowNum = 'odd';
                $rowNum = 'even';

				//COLOR ROW BY STATUS!
				echo "<tr class='odd'>";
                echo '<td>';
                echo __($guid, 'Blood Type');
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    echo $row2['bloodType'];
                }
                echo '</td>';
                echo '<td>';
                $style = '';
                if (isset($row2)) {
                    if ($row2['bloodType'] != $row['bloodType']) {
                        $style = "style='color: #ff0000'";
                    }
                }
                echo "<span $style>";
                echo $row['bloodType'];
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    if ($row2['bloodType'] != $row['bloodType']) {
                        echo "<input checked type='checkbox' name='bloodTypeOn'><input name='bloodType' type='hidden' value='".htmlprep($row['bloodType'])."'>";
                    }
                } elseif ($row['bloodType'] != '') {
                    echo "<input checked type='checkbox' name='bloodTypeOn'><input name='bloodType' type='hidden' value='".htmlprep($row['bloodType'])."'>";
                }
                echo '</td>';
                echo '</tr>';
                echo "<tr class='even'>";
                echo '<td>';
                echo __($guid, 'Long Term Medication');
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    echo $row2['longTermMedication'];
                }
                echo '</td>';
                echo '<td>';
                $style = '';
                if (isset($row2)) {
                    if ($row2['longTermMedication'] != $row['longTermMedication']) {
                        $style = "style='color: #ff0000'";
                    }
                }
                echo "<span $style>";
                echo $row['longTermMedication'];
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    if ($row2['longTermMedication'] != $row['longTermMedication']) {
                        echo "<input checked type='checkbox' name='longTermMedicationOn'><input name='longTermMedication' type='hidden' value='".htmlprep($row['longTermMedication'])."'>";
                    }
                } elseif ($row['longTermMedication'] != '') {
                    echo "<input checked type='checkbox' name='longTermMedicationOn'><input name='longTermMedication' type='hidden' value='".htmlprep($row['longTermMedication'])."'>";
                }
                echo '</td>';
                echo '</tr>';
                echo "<tr class='odd'>";
                echo '<td>';
                echo __($guid, 'Long Term Medication Details');
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    echo $row2['longTermMedicationDetails'];
                }
                echo '</td>';
                echo '<td>';
                $style = '';
                if (isset($row2)) {
                    if ($row2['longTermMedicationDetails'] != $row['longTermMedicationDetails']) {
                        $style = "style='color: #ff0000'";
                    }
                }
                echo "<span $style>";
                echo $row['longTermMedicationDetails'];
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    if ($row2['longTermMedicationDetails'] != $row['longTermMedicationDetails']) {
                        echo "<input checked type='checkbox' name='longTermMedicationDetailsOn'><input name='longTermMedicationDetails' type='hidden' value='".htmlprep($row['longTermMedicationDetails'])."'>";
                    }
                } elseif ($row['longTermMedicationDetails'] != '') {
                    echo "<input checked type='checkbox' name='longTermMedicationDetailsOn'><input name='longTermMedicationDetails' type='hidden' value='".htmlprep($row['longTermMedicationDetails'])."'>";
                }
                echo '</td>';
                echo '</tr>';
                echo "<tr class='even'>";
                echo '<td>';
                echo __($guid, 'Tetanus Within 10 Years');
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    echo $row2['tetanusWithin10Years'];
                }
                echo '</td>';
                echo '<td>';
                $style = '';
                if (isset($row2)) {
                    if ($row2['tetanusWithin10Years'] != $row['tetanusWithin10Years']) {
                        $style = "style='color: #ff0000'";
                    }
                }
                echo "<span $style>";
                echo $row['tetanusWithin10Years'];
                echo '</td>';
                echo '<td>';
                if (isset($row2)) {
                    if ($row2['tetanusWithin10Years'] != $row['tetanusWithin10Years']) {
                        echo "<input checked type='checkbox' name='tetanusWithin10YearsOn'><input name='tetanusWithin10Years' type='hidden' value='".htmlprep($row['tetanusWithin10Years'])."'>";
                    }
                } elseif ($row['tetanusWithin10Years'] != '') {
                    echo "<input checked type='checkbox' name='tetanusWithin10YearsOn'><input name='tetanusWithin10Years' type='hidden' value='".htmlprep($row['tetanusWithin10Years'])."'>";
                }
                echo '</td>';
                echo '</tr>';

                    //Get existing conditions
                    try {
                        $dataCond = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
                        $sqlCond = 'SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID AND NOT gibbonPersonMedicalConditionID IS NULL ORDER BY gibbonPersonMedicalConditionUpdateID';
                        $resultCond = $connection2->prepare($sqlCond);
                        $resultCond->execute($dataCond);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                $count = 0;
                if ($resultCond->rowCount() > 0) {
                    while ($rowCond = $resultCond->fetch()) {
                        $resultCond2 = null;
                        try {
                            $dataCond2 = array('gibbonPersonMedicalConditionID' => $rowCond['gibbonPersonMedicalConditionID']);
                            $sqlCond2 = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID';
                            $resultCond2 = $connection2->prepare($sqlCond2);
                            $resultCond2->execute($dataCond2);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultCond2->rowCount() == 1) {
                            $rowCond2 = $resultCond2->fetch();
                        }

                        echo "<tr class='break'>";
                        echo '<td colspan=4> ';
                        echo '<h3>'.__($guid, 'Existing Condition').' '.($count + 1).'</h3>';
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Name');
                        echo '</td>';
                        echo '<td>';
                        echo __($guid, $rowCond2['name']);
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['name'] != $rowCond['name']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo __($guid, $rowCond['name']);
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['name'] != $rowCond['name']) {
                            echo "<input checked type='checkbox' name='nameOn$count'><input name='name$count' type='hidden' value='".htmlprep($rowCond['name'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Risk');
                        echo '</td>';
                        echo '<td>';
                        $alert = getAlert($guid, $connection2, $rowCond2['gibbonAlertLevelID']);
                        if ($alert != false) {
                            $style = '';
                            if ($rowCond2['gibbonAlertLevelID'] != $rowCond['gibbonAlertLevelID']) {
                                $style = "style='color: #ff0000'";
                            }
                            echo "<span $style>";
                            echo __($guid, $alert['name']);
                        }
                        echo '</td>';
                        echo '<td>';
                        $alert = getAlert($guid, $connection2, $rowCond['gibbonAlertLevelID']);
                        if ($alert != false) {
                            $style = '';
                            if ($rowCond2['gibbonAlertLevelID'] != $rowCond['gibbonAlertLevelID']) {
                                $style = "style='color: #ff0000'";
                            }
                            echo "<span $style>";
                            echo $alert['name'];
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['gibbonAlertLevelID'] != $rowCond['gibbonAlertLevelID']) {
                            echo "<input checked type='checkbox' name='gibbonAlertLevelIDOn$count'><input name='gibbonAlertLevelID$count' type='hidden' value='".htmlprep($rowCond['gibbonAlertLevelID'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Triggers');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['triggers'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['triggers'] != $rowCond['triggers']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['triggers'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['triggers'] != $rowCond['triggers']) {
                            echo "<input checked type='checkbox' name='triggersOn$count'><input name='triggers$count' type='hidden' value='".htmlprep($rowCond['triggers'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Reaction');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['reaction'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['reaction'] != $rowCond['reaction']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['reaction'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['reaction'] != $rowCond['reaction']) {
                            echo "<input checked type='checkbox' name='reactionOn$count'><input name='reaction$count' type='hidden' value='".htmlprep($rowCond['reaction'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Response');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['response'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['response'] != $rowCond['response']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['response'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['response'] != $rowCond['response']) {
                            echo "<input checked type='checkbox' name='responseOn$count'><input name='response$count' type='hidden' value='".htmlprep($rowCond['response'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';

                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Medication');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['medication'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['medication'] != $rowCond['medication']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['medication'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['medication'] != $rowCond['medication']) {
                            echo "<input checked type='checkbox' name='medicationOn$count'><input name='medication$count' type='hidden' value='".htmlprep($rowCond['medication'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Last Episode');
                        echo '</td>';
                        echo '<td>';
                        echo dateConvertBack($guid, $rowCond2['lastEpisode']);
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['lastEpisode'] != $rowCond['lastEpisode']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo dateConvertBack($guid, $rowCond['lastEpisode']);
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['lastEpisode'] != $rowCond['lastEpisode']) {
                            echo "<input checked type='checkbox' name='lastEpisodeOn$count'><input name='lastEpisode$count' type='hidden' value='".htmlprep($rowCond['lastEpisode'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Last Episode Treatment');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['lastEpisodeTreatment'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['lastEpisodeTreatment'] != $rowCond['lastEpisodeTreatment']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['lastEpisodeTreatment'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['lastEpisodeTreatment'] != $rowCond['lastEpisodeTreatment']) {
                            echo "<input checked type='checkbox' name='lastEpisodeTreatmentOn$count'><input name='lastEpisodeTreatment$count' type='hidden' value='".htmlprep($rowCond['lastEpisodeTreatment'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Comment');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['comment'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['comment'] != $rowCond['comment']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['comment'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['comment'] != $rowCond['comment']) {
                            echo "<input checked type='checkbox' name='commentOn$count'><input name='comment$count' type='hidden' value='".htmlprep($rowCond['comment'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';

                        echo "<input name='gibbonPersonMedicalConditionID$count' id='gibbonPersonMedicalConditionID$count' type='hidden' value='".htmlprep($rowCond['gibbonPersonMedicalConditionID'])."'>";

                        ++$count;
                    }
                }

                echo "<input name='count' id='count' value='$count' type='hidden'>";

                    //Get new conditions
                    $count2 = 0;
                try {
                    $dataCond = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
                    $sqlCond = 'SELECT * FROM gibbonPersonMedicalConditionUpdate WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID AND gibbonPersonMedicalConditionID IS NULL ORDER BY name';
                    $resultCond = $connection2->prepare($sqlCond);
                    $resultCond->execute($dataCond);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultCond->rowCount() > 0) {
                    while ($rowCond = $resultCond->fetch()) {
                        ++$count2;
                        $resultCond2 = null;
                        $rowCond2 = null;
                        echo "<tr class='break'>";
                        echo '<td colspan=4> ';
                        echo '<h3>'.__($guid, 'New Condition').' '.$count2.'</h3>';
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Name');
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['name'] != '') {
                            echo __($guid, $rowCond2['name']);
                        }
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['name'] != $rowCond['name']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo __($guid, $rowCond['name']);
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['name'] != $rowCond['name']) {
                            echo "<input checked type='checkbox' name='nameOn".($count + $count2)."'><input name='name".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['name'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Risk');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['gibbonAlertLevelID'];
                        echo '</td>';
                        echo '<td>';
                        $alert = getAlert($guid, $connection2, $rowCond['gibbonAlertLevelID']);
                        if ($alert != false) {
                            $style = '';
                            if ($rowCond2['gibbonAlertLevelID'] != $rowCond['gibbonAlertLevelID']) {
                                $style = "style='color: #ff0000'";
                            }
                            echo "<span $style>";
                            echo __($guid, $alert['name']);
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['gibbonAlertLevelID'] != $rowCond['gibbonAlertLevelID']) {
                            echo "<input checked type='checkbox' name='gibbonAlertLevelIDOn".($count + $count2)."'><input name='gibbonAlertLevelID".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['gibbonAlertLevelID'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Triggers');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['triggers'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['triggers'] != $rowCond['triggers']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['triggers'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['triggers'] != $rowCond['triggers']) {
                            echo "<input checked type='checkbox' name='triggersOn".($count + $count2)."'><input name='triggers".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['triggers'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Reaction');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['reaction'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['reaction'] != $rowCond['reaction']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['reaction'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['reaction'] != $rowCond['reaction']) {
                            echo "<input checked type='checkbox' name='reactionOn".($count + $count2)."'><input name='reaction".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['reaction'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Response');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['response'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['response'] != $rowCond['response']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['response'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['response'] != $rowCond['response']) {
                            echo "<input checked type='checkbox' name='responseOn".($count + $count2)."'><input name='response".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['response'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';

                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Medication');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['medication'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['medication'] != $rowCond['medication']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['medication'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['medication'] != $rowCond['medication']) {
                            echo "<input checked type='checkbox' name='medicationOn".($count + $count2)."'><input name='medication".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['medication'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Last Episode');
                        echo '</td>';
                        echo '<td>';
                        echo dateConvertBack($guid, $rowCond2['lastEpisode']);
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['lastEpisode'] != $rowCond['lastEpisode']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo dateConvertBack($guid, $rowCond['lastEpisode']);
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['lastEpisode'] != $rowCond['lastEpisode']) {
                            echo "<input checked type='checkbox' name='lastEpisodeOn".($count + $count2)."'><input name='lastEpisode".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['lastEpisode'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='even'>";
                        echo '<td>';
                        echo __($guid, 'Last Episode Treatment');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['lastEpisodeTreatment'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['lastEpisodeTreatment'] != $rowCond['lastEpisodeTreatment']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['lastEpisodeTreatment'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['lastEpisodeTreatment'] != $rowCond['lastEpisodeTreatment']) {
                            echo "<input checked type='checkbox' name='lastEpisodeTreatmentOn".($count + $count2)."'><input name='lastEpisodeTreatment".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['lastEpisodeTreatment'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo "<tr class='odd'>";
                        echo '<td>';
                        echo __($guid, 'Comment');
                        echo '</td>';
                        echo '<td>';
                        echo $rowCond2['comment'];
                        echo '</td>';
                        echo '<td>';
                        $style = '';
                        if ($rowCond2['comment'] != $rowCond['comment']) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        echo $rowCond['comment'];
                        echo '</td>';
                        echo '<td>';
                        if ($rowCond2['comment'] != $rowCond['comment']) {
                            echo "<input checked type='checkbox' name='commentOn".($count + $count2)."'><input name='comment".($count + $count2)."' type='hidden' value='".htmlprep($rowCond['comment'])."'>";
                        }
                        echo '</td>';
                        echo '</tr>';

                        echo "<input type='hidden' name='gibbonPersonMedicalConditionUpdateID".($count + $count2)."' type='gibbonPersonMedicalConditionUpdateID".($count + $count2)."' value='".$rowCond['gibbonPersonMedicalConditionUpdateID']."'>";
                    }
                    echo "<input name='count2' id='count2' value='$count2' type='hidden'>";
                }

                echo '<tr>';
                echo "<td class='right' colspan=4>";
                echo "<input name='formExists' type='hidden' value='$formExists'>";
                echo "<input name='gibbonPersonID' type='hidden' value='".$row['gibbonPersonID']."'>";
                echo "<input name='address' type='hidden' value='".$_GET['q']."'>";
                echo "<input type='submit' value='Submit'>";
                echo '</td>';
                echo '</tr>';
                echo '</table>'; ?>
				</form>
			<?php

            }
        }
    }
}
?>