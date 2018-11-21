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
use Gibbon\Domain\Rubrics\RubricGateway;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('View Rubrics'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_REQUEST['search'])? $_REQUEST['search'] : '';
    $department = isset($_POST['filter2'])? $_POST['filter2'] : '';
    $yearGroups = getYearGroups($connection2);

    $rubricGateway = $container->get(RubricGateway::class);

    // QUERY
    $criteria = $rubricGateway->newQueryCriteria()
        ->searchBy($rubricGateway->getSearchableColumns(), $search)
        ->sortBy(['scope', 'category', 'name'])
        ->filterBy('department', $department)
        ->fromPOST();

    // SEARCH
    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rubrics_view.php');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Rubric name.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('filter2', __('Learning Areas'));
        $row->addSelect('filter2')
            ->fromArray(array('' => __('All Learning Areas')))
            ->fromQuery($pdo, $sql)
            ->selected($department);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    // If the current user is a student, limit the results to their year group
    $gibbonYearGroupID = null;
    $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    if ($roleCategory == 'Student') {
        $studentGateway = $container->get(StudentGateway::class);
        $enrolment = $studentGateway->selectActiveStudentByPerson($_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'])->fetch();

        if (!empty($enrolment)) {
            $gibbonYearGroupID = $enrolment['gibbonYearGroupID'];
        }
    }

    $rubrics = $rubricGateway->queryRubrics($criteria, 'Y', $gibbonYearGroupID);

    // DATA TABLE
    $table = DataTable::createPaginated('rubrics', $criteria);
    $table->setTitle(__('Rubrics'));

    // COLUMNS
    $table->addExpandableColumn('description');
    $table->addColumn('scope', __('Scope'))
        ->width('15%')
        ->format(function($rubric) {
            if ($rubric['scope'] == 'School') {
                return '<strong>'.__('School').'</strong>';
            } else {
                return '<strong>'.__('Learning Area').'</strong><br/>'.Format::small($rubric['learningArea']);
            }
        });
    $table->addColumn('category', __('Category'))->width('15%');
    $table->addColumn('name', __('Name'))->width('35%');
    $table->addColumn('yearGroups', __('Year Groups'))
        ->format(function($activity) use ($yearGroups) {
            return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonRubricID')
        ->format(function ($rubric, $actions) {
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Rubrics/rubrics_view_full.php')
                ->isModal(1100, 550);
        });

    echo $table->render($rubrics);
}
