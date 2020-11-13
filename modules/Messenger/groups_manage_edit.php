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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Messenger\GroupGateway;

$page->breadcrumbs
    ->add(__('Manage Groups'), 'groups_manage.php')
    ->add(__('Edit Group'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonGroupID = (isset($_GET['gibbonGroupID']))? $_GET['gibbonGroupID'] : null;

    //Check if school year specified
    if ($gibbonGroupID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $groupGateway = $container->get(GroupGateway::class);
        
        $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
        if ($highestAction == 'Manage Groups_all') {
            $result = $groupGateway->selectGroupByID($gibbonGroupID);
        } else {
            $result = $groupGateway->selectGroupByIDAndOwner($gibbonGroupID, $_SESSION[$guid]['gibbonPersonID']);
        }

        if ($result->isEmpty()) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('groups', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_editProcess.php?gibbonGroupID=$gibbonGroupID");
            $form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			
            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required();

            $row = $form->addRow();
                $row->addLabel('members', __('Add Members'));
                $row->addSelectUsers('members', $_SESSION[$guid]['gibbonSchoolYearID'], ['includeStudents' => true])
                    ->selectMultiple();
            	
			$row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
                
            $form->loadAllValuesFrom($values);
				
            echo $form->getOutput();

            echo '<h2>';
            echo __('Current Members');
            echo '</h2>';

            $criteria = $groupGateway->newQueryCriteria(true)
                ->sortBy(['surname', 'preferredName'])
                ->fromPOST();

            $members = $groupGateway->queryGroupMembers($criteria, $gibbonGroupID);

            $table = DataTable::createPaginated('groupsManage', $criteria);

            $table->addColumn('name', __('Name'))
                ->sortable(['surname', 'preferredName'])
                ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));

            $table->addColumn('email', __('Email'))->sortable();

            $table->addActionColumn()
                ->addParam('gibbonGroupID')
                ->addParam('gibbonPersonID')
                ->format(function ($person, $actions) {
                    $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/Messenger/groups_manage_edit_delete.php');
                });

            echo $table->render($members);
        }
    }
}
