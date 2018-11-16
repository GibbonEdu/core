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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Behaviour\BehaviourGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_letters.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs->add(__('View Behaviour Letters'));

    $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL']."/index.php", 'get', 'noIntBorder fullWidth standardForm');
    $form->setTitle(__('Filter'));
    $form->addHiddenValue('q', '/modules/Behaviour/behaviour_letters.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    echo '<h3>';
    echo __('Behaviour Letters');
    echo '</h3>';
    echo '<p>';
    echo __('This interface displays automated behaviour letters that have been issued within the current school year.');
    echo '</p>';

    $behaviourGateway = $container->get(BehaviourGateway::class);

    // CRITERIA
    $criteria = $behaviourGateway->newQueryCriteria()
        ->sortBy('timestamp', 'DESC')
        ->filterBy('student', $gibbonPersonID)
        ->fromPOST();

    $letters = $behaviourGateway->queryBehaviourLettersBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // DATA TABLE
    $table = DataTable::createPaginated('behaviourLetters', $criteria);

    // COLUMNS
    $table->addExpandableColumn('comment')
        ->format(function($letter) {
            $output = '';
            if (!empty($letter['body'])) {
                $output .= '<b>'.__('Letter Body').'</b><br/>';
                $output .= nl2brr($letter['body']).'<br/><br/>';
            }
            if (!empty($letter['recipientList'])) {
                $output .= '<b>'.__('Recipients').'</b><br/>';
                $reipients = array_map('trim', explode(',', $letter['recipientList']));
                $output .= implode('<br/>', $reipients);
            }
            return $output;
        });

    $table->addColumn('student', __('Student'))
        ->description(__('Roll Group'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function($person) use ($guid) {
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Behaviour&search=&allStudents=&sort=surname,preferredName';
            return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                  .'<br/><small><i>'.$person['rollGroup'].'</i></small>';
        });

    $table->addColumn('timestamp', __('Date'))
        ->format(Format::using('date', 'timestamp'));
    $table->addColumn('letterLevel', __('Letter'));
    $table->addColumn('status', __('Status'));

    echo $table->render($letters);
}
