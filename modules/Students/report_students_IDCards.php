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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_IDCards.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Student ID Cards'));

    echo '<p>';
    echo __('This report allows a user to select a range of students and create ID cards for those students.');
    echo '</p>';

    echo '<h2>';
    echo 'Choose Students';
    echo '</h2>';

    $choices = $_POST['gibbonPersonID'] ?? [];

    $form = Form::create('action',  $session->get('absoluteURL')."/index.php?q=/modules/Students/report_students_IDCards.php");

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), array("allStudents" => false, "byName" => true, "byForm" => true))->required()->placeholder()->selectMultiple()->selected($choices);

    $row = $form->addRow();
        $row->addLabel('file', __('Card Background'))->description('.png or .jpg file, 448 x 268px.');
        $row->addFileUpload('file')
            ->accepts('.jpg,.jpeg,.png');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if (count($choices) > 0) {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sqlWhere = ' AND (';
            for ($i = 0; $i < count($choices); ++$i) {
                $data["choice$i"] = preg_replace('/[^0-9]/', '', $choices[$i]);
                $sqlWhere = $sqlWhere."gibbonPerson.gibbonPersonID=:choice$i OR ";
            }
            $sqlWhere = substr($sqlWhere, 0, -4);
            $sqlWhere = $sqlWhere.')';
            $sql = "SELECT officialName, image_240, dob, studentID, gibbonPerson.gibbonPersonID, gibbonYearGroup.name AS year, gibbonFormGroup.name AS form FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo 'There is not data to display in this report';
            echo '</div>';
        } else {
            echo '<p>';
            echo __('These cards are designed to be printed to credit-card size, however, they will look bigger on screen. To print in high quality (144dpi) and at true size, save the cards as an image, and print to 50% scale.');
            echo '</p>';

            //Get background image
            $bg = '';
            if (!empty($_FILES['file']['tmp_name'])) {
                $fileUploader = new Gibbon\FileUploader($pdo, $session);

                $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                // Upload the file, return the /uploads relative path
                $attachment = $fileUploader->uploadFromPost($file, 'Card_BG');

                if (empty($attachment)) {
                    echo '<div class="error">';
                        echo __('Your request failed due to an attachment error.');
                        echo ' '.$fileUploader->getLastError();
                    echo '</div>';
                } else {
                    $bg = 'background: url("'.$session->get('absoluteURL')."/$attachment\") no-repeat left top #fff; background-size: cover;";
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
                if ($row['image_240'] == '' or file_exists($session->get('absolutePath').'/'.$row['image_240']) == false) {
                    echo "<img style='width: 150px; height: 200px' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240.jpg'/><br/>";
                } else {
                    echo "<img style='width: 150px; height: 200px' class='user' src='".$session->get('absoluteURL').'/'.$row['image_240']."'/><br/>";
                }
                echo '</td>';
                echo "<td style='padding: 0px ; width: 18px'></td>";
                echo "<td style='padding: 15px 0 0 0 ; text-align: left; width: 280px; vertical-align: top; font-size: 22px'>";
                echo "<div style='padding: 5px; background-color: rgba(255,255,255,0.3); min-height: 200px'>";
                $nameLength = strlen($row['officialName']);
                switch ($nameLength) {
                    case $nameLength >= 30:  $size = 16; break;
                    case $nameLength >= 25:  $size = 20; break;
                    case $nameLength >= 20:  $size = 24; break;
                    default: $size = 26;
                }

                echo "<div style='font-weight: bold; font-size: ".$size."px !important;'>".$row['officialName'].'</div><br/>';
                echo '<b>'.__('DOB')."</b>: <span style='float: right'><i>".Format::date($row['dob']).'</span><br/>';
                echo '<b>'.$session->get('organisationNameShort').' '.__('ID')."</b>: <span style='float: right'><i>".$row['studentID'].'</span><br/>';
                echo '<b>'.__('Year/Form')."</b>: <span style='float: right'><i>".__($row['year']).' / '.$row['form'].'</span><br/>';
                echo '<b>'.__('School Year')."</b>: <span style='float: right'><i>".$session->get('gibbonSchoolYearName').'</span><br/>';
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
