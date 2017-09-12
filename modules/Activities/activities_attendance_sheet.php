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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance_sheet.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Printable Attendance Sheet').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Activity');
    echo '</h2>';

    $gibbonActivityID = null;
    if (isset($_GET['gibbonActivityID'])) {
        $gibbonActivityID = $_GET['gibbonActivityID'];
    }

    $numberOfColumns = (isset($_GET['numberOfColumns']) && $_GET['numberOfColumns'] <= 20 ) ? $_GET['numberOfColumns'] : 20;

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_attendance_sheet.php");

    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonActivityID AS value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
    $row = $form->addRow();
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->isRequired()->placeholder();

    $row = $form->addRow();
        $row->addLabel('numberOfColumns', __('Number of Columns'));
        $row->addNumber('numberOfColumns')->decimalPlaces(0)->maximum(20)->maxLength(2)->setValue($numberOfColumns)->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit(__('Go'))->prepend(sprintf('<a href="%s" class="right">%s</a> &nbsp;', $_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q'], __('Clear Form')));

    echo $form->getOutput();

    if ($gibbonActivityID != '') {
        $output = '';
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/activities_attendance_sheetPrint.php&gibbonActivityID=$gibbonActivityID&columns=$numberOfColumns'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            $lastPerson = '';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo "<th colspan=$numberOfColumns>";
            echo __($guid, 'Attendance');
            echo '</th>';
            echo '</tr>';
            echo "<tr style='height: 75px' class='odd'>";
            echo "<td style='vertical-align:top; width: 120px'>Date</td>";
            for ($i = 1; $i <= $numberOfColumns; ++$i) {
                echo "<td style='color: #bbb; vertical-align:top; width: 15px'>$i</td>";
            }
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
                echo '<td>';
                echo $count.'. '.formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                for ($i = 1; $i <= $numberOfColumns; ++$i) {
                    echo '<td></td>';
                }
                echo '</tr>';

                $lastPerson = $row['gibbonPersonID'];
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=16>';
                echo __($guid, 'There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
