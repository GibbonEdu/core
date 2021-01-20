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
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Student Enrolment'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/studentEnrolment_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/studentEnrolment_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

        $search = isset($_GET['search'])? $_GET['search'] : '';

        $studentGateway = $container->get(StudentGateway::class);

        $criteria = $studentGateway->newQueryCriteria(true)
            ->searchBy($studentGateway->getSearchableColumns(), $search)
            ->sortBy(['surname', 'preferredName'])
            ->fromPOST();

        echo '<h3>';
        echo __('Search');
        echo '</h3>';

        $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/studentEnrolment_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';
        echo '<p>';
        echo __("Students highlighted in red are marked as 'Full' but have either not reached their start date, or have exceeded their end date.");
        echo '<p>';

        $students = $studentGateway->queryStudentEnrolmentBySchoolYear($criteria, $gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::createPaginated('students', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Students/studentEnrolment_manage_add.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('search', $criteria->getSearchText(true))
            ->displayLabel();
    
        $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

        $table->addMetaData('filterOptions', [
            'status:full'     => __('Status').': '.__('Full'),
            'status:left'     => __('Status').': '.__('Left'),
            'status:expected' => __('Status').': '.__('Expected'),
            'date:starting'   => __('Before Start Date'),
            'date:ended'      => __('After End Date'),
        ]);

        // COLUMNS
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) {
                return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
            });
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('rollGroup', __('Roll Group'))
            ->description(__('Roll Order'))
            ->format(function($row) {
                return $row['rollGroup'] . (!empty($row['rollOrder']) ? '<br/><span class="small emphasis">'.$row['rollOrder'].'</span>' : '');
            });

        $table->addActionColumn()
            ->addParam('gibbonStudentEnrolmentID')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($row, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Students/studentEnrolment_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Students/studentEnrolment_manage_delete.php');
            });

        echo $table->render($students);
    }
}
