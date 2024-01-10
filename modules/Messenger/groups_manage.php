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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Forms\Form;

$page->breadcrumbs->add(__('Manage Groups'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search'] ?? '';

    // School Year Picker
    if (!empty($gibbonSchoolYearID)) {
       $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);
    }

    $groupGateway = $container->get(GroupGateway::class);

    // QUERY
    $criteria = $groupGateway->newQueryCriteria(true)
        ->searchBy($groupGateway->getSearchableColumns(), $search)
        ->sortBy(['schoolYear', 'name'])
        ->fromPOST();

    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
    if ($highestAction == 'Manage Groups_all') {
        $groups = $groupGateway->queryGroups($criteria, $gibbonSchoolYearID);
    } else {
        $groups = $groupGateway->queryGroups($criteria, $gibbonSchoolYearID, $session->get('gibbonPersonID'));
    }

    // SEARCH FORM
    $searchForm = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $searchForm->setClass('noIntBorder fullWidth');

    $searchForm->addHiddenValue('address', $session->get('address'));
    $searchForm->addHiddenValue('q', '/modules/Messenger/groups_manage.php');
    $searchForm->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $searchForm->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Group name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $searchForm->addRow()->addSearchSubmit($session, __('Clear Search'), ['gibbonSchoolYearID']);
    echo $searchForm->getOutput();
    
    // BULK ACTION FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/groups_manageProcessBulk.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    if ($highestAction == 'Manage Groups_all') {
        // BULK ACTIONS
        $bulkActions = array(
            'DuplicateMembers' => __('Duplicate With Members'),
            'Duplicate' => __('Duplicate'),
            'Delete' => __('Delete'),
        );
        $col = $form->createBulkActionColumn($bulkActions);
            $col->addSelectSchoolYear('gibbonSchoolYearIDCopyTo', 'Active')
                ->setClass('shortWidth schoolYear')
                ->placeholder(null);
            $col->addSubmit(__('Go'));

        $form->toggleVisibilityByClass('schoolYear')->onSelect('action')->when(array('Duplicate', 'DuplicateMembers'));

        // DATA TABLE
        $table = $form->addRow()->addDataTable('groupsManage', $criteria)->withData($groups);

        $table->addMetaData('bulkActions', $col);
    } else {
        // DATA TABLE
        $table = $form->addRow()->addDataTable('groupsManage', $criteria)->withData($groups);
    }

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Messenger/groups_manage_add.php')
        ->displayLabel();

    // COLUMNS
    $table->addColumn('name', __('Name'))->sortable();

    $table->addColumn('owner', __('Group Owner'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', true, true]));

    $table->addColumn('count', __('Group Members'))->sortable();

    $table->addActionColumn()
        ->addParam('gibbonGroupID')
        ->format(function ($person, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Messenger/groups_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Messenger/groups_manage_delete.php');
        });

    if ($highestAction == 'Manage Groups_all') {
        $table->addCheckboxColumn('gibbonGroupIDList', 'gibbonGroupID');
    }

    echo $form->getOutput();
}
