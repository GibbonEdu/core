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
use Gibbon\Domain\Planner\OutcomeGateway;

$page->breadcrumbs->add(__('Manage Outcomes'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Filter variables
        $where = '';
        $data = array();

        $filter2 = isset($_GET['filter2'])? $_GET['filter2'] : '';
        if ($filter2 != '') {
            $data['gibbonDepartmentID'] = $filter2;
            $where .= " WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID";
        }

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/outcomes.php');

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('filter2', __('Learning Areas'));
            $row->addSelect('filter2')
                ->fromArray(array('' => __('All Learning Areas')))
                ->fromQuery($pdo, $sql)
                ->selected($filter2);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        $outcomeGateway = $container->get(OutcomeGateway::class);

        // QUERY
        $criteria = $outcomeGateway->newQueryCriteria(true)
            ->sortBy(['scope', 'department', 'category', 'nameShort'])
            ->pageSize(50)
            ->fromPOST();

        $outcomes = $outcomeGateway->queryOutcomes($criteria, $filter2);

        // TABLE
        $table = DataTable::createPaginated('outcomes', $criteria);
        $table->setTitle(__('View'));

        $table->modifyRows(function ($unit, $row) {
            if ($unit['active'] != 'Y') $row->addClass('error');
            return $row;
        });

        if ($highestAction == 'Manage Outcomes_viewEditAll' or $highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
            $table->addHeaderAction('add', __('Add'))
                ->addParam('filter2', $filter2)
                ->setURL('/modules/Planner/outcomes_add.php')
                ->displayLabel();
        }

        $table->addColumn('scope', __('Scope'))
            ->format(function ($row) {
                return ($row['scope'] == "School") ? $row['scope'] : $row['scope']."<br/>".Format::small(__($row['department']));
            });

        $table->addColumn('category', __('Category'));

        $table->addColumn('name', __('Name'))
            ->format(function ($row) {
                return $row['nameShort']."<br/>".Format::small(__($row['name']));
            });

        $table->addColumn('yearGroupList', __('Year Groups'));

        $table->addColumn('active', __('Active'))
            ->format(function ($row) {
                return Format::yesNo(__($row['active']));
            });

        $actions = $table->addActionColumn()
            ->addParam('gibbonOutcomeID')
            ->addParam('filter2', $filter2)
            ->format(function ($resource, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Planner/outcomes_edit.php');
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Planner/outcomes_delete.php');
            });

        echo $table->render($outcomes);
    }
}
