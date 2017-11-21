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
use Gibbon\Forms\DatabaseFormFactory;

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Manage Rubrics')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Rubric').'</div>';
            echo '</div>';

            if ($search != '' or $filter2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Rubrics/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }
            
            $scopes = array(
                'School' => __('School'),
                'Learning Area' => __('Learning Area'),
            );

            $form = Form::create('addRubric', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rubrics_addProcess.php?search='.$search.'&filter2='.$filter2);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            
            $form->addRow()->addHeading(__('Rubric Basics'));

            $row = $form->addRow();
                $row->addLabel('scope', 'Scope');
            if ($highestAction == 'Manage Rubrics_viewEditAll') {
                $row->addSelect('scope')->fromArray($scopes)->isRequired()->placeholder();
            } else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                $row->addTextField('scope')->readOnly()->setValue('Learning Area');
            }

            if ($highestAction == 'Manage Rubrics_viewEditAll') {
                $data = array();
                $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
            } else if ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND type='Learning Area' ORDER BY name";
            }

            $form->toggleVisibilityByClass('learningAreaRow')->onSelect('scope')->when('Learning Area');
            $row = $form->addRow()->addClass('learningAreaRow');
                $row->addLabel('gibbonDepartmentID', __('Learning Area'));
                $row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, $data)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->maxLength(50)->isRequired();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->isRequired();

            $sql = "SELECT DISTINCT category FROM gibbonRubric ORDER BY category";
            $result = $pdo->executeQuery(array(), $sql);
            $categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addTextField('category')->maxLength(100)->autocomplete($categories);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextArea('description')->setRows(5);

            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupIDList[]', __('Year Groups'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList[]')->addCheckAllNone()->checkAll();

            $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE (active='Y') ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('gibbonScaleID', __('Grade Scale'))->description(__('Link columns to grades on a scale?'));
                $row->addSelect('gibbonScaleID')->fromQuery($pdo, $sql)->placeholder();

            $form->addRow()->addHeading(__('Rubric Design'));

            $row = $form->addRow();
                $row->addLabel('rows', __('Initial Rows'))->description(__('Rows store assessment strands.'));
                $row->addSelect('rows')->fromArray(range(1, 10))->isRequired();

            $row = $form->addRow();
                $row->addLabel('columns', __('Initial Columns'))->description(__('Columns store assessment levels.'));
                $row->addSelect('columns')->fromArray(range(1, 10))->isRequired();
            
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
            
            echo $form->getOutput();
        }
    }
}
