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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Timetable\TimetableDayGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_master.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View Master Timetable'));

    echo '<h2>';
    echo __('Choose Timetable');
    echo '</h2>';

    $gibbonTTID = null;
    if (isset($_GET['gibbonTTID'])) {
        $gibbonTTID = $_GET['gibbonTTID'];
    }
    if ($gibbonTTID == null) { //If TT not set, get the first timetable in the current year, and display that
        
            $dataSelect = array();
            $sqlSelect = "SELECT gibbonTTID FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' ORDER BY gibbonTT.name LIMIT 0, 1";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        if ($resultSelect->rowCount() == 1) {
            $rowSelect = $resultSelect->fetch();
            $gibbonTTID = $rowSelect['gibbonTTID'];
        }
    }

    $form = Form::create('ttMaster', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/tt_master.php');

    $sql = "SELECT gibbonSchoolYear.name as groupedBy, gibbonTTID as value, gibbonTT.name AS name FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYear.sequenceNumber, gibbonTT.name";
    $result = $pdo->executeQuery(array(), $sql);

    // Transform into an option list grouped by Year
    $ttList = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
    $ttList = array_reduce($ttList, function($list, $item) {
        $list[$item['groupedBy']][$item['value']] = $item['name'];
        return $list;
    }, array());

    $row = $form->addRow();
        $row->addLabel('gibbonTTID', __('Timetable'));
        $row->addSelect('gibbonTTID')->fromArray($ttList)->required()->selected($gibbonTTID);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);


    echo $form->getOutput();

    if ($gibbonTTID != '') {

        $timetableGateway = $container->get(TimetableGateway::class);
        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        
        $values = $timetableGateway->getTTByID($gibbonTTID);
        $ttDays = $timetableDayGateway->selectTTDaysByID($gibbonTTID)->fetchAll();

        if (empty($values) || empty($ttDays)) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            foreach ($ttDays as $ttDay) {
                echo '<h2 style="margin-top: 40px">';
                echo __($ttDay['name']);
                echo '</h2>';

                $ttDayRows = $timetableDayGateway->selectTTDayRowsByID($ttDay['gibbonTTDayID'])->fetchAll();
                
                foreach ($ttDayRows as $ttDayRow) {
                    echo '<h5 style="margin-top: 25px">';
                    echo __($ttDayRow['name']).'<span style=\'font-weight: normal\'> ('.Format::timeRange($ttDayRow['timeStart'], $ttDayRow['timeEnd']).')</span>';
                    echo '</h5>';

                    $ttDayRowClasses = $timetableDayGateway->selectTTDayRowClassesByID($ttDay['gibbonTTDayID'], $ttDayRow['gibbonTTColumnRowID']);

                    if ($ttDayRowClasses->isEmpty()) {
                        echo '<div class="warning">';
                        echo __('There are no classes associated with this period on this day.');
                        echo '</div>';
                    } else {
                        $table = DataTable::create('timetableDayRowClasses');

                        $table->modifyRows(function ($data, $row) {
                            return $row->addClass('compactRow');
                        });

                        $table->addColumn('class', __('Class'))->format(Format::using('courseClassName', ['courseName', 'className']));
                        $table->addColumn('location', __('Location'));
                        $table->addColumn('teachers', __('Teachers'))->format(function($class) use ($timetableDayGateway) {
                            $teachers = $timetableDayGateway->selectTTDayRowClassTeachersByID($class['gibbonTTDayRowClassID'])->fetchAll();
                            return Format::nameList($teachers, 'Staff', false, true);
                        });

                        echo $table->render($ttDayRowClasses->toDataSet());
                    }
                }
            }
        }
    }
}

