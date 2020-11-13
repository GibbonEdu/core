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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Attendance\StudentHistoryData;
use Gibbon\Module\Attendance\StudentHistoryView;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Student History'));
    $page->scripts->add('chart');

    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {

        $canTakeAttendanceByPerson = isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php');
        $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

        if ($highestAction == 'Student History_all') {
            echo '<h2>';
            echo __('Choose Student');
            echo '</h2>';

            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_studentHistory.php");

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelectStudent('gibbonPersonID', $gibbonSchoolYearID)->selected($gibbonPersonID)->placeholder()->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSearchSubmit($gibbon->session);

            echo $form->getOutput();

            if ($gibbonPersonID != '') {
                $output = '';
                echo '<h2>';
                echo __('Report Data');
                echo '</h2>';

                
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The specified record does not exist.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    // ATTENDANCE DATA
                    $attendanceData = $container
                        ->get(StudentHistoryData::class)
                        ->getAttendanceData($gibbonSchoolYearID, $gibbonPersonID, $row['dateStart'], $row['dateEnd']);

                    // DATA TABLE
                    $renderer = $container->get(StudentHistoryView::class);
                    $renderer->addData('canTakeAttendanceByPerson', $canTakeAttendanceByPerson);

                    $table = DataTable::create('studentHistory', $renderer);
                    $table->addHeaderAction('print', __('Print'))
                        ->setURL('/report.php')
                        ->addParam('q', '/modules/Attendance/report_studentHistory_print.php')
                        ->addParam('gibbonPersonID', $gibbonPersonID)
                        ->addParam('viewMode', 'print')
                        ->setIcon('print')
                        ->setTarget('_blank')
                        ->directLink()
                        ->displayLabel();

                    echo $table->render($attendanceData);
                }
            }
        }
        else if ($highestAction == 'Student History_myChildren') {
            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }
            //Test data access field for permission
            
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('Access denied.');
                echo '</div>';
            } else {
                //Get child list
                $countChild = 0;
                $options = [];
                while ($row = $result->fetch()) {
                    
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    if ($resultChild->rowCount() > 0) {
                        if ($resultChild->rowCount() == 1) {
                            $rowChild = $resultChild->fetch();
                            $gibbonPersonID = $rowChild['gibbonPersonID'];
                            $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                            ++$countChild;
                        }
                        else {
                            while ($rowChild = $resultChild->fetch()) {
                                $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                                ++$countChild;
                            }
                        }
                    }
                }

                if ($countChild == 0) {
                    echo "<div class='error'>";
                    echo __('Access denied.');
                    echo '</div>';
                } else {
                    echo '<h2>';
                    echo __('Choose');
                    echo '</h2>';

                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->setClass('noIntBorder fullWidth');

                    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_studentHistory.php");

                    if ($countChild > 0) {
                        $row = $form->addRow();
                            $row->addLabel('gibbonPersonID', __('Child'));
                            if ($countChild > 1) {
                                $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder()->required();
                            }
                            else {
                                $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->required();
                            }
                    }

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSearchSubmit($gibbon->session);

                    echo $form->getOutput();
                }

                if ($gibbonPersonID != '' and $countChild > 0) {
                    //Confirm access to this student
                    
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        @$resultChild->execute($dataChild);

                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __('The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $rowChild = $resultChild->fetch();

                        if ($gibbonPersonID != '') {
                            $output = '';
                            echo '<h2>';
                            echo __('Report Data');
                            echo '</h2>';

                            
                                $data = array('gibbonPersonID' => $gibbonPersonID);
                                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            if ($result->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __('The specified record does not exist.');
                                echo '</div>';
                            } else {
                                $row = $result->fetch();

                                // ATTENDANCE DATA
                                $attendanceData = $container
                                    ->get(StudentHistoryData::class)
                                    ->getAttendanceData($gibbonSchoolYearID, $gibbonPersonID, $row['dateStart'], $row['dateEnd']);

                                // DATA TABLE
                                $renderer = $container->get(StudentHistoryView::class);
                                $table = DataTable::create('studentHistory', $renderer);
                                echo $table->render($attendanceData);
                            }
                        }
                    }
                }
            }
        }
        else if ($highestAction == 'Student History_my') {
            $output = '';
            echo '<h2>';
            echo __('Report Data');
            echo '</h2>';

            
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The specified record does not exist.');
                echo '</div>';
            } else {
                $row = $result->fetch();

                // ATTENDANCE DATA
                $attendanceData = $container
                    ->get(StudentHistoryData::class)
                    ->getAttendanceData($gibbonSchoolYearID, $_SESSION[$guid]['gibbonPersonID'], $row['dateStart'], $row['dateEnd']);

                // DATA TABLE
                $renderer = $container->get(StudentHistoryView::class);
                $table = DataTable::create('studentHistory', $renderer);
                echo $table->render($attendanceData);
            }
        }
    }
}
?>
