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
use Gibbon\Domain\Staff\StaffApplicationFormGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Applications'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $applicationGateway = $container->get(StaffApplicationFormGateway::class);

    // CRITERIA
    $criteria = $applicationGateway->newQueryCriteria()
        ->searchBy($applicationGateway->getSearchableColumns(), $search)
        ->sortBy('gibbonStaffApplicationForm.status')
        ->sortBy(['priority', 'timestamp'], 'DESC')
        ->fromPOST();

    echo '<h4>';
    echo __('Search');
    echo '</h2>';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/applicationForm_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Application ID, preferred, surname'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    echo '<h4>';
    echo __('View');
    echo '</h2>';

    $applications = $applicationGateway->queryApplications($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('applicationsManage', $criteria);

    $table->modifyRows(function($application, $row) {
        // Highlight rows based on status
        if ($application['status'] == 'Accepted') {
            $row->addClass('current');
        } else if ($application['status'] == 'Rejected' || $application['status'] == 'Withdrawn') {
            $row->addClass('error');
        }
        return $row;
    });

    // COLUMNS
    $table->addColumn('gibbonStaffApplicationFormID', __('ID'))
        
        ->format(Format::using('number', 'gibbonStaffApplicationFormID'));

    $table->addColumn('person', __('Applicant'))
        ->description(__('Application Date'))
        ->sortable(['surname', 'preferredName'])
        ->format(function($row) {
            if (!empty($row['gibbonPersonID'])) {
                $output = Format::name('', $row['preferredName'], $row['surname'], 'Staff', true, true);
            } else {
                $output = Format::name('', $row['applicationPreferredName'], $row['applicationSurname'], 'Staff', true, true);
            }
            return $output.'<br/><span class="small emphasis">'.Format::dateTime($row['timestamp']).'</span>';
        });

    $table->addColumn('jobTitle', __('Position'));
    
    $table->addColumn('status', __('Status'))
        
        ->description(__('Milestones'))
        ->format(function($row) {
            $output = '<strong>'.$row['status'].'</strong>';
            if ($row['status'] == 'Pending') {
                $output .= '<br/><span class="small emphasis">'.trim(str_replace(',', '<br/>', $row['milestones'])).'</span>';
            }
            return $output;
        });

    $table->addColumn('priority', __('Priority'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonStaffApplicationFormID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($row, $actions) use ($guid) {
            if ($row['status'] == 'Pending' || $row['status'] == 'Waiting List') {
                $actions->addAction('accept', __('Accept'))
                        ->setIcon('iconTick')
                        ->setURL('/modules/Staff/applicationForm_manage_accept.php');

                $actions->addAction('reject', __('Reject'))
                        ->setIcon('iconCross')
                        ->append('<br/>')
                        ->setURL('/modules/Staff/applicationForm_manage_reject.php');
            }

            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Staff/applicationForm_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Staff/applicationForm_manage_delete.php');
        });

    echo $table->render($applications);
}
