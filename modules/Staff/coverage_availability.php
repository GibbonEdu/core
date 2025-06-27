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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Services\Format;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Module\Staff\Tables\CoverageCalendar;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_availability.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    if (isActionAccessible($guid, $connection2, '/modules/Staff/substitutes_manage.php')) {
        $page->breadcrumbs
            ->add(__('Manage Substitutes'), 'substitutes_manage.php')
            ->add(__('Edit Availability'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID');

        // Display the details for who's availability we're editing
        $form = Form::create('userInfo', '#');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $row = $form->addRow();
            $row->addLabel('personLabel', __('Person'));
            $row->addSelectUsers('person')->readonly()->selected($gibbonPersonID);

        echo $form->getOutput();
    } else {
        $page->breadcrumbs
            ->add(__('My Coverage'), 'coverage_my.php')
            ->add(__('Edit Availability'));

        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    if (empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $substituteGateway = $container->get(SubstituteGateway::class);
    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    $page->return->addReturns([
            'success1' => __('Your request was completed successfully.').' '.__('You may now continue by submitting a coverage request for this absence.')
        ]);

    $criteria = $staffCoverageGateway->newQueryCriteria();

    $coverage = $staffCoverageGateway->queryCoverageByPersonCovering($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, false);
    $exceptions = $substituteGateway->queryUnavailableDatesBySub($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);
    $schoolYear = $schoolYearGateway->getSchoolYearByID($session->get('gibbonSchoolYearID'));

    // CALENDAR VIEW
    $calendar = CoverageCalendar::create($coverage->toArray(), $exceptions->toArray(), $schoolYear['firstDay'], $schoolYear['lastDay']);
    echo $calendar->getOutput().'<br/>';

    // BULK ACTIONS
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Staff/coverage_availability_deleteProcess.php');
    $form->setTitle(__('Dates'));
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

    // DATA TABLE
    $criteria = $substituteGateway->newQueryCriteria(true)
        ->sortBy('date')
        ->fromPOST();

    $dates = $substituteGateway->queryUnavailableDatesBySub($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID);

    $bulkActions = array(
        'Delete' => __('Delete'),
    );

    $col = $form->createBulkActionColumn($bulkActions);
    $col->addSubmit(__('Go'));

    $table = $form->addRow()->addDataTable('staffAvailabilityExceptions', $criteria)->withData($dates);

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('date', __('Date'))
        ->format(Format::using('dateReadable', 'date'));

    $table->addColumn('timeStart', __('Time'))->format(function ($date) {
        if ($date['allDay'] == 'N') {
            return Format::timeRange($date['timeStart'], $date['timeEnd']);
        } else {
            return __('All Day');
        }
    });

    $table->addColumn('reason', __('Reason'))
        ->format(function ($date) {
            return !empty($date['reason'])
                ? __($date['reason'])
                : Format::small(__('Not Available'));
        });

    $table->addActionColumn()
        ->addParam('gibbonPersonID', $gibbonPersonID)
        ->addParam('gibbonStaffCoverageDateID')
        ->format(function ($date, $actions) {
            $actions->addAction('deleteInstant', __('Delete'))
                    ->setIcon('garbage')
                    ->isDirect()
                    ->setURL('/modules/Staff/coverage_availability_deleteProcess.php')
                    ->addConfirmation(__('Are you sure you wish to delete this record?'));
        });

    $table->addCheckboxColumn('gibbonStaffCoverageDateID');

    echo $form->getOutput();


    $form = Form::create('staffAvailability', $session->get('absoluteURL').'/modules/Staff/coverage_availability_addProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

    $form->addRow()->addHeading('Add', __('Add'));

    $row = $form->addRow();
    $row->addLabel('allDay', __('All Day'));
    $row->addYesNoRadio('allDay')->checked('Y');

    $form->toggleVisibilityByClass('timeOptions')->onRadio('allDay')->when('N');

    $date = $_GET['date'] ?? '';
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->isRequired()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->setValue($date);

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Start Time'));
        $row->addTime('timeStart')->isRequired();

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeEnd', __('End Time'));
        $row->addTime('timeEnd')->chainedTo('timeStart')->isRequired();

    $row = $form->addRow();
        $row->addLabel('reason', __('Reason'))->description(__('Optional'));
        $row->addTextField('reason')->maxLength(255);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
