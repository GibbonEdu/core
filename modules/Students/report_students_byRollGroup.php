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
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byRollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonRollGroupID = (isset($_GET['gibbonRollGroupID']) ? $_GET['gibbonRollGroupID'] : null);
    $view = (isset($_GET['view']) ? $_GET['view'] : 'Basic');

    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Students by Roll Group').'</div>';
        echo '</div>';

        echo '<h2>';
        echo __($guid, 'Choose Roll Group');
        echo '</h2>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_students_byRollGroup.php");

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'], true)->selected($gibbonRollGroupID)->placeholder()->isRequired();

        $row = $form->addRow();
            $row->addLabel('view', __('View'));
            $row->addSelect('view')->fromArray(array('basic' => __('Basic'), 'Extended' =>__('Extended')))->selected($view)->isRequired();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    if ($gibbonRollGroupID != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        if ($gibbonRollGroupID != '*') {
            try {
                $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
                $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                echo "<p style='margin-bottom: 0px'><b>".__($guid, 'Roll Group').'</b>: '.$row['name'].'</p>';

                //Show Tutors
                try {
                    $dataDetail = array('gibbonPersonIDTutor' => $row['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $row['gibbonPersonIDTutor3']);
                    $sqlDetail = 'SELECT title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultDetail->rowCount() > 0) {
                    $tutorCount = 0;
                    echo "<p style=''><b>".__($guid, 'Tutors').'</b>: ';
                    while ($rowDetail = $resultDetail->fetch()) {
                        echo formatName($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                        ++$tutorCount;
                        if ($tutorCount < $resultDetail->rowCount()) {
                            echo ', ';
                        }
                    }
                    echo '</p>';
                }
            }
        }

        try {
            if ($gibbonRollGroupID == '*') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonRollGroup.nameShort, surname, preferredName";
            } else {
                $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        $studentGateway = $container->get(StudentGateway::class);

        // QUERY
        $criteria = $studentGateway->newQueryCriteria()
            ->sortBy(['rollGroup', 'surname', 'preferredName'])
            ->pageSize(!empty($viewMode) ? 0 : 50)
            ->fromArray($_POST);

        
        if ($gibbonRollGroupID != '*') {
            $attendanceCodes = $studentGateway->queryStudentEnrolmentByRollGroup($criteria, $gibbonRollGroupID);
        }
        else {
            $attendanceCodes = $studentGateway->queryStudentEnrolmentByRollGroup($criteria); 
        }

        // DATA TABLE
        $table = ReportTable::createPaginated('studentsByRollGroup', $criteria)->setViewMode($viewMode, $gibbon->session);;

        $table->addColumn('rollGroup', __('Roll Group'));
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) {
                return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
            });
        if ($view == "Extended") {
            $table->addColumn('gender', __('Gender'));
            $table->addColumn('name', __('Age').'<br/>'.Format::small('DOB'))
                ->format(function($values) use ($guid) {
                    if (!empty($values['dob'])) {
                        return '<strong>'.getAge($guid, dateConvertToTimestamp($values['dob'])).'</strong><br/>'.Format::date($values['dob']);
                    }
                });
            $table->addColumn('citizenship1', __('Nationality'))
            ->format(function($values) {
                $citizenship = '';
                if (!empty($values['citizenship1'])) {
                    $citizenship .= $values['citizenship1']."<br/>";
                }
                if (!empty($values['citizenship2'])) {
                    $citizenship .= $values['citizenship2']."<br/>";
                }
                return $citizenship;
            });
            $table->addColumn('transport', __('Transport'));
            $table->addColumn('house', __('House'));
            $table->addColumn('lockerNumber', __('Locker'));
            $table->addColumn('medical', __('Medical'));
        }
        
        echo $table->render($attendanceCodes);

        // echo '<td>';
        // try {
        //     $dataForm = array('gibbonPersonID' => $row['gibbonPersonID']);
        //     $sqlForm = 'SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID';
        //     $resultForm = $connection2->prepare($sqlForm);
        //     $resultForm->execute($dataForm);
        // } catch (PDOException $e) {
        //     echo "<div class='error'>".$e->getMessage().'</div>';
        // }

        // if ($resultForm->rowCount() == 1) {
        //     $rowForm = $resultForm->fetch();
        //     if ($rowForm['longTermMedication'] == 'Y') {
        //         echo '<b><i>'.__($guid, 'Long Term Medication').'</i></b>: '.$rowForm['longTermMedicationDetails'].'<br/>';
        //     }
        //     $condCount = 1;
        //     try {
        //         $dataConditions = array('gibbonPersonMedicalID' => $rowForm['gibbonPersonMedicalID']);
        //         $sqlConditions = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
        //         $resultConditions = $connection2->prepare($sqlConditions);
        //         $resultConditions->execute($dataConditions);
        //     } catch (PDOException $e) {
        //         echo "<div class='error'>".$e->getMessage().'</div>';
        //     }

        //     while ($rowConditions = $resultConditions->fetch()) {
        //         echo '<b><i>'.__($guid, 'Condition')." $condCount</i></b> ";
        //         echo ': '.__($guid, $rowConditions['name']);

        //         $alert = getAlert($guid, $connection2, $rowConditions['gibbonAlertLevelID']);
        //         if ($alert != false) {
        //             echo " <span style='color: #".$alert['color']."; font-weight: bold'>(".__($guid, $alert['name']).' '.__($guid, 'Risk').')</span>';
        //             echo '<br/>';
        //             ++$condCount;
        //         }
        //     }
        // } else {
        //     echo '<i>'.__($guid, 'No medical data').'</i>';
        // }
        // echo '</td>';
    }
}
?>
