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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Timetable\TimetableDayGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Timetables'), 'tt.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Timetable'));

    $timetableGateway = $container->get(TimetableGateway::class);

    //Check if gibbonTTID and gibbonSchoolYearID specified
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    if ($gibbonTTID == '' || $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $values = $timetableGateway->getTTByID($gibbonTTID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/tt_editProcess.php');

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonTTID', $gibbonTTID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
                $row->addTextField('schoolYear')->maxLength(20)->required()->readonly()->setValue($values['schoolYear']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
                $row->addTextField('name')->maxLength(30)->required();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->maxLength(12)->required();

            $row = $form->addRow();
                $row->addLabel('nameShortDisplay', __('Day Column Name'));
                $row->addSelect('nameShortDisplay')->fromArray(array('Day Of The Week' => __('Day Of The Week'), 'Timetable Day Short Name' => __('Timetable Day Short Name')))->required();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $yearGroupsOptions = $timetableGateway->getNonTimetabledYearGroups($gibbonSchoolYearID, $gibbonTTID);
            $row = $form->addRow();
                $row->addLabel('active', __('Year Groups'))->description(__('Groups not in an active TT this year.'));
                if (empty($yearGroupsOptions)) {
                    $row->addContent('<i>'.__('No year groups available.').'</i>')->addClass('right');
                } else {
                    $gibbonYearGroupIDList = explode(',', $values['gibbonYearGroupIDList']);
                    $checked = array_filter(array_keys($yearGroupsOptions), function ($item) use ($gibbonYearGroupIDList) {
                        return in_array($item, $gibbonYearGroupIDList);
                    });

                    $row->addCheckbox('gibbonYearGroupID')->fromArray($yearGroupsOptions)->checked($checked);
                }
            $form->addHiddenValue('count', count($yearGroupsOptions));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Timetable Days');
            echo '</h2>';

            $timetableDayGateway = $container->get(TimetableDayGateway::class);

            $criteria = $timetableDayGateway->newQueryCriteria(true)
                ->sortBy(['name'])
                ->fromPOST();

            $ttDays = $timetableDayGateway->queryTTDays($criteria, $gibbonTTID);

            // FORM
            $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module')."/tt_editProcessBulk.php?gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID");
            $form->addHiddenValue('address', $session->get('address'));

            // BULK ACTIONS
            $bulkActions = array(
                'Duplicate' => __('Duplicate')
            );
            $col = $form->createBulkActionColumn($bulkActions);
                $col->addSubmit(__('Go'));

            // DATA TABLE
            $table = $form->addRow()->addDataTable('ttEdit', $criteria)->withData($ttDays);

            $table->addMetaData('bulkActions', $col);

            $table->addHeaderAction('edit', __('Edit Timetable by Class'))
                ->setURL('/modules/Timetable Admin/tt_edit_byClass.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->displayLabel()
                ->append('&nbsp;&nbsp;|&nbsp;&nbsp;');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/tt_edit_day_add.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('nameShort', __('Short Name'));
            $table->addColumn('columnName', __('Column'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID')
                ->format(function ($values, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_delete.php');
                });

            $table->addCheckboxColumn('gibbonTTDayIDList', 'gibbonTTDayID');

            echo $form->getOutput();
        }
    }
}
