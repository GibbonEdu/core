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

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('View Student Profiles'));

        $studentGateway = $container->get(StudentGateway::class);

        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];

        $canViewFullProfile = ($highestAction == 'View Student Profile_full' or $highestAction == 'View Student Profile_fullNoNotes' or $highestAction == 'View Student Profile_fullEditAllNotes');
        $canViewBriefProfile = isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief');

        if ($highestAction == 'View Student Profile_myChildren' or $highestAction == 'View Student Profile_my') {
            
            if ($highestAction == 'View Student Profile_myChildren') {
                echo '<h2>';
                echo __('My Children');
                echo '</h2>';
                
                $result = $studentGateway->selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $gibbonPersonID);
            } else if ($highestAction == 'View Student Profile_my') {
                echo '<h2>';
                echo __('View Student Profile');
                echo '</h2>';

                $result = $studentGateway->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID);
            }

            if ($result->isEmpty()) {
                echo "<div class='error'>";
                echo __('You do not have access to this action.');
                echo '</div>';
            } else {
                $table = DataTable::create('students');

                $table->addColumn('student', __('Student'))
                    ->sortable(['surname', 'preferredName'])
                    ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));
                $table->addColumn('yearGroup', __('Year Group'));
                $table->addColumn('rollGroup', __('Roll Group'));

                $table->addActionColumn()
                    ->addParam('gibbonPersonID')
                    ->format(function ($row, $actions) {
                        $actions->addAction('view', __('View Details'))
                            ->setURL('/modules/Students/student_view_details.php');
                    });

                echo $table->render($result->toDataSet());
            }
        }
      
        if ($canViewBriefProfile || $canViewFullProfile) {
            //Proceed!
            $search = isset($_GET['search'])? $_GET['search'] : '';
            $sort = isset($_GET['sort'])? $_GET['sort'] : 'surname,preferredName';
            $allStudents = isset($_GET['allStudents'])? $_GET['allStudents'] : '';
            
            $studentGateway = $container->get(StudentGateway::class);

            $searchColumns = $canViewFullProfile
                ? array_merge($studentGateway->getSearchableColumns(), ['parent1.email', 'parent1.emailAlternate', 'parent2.email', 'parent2.emailAlternate'])
                : $studentGateway->getSearchableColumns();

            $criteria = $studentGateway->newQueryCriteria(true)
                ->searchBy($searchColumns, $search)
                ->sortBy(array_filter(explode(',', $sort)))
                ->filterBy('all', $canViewFullProfile ? $allStudents : '')
                ->fromPOST();

            echo '<h2>';
            echo __('Filter');
            echo '</h2>';

            $sortOptions = array(
                'surname,preferredName' => __('Surname'),
                'preferredName' => __('Given Name'),
                'rollGroup' => __('Roll Group'),
                'yearGroup' => __('Year Group'),
            );

            $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
            
            $form->setClass('noIntBorder fullWidth');
            $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/student_view.php');
        
            $searchDescription = $canViewFullProfile 
                ? __('Preferred, surname, username, student ID, email, phone number, vehicle registration, parent email.') 
                : __('Preferred, surname, username.');

            $row = $form->addRow();
                $row->addLabel('search', __('Search For'))
                    ->setClass('mediumWidth')
                    ->description($searchDescription);
                $row->addTextField('search')->setValue($criteria->getSearchText());

            $row = $form->addRow();
                $row->addLabel('sort', __('Sort By'));
                $row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

            if ($canViewFullProfile) {
                $row = $form->addRow();
                    $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
                    $row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);
            }

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session, __('Clear Search'));
            
            echo $form->getOutput();

            echo '<h2>';
            echo __('Choose A Student');
            echo '</h2>';

            $students = $studentGateway->queryStudentsBySchoolYear($criteria, $gibbonSchoolYearID, $canViewFullProfile);

            // DATA TABLE
            $table = DataTable::createPaginated('students', $criteria);
    
            $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

            if ($canViewFullProfile) {
                $table->addMetaData('filterOptions', [
                    'all:on'        => __('All Students')
                ]);
        
                if ($criteria->hasFilter('all')) {
                    $table->addMetaData('filterOptions', [
                        'status:full'     => __('Status').': '.__('Full'),
                        'status:expected' => __('Status').': '.__('Expected'),
                        'date:starting'   => __('Before Start Date'),
                        'date:ended'      => __('After End Date'),
                    ]);
                }
            }
    
            // COLUMNS
            $table->addColumn('student', __('Student'))
                ->sortable(['surname', 'preferredName'])
                ->format(function ($person) {
                    return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
                });
            $table->addColumn('yearGroup', __('Year Group'));
            $table->addColumn('rollGroup', __('Roll Group'));
    
            $table->addActionColumn()
                ->addParam('gibbonPersonID')
                ->addParam('search', $criteria->getSearchText(true))
                ->addParam('sort', $sort)
                ->addParam('allStudents', $canViewFullProfile ? $allStudents : '')
                ->format(function ($row, $actions) {
                    $actions->addAction('view', __('View Details'))
                        ->setURL('/modules/Students/student_view_details.php');
                });
    
            echo $table->render($students);
        }
    }
}

