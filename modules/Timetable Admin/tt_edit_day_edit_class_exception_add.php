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

use Gibbon\Domain\Timetable\TimetableDayGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_exception_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $urlParams = [
            'gibbonTTDayID' => $gibbonTTDayID,
            'gibbonTTID' => $gibbonTTID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonTTColumnRowID' => $gibbonTTColumnRowID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
        ];

        $page->breadcrumbs
            ->add(__('Manage Timetables'), 'tt.php', $urlParams)
            ->add(__('Edit Timetable'), 'tt_edit.php', $urlParams)
            ->add(__('Edit Timetable Day'), 'tt_edit_day_edit.php', $urlParams)
            ->add(__('Classes in Period'), 'tt_edit_day_edit_class.php', $urlParams)
            ->add(__('Class List Exception'), 'tt_edit_day_edit_class_exception.php', $urlParams)
            ->add(__('Add Exception'));

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowClassByID($gibbonTTDayID, $gibbonTTColumnRowID, $gibbonCourseClassID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $gibbonTTDayRowClassID = $values['gibbonTTDayRowClassID'];

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
                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonCourseClassPerson.role
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
                $participants[$rowSelect['gibbonPersonID']] = Format::name('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.__($rowSelect['role'].')');
            }

            $row = $form->addRow();
                $row->addLabel('Members', __('Participants'));
                $row->addSelect('Members')->fromArray($participants)->selectMultiple()->required()->setSize(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
