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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Timetable\CourseClassGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Timetable\CourseGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonCourseID, gibbonSchoolYearID, and gibbonCourseClassID specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $search = $_GET['search'] ?? '';
    $urlParams = compact('gibbonSchoolYearID', 'search');


    if (empty($gibbonCourseID) or empty($gibbonSchoolYearID) or empty($gibbonCourseClassID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $userGateway = $container->get(UserGateway::class);
        $courseGateway = $container->get(CourseGateway::class);
        $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);
        $courseClassGateway = $container->get(CourseClassGateway::class);

        $values = $courseGateway->getCourseClassByID($gibbonCourseClassID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $page->breadcrumbs
            ->add(__('Manage Courses & Classes'), 'course_manage.php', $urlParams)
            ->add(__('Edit Course & Classes'), 'course_manage_edit.php', $urlParams + ['gibbonCourseID' => $gibbonCourseID])
            ->add(__('Edit Exception'));

            //Report minimum/maximum enrolment messages
            if (is_numeric($values['enrolmentMin']) && $values['studentsTotal'] < $values['enrolmentMin']) {
                $page->addWarning(__('This class is currently under enrolled, based on a minimum enrolment of {enrolmentMin} students.', ['enrolmentMin' => $values['enrolmentMin']]));
            }
            if (is_numeric($values['enrolmentMax']) && $values['studentsTotal'] > $values['enrolmentMax']) {
                $page->addError(__('This class is currently over enrolled, based on a maximum enrolment of {enrolmentMax} students.', ['enrolmentMax' => $values['enrolmentMax']]));
            }

            if (!empty($search)) {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Timetable Admin', 'course_manage.php')->withQueryParams($urlParams));
            }
            echo '<h2>';
            echo __('Add Exceptions');
            echo '</h2>';

            $form = Form::create('manageException', $session->get('absoluteURL').'/modules/'.$session->get('module')."/courseException_manage_class_editProcessBulk.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            $form->addHiddenValue('search', $search);

            $people = array();

            $participants = array();
            try {
                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname , gibbonCourseClassPerson.role
                    FROM gibbonPerson
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        LEFT JOIN gibbonCourseClassSlotException ON (gibbonCourseClassSlotException.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID
                        AND NOT role='Student - Left'
                        AND NOT role='Teacher - Left'
                        AND NOT gibbonPerson.status='Left'
                    ORDER BY surname, preferredName";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                $participants[$rowSelect['gibbonPersonID']] = Format::name('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true) . ' (' . __($rowSelect['role'] . ')');
            }

            $row = $form->addRow();
            $row->addLabel('Members', __('Participants'));
            $row->addSelect('Members')->fromArray($participants)->selectMultiple()->required()->setSize(8);

            $slots = array();
            try{
                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $query = "SELECT gibbonCourseClassSlot.gibbonCourseClassSlotID, gibbonCourseClassSlot.timeStart, gibbonCourseClassSlot.timeEnd, gibbonDaysOfWeek.name, gibbonDaysOfWeek.nameShort FROM gibbonCourseClassSlot 
                LEFT JOIN gibbonDaysOfWeek ON gibbonCourseClassSlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID  WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonDaysOfWeek.sequenceNumber, timeStart";
                $resultSlots = $connection2->prepare($query);
                $resultSlots->execute($dataSelect);
            } catch (PDOException $e) {
            }

            while ($rowSelect = $resultSlots->fetch()) {
                $slots[$rowSelect['gibbonCourseClassSlotID']] = $rowSelect['name']. "  " . substr($rowSelect['timeStart'], 0 ,5).' - '.substr($rowSelect['timeEnd'], 0, 5);
            }
            
            $row = $form->addRow();
                $row->addLabel('slot', __('Slot'));
                $row->addSelect('slot')->fromArray($slots)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            $courseClassSlotExceptions = $courseClassGateway->selectCourseClassExceptionsByID($gibbonCourseClassID);

            $table = DataTable::create('timetableDayRowClassExceptions');
            $table->setTitle(__('Exception List'));
            $table->addColumn('name2', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));

            $table->addColumn('name', __('Day'));
            $table->addColumn('slot', __('Time Slot'));

            // ACTIONS
            $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonCourseID', $gibbonCourseID)
            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
            ->addParam('gibbonCourseClassSlotExceptionID')
            ->format(function ($values, $actions) {
                $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Timetable Admin/courseException_manage_class_delete.php');
            });

            echo $table->render($courseClassSlotExceptions->toDataSet());

        }
    }
}
