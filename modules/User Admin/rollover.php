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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/rollover.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Rollover').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $step = null;
    if (isset($_GET['step'])) {
        $step = $_GET['step'];
    }
    if ($step != 1 and $step != 2 and $step != 3) {
        $step = 1;
    }

    //Step 1
    if ($step == 1) {
        echo '<h3>';
        echo __($guid, 'Step 1');
        echo '</h3>';

        $nextYear = getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2);
        if ($nextYear == false) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            if ($nameNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollover.php&step=2');

                $form->setClass('smallIntBorder fullWidth');

                $form->addHiddenValue('nextYear', $nextYear);

                $row = $form->addRow();
                    $row->addContent(sprintf(__('By clicking the "Proceed" button below you will initiate the rollover from %1$s to %2$s. In a big school this operation may take some time to complete. This will change data in numerous tables across the system! %3$sYou are really, very strongly advised to backup all data before you proceed%4$s.'), '<b>'.$_SESSION[$guid]['gibbonSchoolYearName'].'</b>', '<b>'.$nameNext.'</b>', '<span style="color: #cc0000"><i>', '</span>'));

                $row = $form->addRow();
                    $row->addSubmit('Proceed');

                echo $form->getOutput();
            }
        }
    } elseif ($step == 2) {
        echo '<h3>';
        echo __($guid, 'Step 2');
        echo '</h3>';

        $nextYear = $_POST['nextYear'];
        if ($nextYear == '' or $nextYear != getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2)) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            $sequenceNext = $rowNext['sequenceNumber'];
            if ($nameNext == '' or $sequenceNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                echo '<p>';
                echo sprintf(__($guid, 'In rolling over to %1$s, the following actions will take place. You may need to adjust some fields below to get the result you desire.'), $nameNext);
                echo '</p>';

                //Set up years, roll groups and statuses arrays for use later on
                $yearGroups = array();
                try {
                    $dataSelect = array();
                    $sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $yearGroups[$rowSelect['gibbonYearGroupID']] =  htmlPrep($rowSelect['name']);
                }

                $rollGroups = array();
                try {
                    $dataSelect = array('gibbonSchoolYearID' => $nextYear);
                    $sqlSelect = 'SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $rollGroups[$rowSelect['gibbonRollGroupID']] =  htmlPrep($rowSelect['name']);
                }

                $statuses = array(
                    'Expected'     => __('Expected'),
                    'Full'  => __('Full'),
                    'Left' => __('Left'),
                );

                //START FORM
                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rollover.php&step=3");

                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('smallIntBorder fullWidth');

                $form->addHiddenValue('nextYear', $nextYear);

                //ADD YEAR FOLLOWING NEXT
                if (getNextSchoolYearID($nextYear, $connection2) == false) {
                    $form->addRow()->addHeading(sprintf(__('Add Year Following %1$s'), $nameNext));

                    $row = $form->addRow();
                        $row->addLabel('nextname', __('School Year Name'))->description(__('Must be unique.'));
                        $row->addTextField('nextname')->isRequired()->maxLength(9);

                    $row = $form->addRow();
                        $row->addLabel('nextstatus', __('Status'));
                        $row->addTextField('nextstatus')->setValue(__('Upcoming'))->isRequired()->readonly();

                    $row = $form->addRow();
                        $row->addLabel('nextsequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
                        $row->addSequenceNumber('nextsequenceNumber', 'gibbonSchoolYear', '', 'sequenceNumber')->isRequired()->maxLength(3)->readonly();

                    $row = $form->addRow();
                        $row->addLabel('nextfirstDay', __('First Day'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                        $row->addDate('nextfirstDay')->isRequired();

                    $row = $form->addRow();
                        $row->addLabel('nextlastDay', __('Last Day'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                        $row->addDate('nextlastDay')->isRequired();
                }

                //SET EXPECTED USERS TO FULL
                $form->addRow()->addHeading(__('Set Expected Users To Full'));
                $form->addRow()->addContent(__('This step primes newcomers who have status set to "Expected" to be enroled as students or added as staff (below).'));

                try {
                    $dataExpect = array();
                    $sqlExpect = "SELECT gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' ORDER BY name, surname, preferredName";
                    $resultExpect = $connection2->prepare($sqlExpect);
                    $resultExpect->execute($dataExpect);
                } catch (PDOException $e) {
                    $form->addRow()->addAlert($e->getMessage(), 'error');
                }
                if ($resultExpect->rowCount() < 1) {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                } else {
                    $row = $form->addRow()->addClass('head break');
                        $row->addColumn()->addContent(__('Name'));
                        $row->addColumn()->addContent(__('Primary Role'));
                        $row->addColumn()->addContent(__('Current Status'));
                        $row->addColumn()->addContent(__('New Status'));

                    $count = 0;
                    while ($rowExpect = $resultExpect->fetch()) {
                        $count++;
                        $form->addHiddenValue($count."-expect-gibbonPersonID", $rowExpect['gibbonPersonID']);
                        $row = $form->addRow();
                            $row->addColumn()->addContent(formatName('', $rowExpect['preferredName'], $rowExpect['surname'], 'Student', true));
                            $row->addColumn()->addContent(__($rowExpect['name']));
                            $row->addColumn()->addContent(__('Expected'));
                            $column = $row->addColumn();
                                $column->addSelect($count."-expect-status")->fromArray($statuses)->isRequired()->setClass('shortWidth floatNone');
                    }
                    $form->addHiddenValue("expect-count", $count);
                }

                //ENROL NEW STUDENTS - EXPECTED
                $form->addRow()->addHeading(__('Enrol New Students (Status Expected)'));
                $form->addRow()->addContent(__('Take students who are marked expected and enrol them. All parents of new students who are enroled below will have their status set to "Full". If a student is not enroled, they will be set to "Left".'));

                if (count($yearGroups) < 1 or count($rollGroups) < 1) {
                    $form->addRow()->addAlert(__('Year groups or roll groups are not properly set up, so you cannot proceed with this section.'), 'error');
                } else {
                    try {
                        $dataEnrol = array();
                        $sqlEnrol = "SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Student' ORDER BY surname, preferredName";
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        $form->addRow()->addAlert($e->getMessage(), 'error');
                    }

                    if ($resultEnrol->rowCount() < 1) {
                        $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                    } else {
                        $row = $form->addRow()->addClass('head break');
                            $row->addColumn()->addContent(__('Name'));
                            $row->addColumn()->addContent(__('Primary Role'));
                            $row->addColumn()->addContent(__('Enrol'));
                            $row->addColumn()->addContent(__('Year Group'));
                            $row->addColumn()->addContent(__('Form Group'));

                        $count = 0;
                        while ($rowEnrol = $resultEnrol->fetch()) {
                            $count++;
                            $form->addHiddenValue($count."-enrol-gibbonPersonID", $rowEnrol['gibbonPersonID']);
                            $row = $form->addRow();
                                $row->addColumn()->addContent(formatName('', $rowEnrol['preferredName'], $rowEnrol['surname'], 'Student', true));
                                $row->addColumn()->addContent(__($rowEnrol['name']));
                                $column = $row->addColumn();
                                    $column->addCheckbox($count."-enrol-enrol")->setValue('Y')->checked('Y');
                                $column = $row->addColumn();
                                    $column->addSelect($count."-enrol-gibbonYearGroupID")->fromArray($yearGroups)->isRequired()->setClass('shortWidth floatNone');
                                $column = $row->addColumn();
                                    $column->addSelect($count."-enrol-gibbonRollGroupID")->fromArray($rollGroups)->isRequired()->setClass('shortWidth floatNone');
                        }
                        $form->addHiddenValue("enrol-count", $count);
                    }
                }

                //ENROL NEW STUDENTS - FULL
                $form->addRow()->addHeading(__('Enrol New Students (Status Full)'));
                $form->addRow()->addContent(__('Take new students who are already set as full, but who were not enroled last year, and enrol them. These students probably came through the Online Application form, and may already be enroled in next year: if this is the case, their enrolment will be updated as per the information below. All parents of new students who are enroled below will have their status set to "Full". If a student is not enroled, they will be set to "Left"'));

                if (count($yearGroups) < 1 or count($rollGroups) < 1) {
                    $form->addRow()->addAlert(__('Year groups or roll groups are not properly set up, so you cannot proceed with this section.'), 'error');
                } else {
                    $students = array();
                    $count = 0;
                    try {
                        $dataEnrol = array();
                        $sqlEnrol = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRole.name, category
                            FROM gibbonPerson
                                JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                                LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                LEFT JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                            WHERE gibbonPerson.status='Full'
                                AND category='Student'
                                AND (gibbonStudentEnrolment.gibbonPersonID IS NULL OR gibbonSchoolYear.status='Upcoming')
                            ORDER BY surname, preferredName";
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        $form->addRow()->addAlert($e->getMessage());
                    }

                    if ($resultEnrol->rowCount() < 1) {
                        $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                    } else {
                        while ($rowEnrol = $resultEnrol->fetch()) {
                            try {
                                $dataEnrolled = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $rowEnrol['gibbonPersonID']);
                                $sqlEnrolled = "SELECT gibbonStudentEnrolment.* FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                $resultEnrolled = $connection2->prepare($sqlEnrolled);
                                $resultEnrolled->execute($dataEnrolled);
                            } catch (PDOException $e) {
                                $form->addRow()->addAlert($e->getMessage(), 'error');
                            }
                            if ($resultEnrolled->rowCount() < 1) {
                                $students[$count][0] = $rowEnrol['gibbonPersonID'];
                                $students[$count][1] = $rowEnrol['surname'];
                                $students[$count][2] = $rowEnrol['preferredName'];
                                $students[$count][3] = $rowEnrol['name'];
                                ++$count;
                            }
                        }
                    }

                    if ($count < 1) {
                        $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                    } else {
                        $row = $form->addRow()->addClass('head break');
                            $row->addColumn()->addContent(__('Name'));
                            $row->addColumn()->addContent(__('Primary Role'));
                            $row->addColumn()->addContent(__('Enrol'));
                            $row->addColumn()->addContent(__('Year Group'));
                            $row->addColumn()->addContent(__('Form Group'));

                        $count = 0;
                        foreach ($students AS $student) {
                            $count++;
                            //Check for enrolment in next year (caused by automated enrolment on application form accept)
                            $yearGroupSelect = '';
                            $rollGroupSelect = '';
                            try {
                                $dataEnrolled = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $student[0]);
                                $sqlEnrolled = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                $resultEnrolled = $connection2->prepare($sqlEnrolled);
                                $resultEnrolled->execute($dataEnrolled);
                            } catch (PDOException $e) {
                                $form->addRow()->addAlert($e->getMessage(), 'error');
                            }
                            if ($resultEnrolled->rowCount() == 1) {
                                $rowEnrolled = $resultEnrolled->fetch();
                                $yearGroupSelect = $rowEnrolled['gibbonYearGroupID'];
                                $rollGroupSelect = $rowEnrolled['gibbonRollGroupID'];
                            }

                            $form->addHiddenValue($count."-enrolFull-gibbonPersonID", $student[0]);
                            $row = $form->addRow();
                                $row->addColumn()->addContent(formatName('', $student[2], $student[2], 'Student', true));
                                $row->addColumn()->addContent(__($student[3]));
                                $column = $row->addColumn();
                                    $column->addCheckbox($count."-enrolFull-enrol")->setValue('Y')->checked('Y');
                                $column = $row->addColumn();
                                    $column->addSelect($count."-enrolFull-gibbonYearGroupID")->fromArray($yearGroups)->isRequired()->setClass('shortWidth floatNone')->selected($yearGroupSelect);
                                $column = $row->addColumn();
                                    $column->addSelect($count."-enrolFull-gibbonRollGroupID")->fromArray($rollGroups)->isRequired()->setClass('shortWidth floatNone')->selected($rollGroupSelect);
                        }
                        $form->addHiddenValue("enrolFull-count", $count);
                    }
                }

                //RE-ENROL OTHER STUDENTS
                $form->addRow()->addHeading(__('Re-Enrol Other Students'));
                $form->addRow()->addContent(__('Any students who are not re-enroled will have their status set to "Left".').' '.__($guid, 'Students who are already enroled will have their enrolment updated.'));

                $lastYearGroup = getLastYearGroupID($connection2);

                if (count($yearGroups) < 1 or count($rollGroups) < 1) {
                    $form->addRow()->addAlert(__('Year groups or roll groups are not properly set up, so you cannot proceed with this section.'), 'error');
                } else {
                    try {
                        $dataReenrol = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $lastYearGroup);
                        $sqlReenrol = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRole.name, category, gibbonStudentEnrolment.gibbonYearGroupID, gibbonRollGroupIDNext
                            FROM gibbonPerson
                                JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND NOT gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName";
                        $resultReenrol = $connection2->prepare($sqlReenrol);
                        $resultReenrol->execute($dataReenrol);
                    } catch (PDOException $e) {
                        $form->addRow()->addAlert($e->getMessage(), 'error');
                    }

                    if ($resultEnrol->rowCount() < 1) {
                        $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                    } else {
                        $row = $form->addRow()->addClass('head break');
                            $row->addColumn()->addContent(__('Name'));
                            $row->addColumn()->addContent(__('Primary Role'));
                            $row->addColumn()->addContent(__('Re-Enrol'));
                            $row->addColumn()->addContent(__('Year Group'));
                            $row->addColumn()->addContent(__('Form Group'));

                        $count = 0;
                        while ($rowReenrol = $resultReenrol->fetch()) {
                            $count++;
                            //Check for enrolment in next year
                            try {
                                $dataEnrolmentCheck = array('gibbonPersonID' => $rowReenrol['gibbonPersonID'], 'gibbonSchoolYearID' => $nextYear);
                                $sqlEnrolmentCheck = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                                $resultEnrolmentCheck = $connection2->prepare($sqlEnrolmentCheck);
                                $resultEnrolmentCheck->execute($dataEnrolmentCheck);
                            } catch (PDOException $e) {
                                $form->addRow()->addAlert($e->getMessage(), 'error');
                            }
                            $enrolmentCheckYearGroup = null;
                            $enrolmentCheckRollGroup = null;
                            if ($resultEnrolmentCheck->rowCount() == 1) {
                                $rowEnrolmentCheck = $resultEnrolmentCheck->fetch();
                                $enrolmentCheckYearGroup = $rowEnrolmentCheck['gibbonYearGroupID'];
                                $enrolmentCheckRollGroup = $rowEnrolmentCheck['gibbonRollGroupID'];
                            }

                            $form->addHiddenValue($count."-reenrol-gibbonPersonID", $rowReenrol['gibbonPersonID']);
                            $row = $form->addRow();
                                $row->addColumn()->addContent(formatName('', $rowReenrol['preferredName'], $rowReenrol['surname'], 'Student', true));
                                $row->addColumn()->addContent(__($rowReenrol['name']));
                                $column = $row->addColumn();
                                    $column->addCheckbox($count."-reenrol-enrol")->setValue('Y')->checked('Y');
                                //If no enrolment, try and work out next year and roll group
                                if (is_null($enrolmentCheckYearGroup)) {
                                    $enrolmentCheckYearGroup=getNextYearGroupID($rowReenrol['gibbonYearGroupID'], $connection2);
                                    $enrolmentCheckRollGroup=$rowReenrol['gibbonRollGroupIDNext'];
                                }
                                $column = $row->addColumn();
                                    $column->addSelect($count."-reenrol-gibbonYearGroupID")->fromArray($yearGroups)->isRequired()->setClass('shortWidth floatNone')->selected($enrolmentCheckYearGroup);
                                $column = $row->addColumn();
                                        $column->addSelect($count."-reenrol-gibbonRollGroupID")->fromArray($rollGroups)->isRequired()->setClass('shortWidth floatNone')->selected($enrolmentCheckRollGroup);
                        }
                        $form->addHiddenValue("reenrol-count", $count);
                    }
                }

                //SET FINAL YEAR USERS TO LEFT
                $form->addRow()->addHeading(__('Set Final Year Students To Left'));
                $form->addRow()->addContent(__('This step finds students in the last year of school and sets their status.'));

                try {
                    $dataFinal = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $lastYearGroup);
                    $sqlFinal = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName";
                    $resultFinal = $connection2->prepare($sqlFinal);
                    $resultFinal->execute($dataFinal);
                } catch (PDOException $e) {
                    $form->addRow()->addAlert($e->getMessage(), 'error');
                }
                if ($resultFinal->rowCount() < 1) {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                } else {
                    $row = $form->addRow()->addClass('head break');
                        $row->addColumn()->addContent(__('Name'));
                        $row->addColumn()->addContent(__('Primary Role'));
                        $row->addColumn()->addContent(__('Current Status'));
                        $row->addColumn()->addContent(__('New Status'));

                    $count = 0;
                    while ($rowFinal = $resultFinal->fetch()) {
                        $count++;
                        $form->addHiddenValue($count."-final-gibbonPersonID", $rowFinal['gibbonPersonID']);
                        $row = $form->addRow();
                            $row->addColumn()->addContent(formatName('', $rowFinal['preferredName'], $rowFinal['surname'], 'Student', true));
                            $row->addColumn()->addContent(__($rowFinal['name']));
                            $row->addColumn()->addContent(__('Expected'));
                            $column = $row->addColumn();
                                $column->addSelect($count."-final-status")->fromArray($statuses)->isRequired()->setClass('shortWidth floatNone')->selected('Left');
                    }
                    $form->addHiddenValue("final-count", $count);
                }

                //REGISTER NEW STAFF
                $form->addRow()->addHeading(__('Register New Staff'));
                $form->addRow()->addContent(__('Any staff who are not registered will have their status set to "Left".'));

                try {
                    $dataRegister = array();
                    $sqlRegister = "SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Staff' ORDER BY surname, preferredName";
                    $resultRegister = $connection2->prepare($sqlRegister);
                    $resultRegister->execute($dataRegister);
                } catch (PDOException $e) {
                    $form->addRow()->addAlert($e->getMessage(), 'error');
                }
                if ($resultRegister->rowCount() < 1) {
                    $form->addRow()->addAlert(__('There are no records to display.'), 'warning');
                } else {
                    $row = $form->addRow()->addClass('head break');
                        $row->addColumn()->addContent(__('Name'));
                        $row->addColumn()->addContent(__('Primary Role'));
                        $row->addColumn()->addContent(__('Register'));
                        $row->addColumn()->addContent(__('Type'));
                        $row->addColumn()->addContent(__('Job Title'));

                    $count = 0;
                    while ($rowRegister = $resultRegister->fetch()) {
                        $count++;
                        $form->addHiddenValue($count."-register-gibbonPersonID", $rowRegister['gibbonPersonID']);
                        $row = $form->addRow();
                            $row->addColumn()->addContent(formatName('', $rowRegister['preferredName'], $rowRegister['surname'], 'Student', true));
                            $row->addColumn()->addContent(__($rowRegister['name']));
                            $column = $row->addColumn();
                                $column->addCheckbox($count."-register-enrol")->setValue('Y')->checked('Y');
                            $column = $row->addColumn();
                                $column->addSelect($count."-register-type")->fromArray(array('Teaching' => __('Teaching'), 'Support' => __('Support')))->isRequired()->setClass('shortWidth floatNone');
                            $column = $row->addColumn();
                                $column->addtextField($count."-register-jobTitle")->setClass('shortWidth floatNone')->maxLength(100);
                    }
                    $form->addHiddenValue("register-count", $count);
                }


                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit('Proceed');

                echo $form->getOutput();
            }
        }
    } elseif ($step == 3) {
        $nextYear = $_POST['nextYear'];
        if ($nextYear == '' or $nextYear != getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2)) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            $sequenceNext = $rowNext['sequenceNumber'];
            if ($nameNext == '' or $sequenceNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                echo '<h3>';
                echo __($guid, 'Step 3');
                echo '</h3>';

                //ADD YEAR FOLLOWING NEXT
                if (getNextSchoolYearID($nextYear, $connection2) == false) {
                    //ADD YEAR FOLLOWING NEXT
                    echo '<h4>';
                    echo sprintf(__($guid, 'Add Year Following %1$s'), $nameNext);
                    echo '</h4>';

                    $name = $_POST['nextname'];
                    $status = $_POST['nextstatus'];
                    $sequenceNumber = $_POST['nextsequenceNumber'];
                    $firstDay = dateConvert($guid, $_POST['nextfirstDay']);
                    $lastDay = dateConvert($guid, $_POST['nextlastDay']);

                    if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $firstDay == '' or $lastDay == '') {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed because your inputs were invalid.');
                        echo '</div>';
                    } else {
                        //Check unique inputs for uniqueness
                        try {
                            $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber);
                            $sql = 'SELECT * FROM gibbonSchoolYear WHERE name=:name OR sequenceNumber=:sequenceNumber';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() > 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            //Write to database
                            $fail = false;
                            try {
                                $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'firstDay' => $firstDay, 'lastDay' => $lastDay);
                                $sql = 'INSERT INTO gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                                $fail = true;
                            }
                            if ($fail == false) {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }
                    }
                }

                //Remember year end date of current year before advance
                $dateEnd = $_SESSION[$guid]['gibbonSchoolYearLastDay'];

                //ADVANCE SCHOOL YEAR
                echo '<h4>';
                echo __($guid, 'Advance School Year');
                echo '</h4>';

                //Write to database
                $advance = true;
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "UPDATE gibbonSchoolYear SET status='Past' WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                    $advance = false;
                }
                if ($advance) {
                    $advance2 = true;
                    try {
                        $data = array('gibbonSchoolYearID' => $nextYear);
                        $sql = "UPDATE gibbonSchoolYear SET status='Current' WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.');
                        echo '</div>';
                        $advance2 = false;
                    }
                    if ($advance2) {
                        setCurrentSchoolYear($guid, $connection2);
                        $_SESSION[$guid]['gibbonSchoolYearIDCurrent'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                        $_SESSION[$guid]['gibbonSchoolYearNameCurrent'] = $_SESSION[$guid]['gibbonSchoolYearName'];
                        $_SESSION[$guid]['gibbonSchoolYearSequenceNumberCurrent'] = $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'];

                        echo "<div class='success'>";
                        echo __($guid, 'Advance was successful, you are now in a new academic year!');
                        echo '</div>';

                        //SET EXPECTED USERS TO FULL
                        echo '<h4>';
                        echo __($guid, 'Set Expected Users To Full');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['expect-count'])) {
                            $count = $_POST['expect-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-expect-gibbonPersonID"];
                                $status = $_POST["$i-expect-status"];

                                //Write to database
                                $expected = true;
                                try {
                                    if ($status == 'Full') {
                                        $data = array('status' => $status, 'gibbonPersonID' => $gibbonPersonID, 'dateStart' => $_SESSION[$guid]['gibbonSchoolYearFirstDay']);
                                        $sql = 'UPDATE gibbonPerson SET status=:status, dateStart=:dateStart WHERE gibbonPersonID=:gibbonPersonID';
                                    } elseif ($status == 'Left' or $status == 'Expected') {
                                        $data = array('status' => $status, 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'UPDATE gibbonPerson SET status=:status WHERE gibbonPersonID=:gibbonPersonID';
                                    }
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $expected = false;
                                }
                                if ($expected) {
                                    ++$success;
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s updates failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //ENROL NEW STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Enrol New Students (Status Expected)');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['enrol-count'])) {
                            $count = $_POST['enrol-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-enrol-gibbonPersonID"];
                                $enrol = $_POST["$i-enrol-enrol"];
                                $gibbonYearGroupID = $_POST["$i-enrol-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-enrol-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;
                                    try {
                                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                        $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                    }
                                    if ($enroled) {
                                        ++$success;

                                        try {
                                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        while ($rowFamily = $resultFamily->fetch()) {
                                            try {
                                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                                $sqlFamily2 = 'SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID';
                                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                                $resultFamily2->execute($dataFamily2);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                                try {
                                                    $dataFamily3 = array('gibbonPersonID' => $rowFamily2['gibbonPersonID']);
                                                    $sqlFamily3 = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                                                    $resultFamily3 = $connection2->prepare($sqlFamily3);
                                                    $resultFamily3->execute($dataFamily3);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $ok = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $ok == false;
                                    }
                                    if ($ok = true) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //ENROL NEW STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Enrol New Students (Status Full)');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['enrolFull-count'])) {
                            $count = $_POST['enrolFull-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-enrolFull-gibbonPersonID"].'<br/>';
                                $enrol = $_POST["$i-enrolFull-enrol"];
                                $gibbonYearGroupID = $_POST["$i-enrolFull-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-enrolFull-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;

                                    try {
                                        //Check for enrolment
                                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                    }
                                    if ($enroled) {
                                        if ($result->rowCount() == 0) {
                                            try {
                                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                                $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $enroled = false;
                                            }
                                        } elseif ($result->rowCount() == 1) {
                                            try {
                                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                                $sql = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $enroled = false;
                                            }
                                        } else {
                                            $enroled = false;
                                        }
                                    }

                                    if ($enroled) {
                                        ++$success;
                                        try {
                                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        while ($rowFamily = $resultFamily->fetch()) {
                                            try {
                                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                                $sqlFamily2 = 'SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID';
                                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                                $resultFamily2->execute($dataFamily2);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                                try {
                                                    $dataFamily3 = array('gibbonPersonID' => $rowFamily2['gibbonPersonID']);
                                                    $sqlFamily3 = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                                                    $resultFamily3 = $connection2->prepare($sqlFamily3);
                                                    $resultFamily3->execute($dataFamily3);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $ok = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $ok == false;
                                    }
                                    if ($ok = true) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo  sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //RE-ENROL OTHER STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Re-Enrol Other Students');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['reenrol-count'])) {
                            $count = $_POST['reenrol-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-reenrol-gibbonPersonID"];
                                $enrol = $_POST["$i-reenrol-enrol"];
                                $gibbonYearGroupID = $_POST["$i-reenrol-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-reenrol-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $reenroled = true;
                                    //Check for existing record...if exists, update
                                    try {
                                        $data = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($result->rowCount() != 1 and $result->rowCount() != 0) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    } elseif ($result->rowCount() == 1) {
                                        try {
                                            $data2 = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                            $sql2 = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                            $result2 = $connection2->prepare($sql2);
                                            $result2->execute($data2);
                                        } catch (PDOException $e) {
                                            $reenroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($reenroled) {
                                            ++$success;
                                        }
                                    } elseif ($result->rowCount() == 0) {
                                        //Else, write
                                        try {
                                            $data2 = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                            $sql2 = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                            $result2 = $connection2->prepare($sql2);
                                            $result2->execute($data2);
                                        } catch (PDOException $e) {
                                            $reenroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($reenroled) {
                                            ++$success;
                                        }
                                    }
                                } else {
                                    $reenroled = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($reenroled) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //SET FINAL YEAR STUDENTS TO LEFT
                        echo '<h4>';
                        echo __($guid, 'Set Final Year Students To Left');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['final-count'])) {
                            $count = $_POST['final-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-final-gibbonPersonID"];
                                $status = $_POST["$i-final-status"];

                                //Write to database
                                $left = true;
                                try {
                                    $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd, 'status' => $status);
                                    $sql = 'UPDATE gibbonPerson SET status=:status, dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $left = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($left) {
                                    ++$success;
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s updates failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //REGISTER NEW STAFF
                        echo '<h4>';
                        echo __($guid, 'Register New Staff');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['register-count'])) {
                            $count = $_POST['register-count'];
                        }
                        if ($count == '') {
                            echo "<div class='warning'>";
                            echo __($guid, 'No actions were selected in Step 2, and so no changes have been made.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-register-gibbonPersonID"];
                                $enrol = $_POST["$i-register-enrol"];
                                $type = $_POST["$i-register-type"];
                                $jobTitle = $_POST["$i-register-jobTitle"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;
                                    //Check for existing record
                                    try {
                                        $dataCheck = array('gibbonPersonID' => $gibbonPersonID);
                                        $sqlCheck = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID';
                                        $resultCheck = $connection2->prepare($sqlCheck);
                                        $resultCheck->execute($dataCheck);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultCheck->rowCount() == 0) {
                                        try {
                                            $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle);
                                            $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type, jobTitle=:jobTitle';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $enroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($enroled) {
                                            ++$success;
                                        }
                                    } elseif ($resultCheck->rowCount() == 1) {
                                        try {
                                            $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle);
                                            $sql = 'UPDATE gibbonStaff SET type=:type, jobTitle=:jobTitle WHERE gibbonPersonID=:gibbonPersonID';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $enroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($enroled) {
                                            ++$success;
                                        }
                                    }
                                } else {
                                    $left = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=$gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $left = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($left) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
