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
use Gibbon\Domain\Timetable\TimetableDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_exception_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'];
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'];
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowClassByID($gibbonTTDayID, $gibbonTTColumnRowID, $gibbonCourseClassID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $gibbonTTDayRowClassID = $values['gibbonTTDayRowClassID'];

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > ... > ... > ... > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Edit Timetable Day')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit_class.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID'>".__($guid, 'Classes in Period')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit_class_exception.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Class List Exception')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Exception').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/tt_edit_day_edit_class_exception_addProcess.php?gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID");

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonTTID', $gibbonTTID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $participants = array();
            try {
                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname
                    FROM gibbonPerson
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonTTDayRowClassException.gibbonTTDayRowClassID=:gibbonTTDayRowClassID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID
                        AND NOT role='Student - Left'
                        AND NOT role='Teacher - Left'
                        AND NOT gibbonPerson.status='Left'
                        AND gibbonTTDayRowClassExceptionID IS NULL
                    ORDER BY surname, preferredName";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { echo $e->getMessage();}
            while ($rowSelect = $resultSelect->fetch()) {
                $participants[$rowSelect['gibbonPersonID']] = formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true);
            }

            $row = $form->addRow();
                $row->addLabel('Members', __('Participants'));
                $row->addSelect('Members')->fromArray($participants)->selectMultiple()->isRequired()->setSize(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
