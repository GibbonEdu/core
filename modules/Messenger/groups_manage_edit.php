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

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/groups_manage.php'>Manage Groups</a> > </div><div class='trailEnd'>Edit Group</div>";
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonGroupID = (isset($_GET['gibbonGroupID']))? $_GET['gibbonGroupID'] : null;

    //Check if school year specified
    if ($gibbonGroupID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $groupGateway = $container->get(GroupGateway::class);
        
        $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
        if ($highestAction == 'Manage Groups_all') {
            $result = $groupGateway->selectGroupByID($gibbonGroupID);
        } else {
            $result = $groupGateway->selectGroupByIDAndOwner($gibbonGroupID, $_SESSION[$guid]['gibbonPersonID']);
        }

        if ($result->isEmpty()) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('groups', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_editProcess.php?gibbonGroupID=$gibbonGroupID");
            $form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			
            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired();

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
            echo __($guid, 'Current Members');
            echo '</h2>';

            $criteria = $groupGateway->newQueryCriteria()
                ->sortBy(['surname', 'preferredName'])
                ->fromArray($_POST);

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
