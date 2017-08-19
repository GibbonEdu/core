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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_IDCards.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student ID Cards').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report allows a user to select a range of students and create ID cards for those students.');
    echo '</p>';

    echo '<h2>';
    echo 'Choose Students';
    echo '</h2>';

    $choices = null;
    if (isset($_POST['gibbonPersonID'])) {
        $choices = $_POST['gibbonPersonID'];
    }

    $form = Form::create('action',  $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/report_students_IDCards.php");

    $form->setFactory(DatabaseFormFactory::create($pdo));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Students'))->description('Use Control, Command and/or Shift to select multiple.');
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array("allStudents" => false, "byName" => true, "byRoll" => true))->isRequired()->placeholder()->selectMultiple()->selected($choices);

    $row = $form->addRow();
        $row->addLabel('file', __('Card Background'))->description('.png or .jpg file, 448 x 268px.');
        $row->addFileUpload('file')
            ->accepts('.jpg,.jpeg,.png')
            ->append('<br/><br/>'.getMaxUpload($guid))
            ->addClass('right');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if (count($choices) > 0) {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlWhere = ' AND (';
            for ($i = 0; $i < count($choices); ++$i) {
                $data[$choices[$i]] = $choices[$i];
                $sqlWhere = $sqlWhere.'gibbonPerson.gibbonPersonID=:'.$choices[$i].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -4);
            $sqlWhere = $sqlWhere.')';
            $sql = "SELECT officialName, image_240, dob, studentID, gibbonPerson.gibbonPersonID, gibbonYearGroup.name AS year, gibbonRollGroup.name AS roll FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo 'There is not data to display in this report';
            echo '</div>';
        } else {
            echo '<p>';
            echo __($guid, 'These cards are designed to be printed to credit-card size, however, they will look bigger on screen. To print in high quality (144dpi) and at true size, save the cards as an image, and print to 50% scale.');
            echo '</p>';

            //Get background image
            $bg = '';
            if (!empty($_FILES['file']['tmp_name'])) {
                $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                // Upload the file, return the /uploads relative path
                $attachment = $fileUploader->uploadFromPost($file, 'Card_BG');

                if (empty($attachment)) {
                    echo '<div class="error">';
                        echo __($guid, 'Your request failed due to an attachment error.');
                        echo ' '.$fileUploader->getLastError();
                    echo '</div>';
                } else {
                    $bg = 'background: url("'.$_SESSION[$guid]['absoluteURL']."/$attachment\") repeat left top #fff;";
                }
            }

            echo "<table class='blank' cellspacing='0' style='width: 100%'>";

            $count = 0;
            $columns = 1;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if ($count % $columns == 0) {
                    echo '<tr>';
                }
                echo "<td style='width:".(100 / $columns)."%; text-align: center; vertical-align: top'>";
                echo "<div style='width: 488px; height: 308px; border: 1px solid black; $bg'>";
                echo "<table class='blank' cellspacing='0' style='width 448px; max-width 448px; height: 268px; max-height: 268px; margin: 45px 10px 10px 10px'>";
                echo '<tr>';
                echo "<td style='padding: 0px ; width: 150px; height: 200px; vertical-align: top' rowspan=5>";
                if ($row['image_240'] == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$row['image_240']) == false) {
                    echo "<img style='width: 150px; height: 200px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_240.jpg'/><br/>";
                } else {
                    echo "<img style='width: 150px; height: 200px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/'.$row['image_240']."'/><br/>";
                }
                echo '</td>';
                echo "<td style='padding: 0px ; width: 18px'></td>";
                echo "<td style='padding: 15px 0 0 0 ; text-align: left; width: 280px; vertical-align: top; font-size: 22px'>";
                echo "<div style='padding: 5px; background-color: rgba(255,255,255,0.3); min-height: 200px'>";
                echo "<div style='font-weight: bold; font-size: 30px'>".$row['officialName'].'</div><br/>';
                echo '<b>'.__($guid, 'DOB')."</b>: <span style='float: right'><i>".dateConvertBack($guid, $row['dob']).'</span><br/>';
                echo '<b>'.$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'ID')."</b>: <span style='float: right'><i>".$row['studentID'].'</span><br/>';
                echo '<b>'.__($guid, 'Year/Roll')."</b>: <span style='float: right'><i>".__($guid, $row['year']).' / '.$row['roll'].'</span><br/>';
                echo '<b>'.__($guid, 'School Year')."</b>: <span style='float: right'><i>".$_SESSION[$guid]['gibbonSchoolYearName'].'</span><br/>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
                echo '</div>';

                echo '</td>';

                if ($count % $columns == ($columns - 1)) {
                    echo '</tr>';
                }
                ++$count;
            }
            for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                echo '<td></td>';
            }

            if ($count % $columns != 0) {
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
