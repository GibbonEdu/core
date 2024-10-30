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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Behaviour\BehaviourGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_letters.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View Behaviour Letters'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    $form = Form::create('filter', $session->get('absoluteURL')."/index.php", 'get', 'noIntBorder fullWidth standardForm');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Behaviour/behaviour_letters.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->selected($gibbonPersonID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    echo '<h3>';
    echo __('Behaviour Letters');
    echo '</h3>';
    echo '<p>';
    echo __('This interface displays automated behaviour letters that have been issued within the current school year.');
    echo '</p>';

    $behaviourGateway = $container->get(BehaviourGateway::class);

    // CRITERIA
    $criteria = $behaviourGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->filterBy('student', $gibbonPersonID)
        ->fromPOST();

    $letters = $behaviourGateway->queryBehaviourLettersBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));

    // DATA TABLE
    $table = DataTable::createPaginated('behaviourLetters', $criteria);

    // COLUMNS
    $table->addExpandableColumn('comment')
        ->format(function($letter) {
            $output = '';
            if (!empty($letter['body'])) {
                $output .= '<b>'.__('Letter Body').'</b><br/>';
                $output .= nl2br($letter['body']).'<br/><br/>';
            }
            if (!empty($letter['recipientList'])) {
                $output .= '<b>'.__('Recipients').'</b><br/>';
                $reipients = array_map('trim', explode(',', $letter['recipientList']));
                $output .= implode('<br/>', $reipients);
            }
            return $output;
        });

    $table->addColumn('student', __('Student'))
        ->description(__('Form Group'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function($person) use ($session) {
            $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Behaviour&search=&allStudents=&sort=surname,preferredName';
            return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                  .'<br/><small><i>'.$person['formGroup'].'</i></small>';
        });

    $table->addColumn('timestamp', __('Date'))
        ->format(Format::using('date', 'timestamp'));
    $table->addColumn('letterLevel', __('Letter'));
    $table->addColumn('status', __('Status'));

    echo $table->render($letters);
}
