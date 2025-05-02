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
use Gibbon\Module\FreeLearning\Domain\MentorGroupGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorGroups_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__m('Manage Mentor Groups'));

    $search = $_GET['search'] ?? '';

    echo '<p>'.__m('This page allows you to create groups of mentors, which can be assigned to students manually or automatically based on a Custom Field value. Students with a mentor group will choose from that list rather than the mentors available for a particular unit.').'</p>';
    
    // QUERY
    $mentorGroupGateway = $container->get(MentorGroupGateway::class);
    $criteria = $mentorGroupGateway->newQueryCriteria(true)
        ->searchBy($mentorGroupGateway->getSearchableColumns(), $search)
        ->sortBy(['name'])
        ->fromPOST();

    // FORM
    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));

    $form->setClass('noIntBorder w-full');
    $form->addHiddenValue('q', '/modules/Free Learning/mentorGroups_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Name'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    $mentorship = $mentorGroupGateway->queryMentorGroups($criteria);

    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Free Learning/mentorGroups_manageProcessBulk.php');

    $bulkActions = ['Delete' => __('Delete')];
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('units', $criteria)->withData($mentorship);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->addParam('search', $search)
        ->setURL('/modules/Free Learning/mentorGroups_manage_add.php')
        ->displayLabel();

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('name', __m('Group Name'));

    $table->addColumn('mentors', __m('Mentors'));

    $table->addColumn('assignment', __m('Group Assignment'));


    // ACTIONS
    $table->addActionColumn()
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('freeLearningMentorGroupID')
        ->format(function ($values, $actions)  {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Free Learning/mentorGroups_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Free Learning/mentorGroups_manage_delete.php');
        });

    $table->addCheckboxColumn('freeLearningMentorGroupID');

    echo $form->getOutput();

}
