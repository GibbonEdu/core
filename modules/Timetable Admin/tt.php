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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\TimetableGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Timetables').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
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
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
			echo ' | ';
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
				echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
			} else {
				echo __($guid, 'Next Year').' ';
			}
        echo '</div>';


        $timetableGateway = $container->get(TimetableGateway::class);
        $timetables = $timetableGateway->selectTimetablesBySchoolYear($gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::create('timetables');

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable Admin/tt_add.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->displayLabel();

        $table->modifyRows(function ($tt, $row) {
            if ($tt['active'] == 'N') $row->addClass('error');
            return $row;
        });

        $table->addColumn('name', __('Name'));
        $table->addColumn('nameShort', __('Short Name'));
        $table->addColumn('yearGroups', __('Year Groups'));
        $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonTTID')
            ->addParam('gibbonSchoolYearID')
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Timetable Admin/tt_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Timetable Admin/tt_delete.php');

                $actions->addAction('import', __('Import'))
                    ->setIcon('upload')
                    ->setURL('/modules/Timetable Admin/tt_import.php');
            });

        echo $table->render($timetables->toDataSet());
    }
}
