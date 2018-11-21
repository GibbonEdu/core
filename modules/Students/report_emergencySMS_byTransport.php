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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_emergencySMS_byTransport.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Emergency SMS by Transport'));

    echo '<p>';
    echo __('This report prints all parent mobile phone numbers, whether or not they are set to receive messages from the school. It is useful when sending emergency SMS messages to groups of students. If no parent mobile is available it will display the emergency numbers given in the student record, and this will appear in red.');
    echo '</p>';

    echo '<h2>';
    echo 'Choose Transport Group';
    echo '</h2>';

    $transport = null;
    if (isset($_GET['transport'])) {
        $transport = $_GET['transport'];
    }
    $prefix = null;
    if (isset($_GET['prefix'])) {
        $prefix = $_GET['prefix'];
    }
    $append = null;
    if (isset($_GET['append'])) {
        $append = $_GET['append'];
    }
    $hideName = null;
    if (isset($_GET['hideName'])) {
        $hideName = $_GET['hideName'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', "get");

    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_emergencySMS_byTransport.php", "get");

    $row = $form->addRow();
        $row->addLabel('transport', __('Transport'));
        $row->addSelectTransport('transport', true)->isRequired()->selected($transport);

    $row = $form->addRow();
        $row->addLabel('prefix', __('Prefix'));
        $row->addTextField('prefix')->setValue($prefix)->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('append', __('Suffix'));
        $row->addTextField('append')->setValue($append)->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('hideName', __('Hide Student Name?'));
        $row->addYesNo('hideName')->selected($hideName);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($transport != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        try {
            if ($transport == '*') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, emergency1Number1, emergency2Number1 FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'transport' => $transport);
                $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, emergency1Number1, emergency2Number1 FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND transport=:transport AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        if ($hideName == 'N') {
            echo '<th>';
            echo 'Student';
            echo '</th>';
        }
        echo '<th>';
        echo 'Parent Mobile Numbers';
        echo '</th>';

        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            if ($hideName == 'N') {
                echo '<td>';
                echo Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
            }
            echo '<td>';
            try {
                $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlFamily = "SELECT gibbonPerson.* FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID) AND (phone1Type='Mobile' OR phone2Type='Mobile' OR phone3Type='Mobile' OR phone4Type='Mobile') AND status='Full'";
                $resultFamily = $connection2->prepare($sqlFamily);
                $resultFamily->execute($dataFamily);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultFamily->rowCount() > 0) {
                while ($rowFamily = $resultFamily->fetch()) {
                    for ($i = 1; $i < 5; ++$i) {
                        if ($rowFamily['phone'.$i] != '' and $rowFamily['phone'.$i.'Type'] == 'Mobile') {
                            echo $prefix.preg_replace('/\s+/', '', $rowFamily['phone'.$i]).$append.'<br/>';
                        }
                    }
                }
            } else {
                echo "<span style='color: #c00'>".$prefix.preg_replace('/\s+/', '', $row['emergency1Number1']).$append.'</span><br/>';
                echo "<span style='color: #c00'>".$prefix.preg_replace('/\s+/', '', $row['emergency2Number1']).$append.'</span><br/>';
            }
            echo '</td>';

            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=2>';
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
