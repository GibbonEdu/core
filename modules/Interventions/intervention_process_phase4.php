<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Module\Interventions\Domain\INInterventionNoteGateway;

// Add a message if the intervention is not yet at this phase
if ($intervention['status'] != 'Support Plan Active' && !$isAdmin) {
    echo '<div class="error">';
    echo __('This intervention is not yet in the implementation phase.');
    echo '</div>';
    return;
}

// Display support plan details
echo '<div class="message">';
echo '<h4>' . __('Support Plan Details') . '</h4>';
echo '<strong>' . __('Goals') . ':</strong> ' . $intervention['goals'] . '<br/>';
echo '<strong>' . __('Strategies') . ':</strong> ' . $intervention['strategies'] . '<br/>';

if (!empty($intervention['resources'])) {
    echo '<strong>' . __('Resources') . ':</strong> ' . $intervention['resources'] . '<br/>';
}

echo '<strong>' . __('Target Date') . ':</strong> ' . Format::date($intervention['targetDate']) . '<br/>';

// Get staff name
try {
    $dataStaff = array('gibbonPersonID' => $intervention['gibbonPersonIDStaff']);
    $sqlStaff = "SELECT title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
    $resultStaff = $connection2->prepare($sqlStaff);
    $resultStaff->execute($dataStaff);
    
    if ($resultStaff->rowCount() == 1) {
        $rowStaff = $resultStaff->fetch();
        $staffName = Format::name($rowStaff['title'], $rowStaff['preferredName'], $rowStaff['surname'], 'Staff');
        echo '<strong>' . __('Staff Responsible') . ':</strong> ' . $staffName . '<br/>';
    }
} catch (PDOException $e) {
    // Log the error but continue
    error_log($e->getMessage());
}

echo '<strong>' . __('Date Started') . ':</strong> ' . Format::date($intervention['dateStart']) . '<br/>';
echo '</div>';

// Get intervention notes
$noteGateway = $container->get(INInterventionNoteGateway::class);
$criteria = [
    'gibbonINInterventionID' => $gibbonINInterventionID
];
$notes = $noteGateway->queryNotes($criteria)->toDataSet();

// Display notes
$table = DataTable::createPaginated('notes', $criteria);
$table->setTitle(__('Implementation Notes'));

$table->addHeaderAction('add', __('Add'))
    ->setURL('/modules/Interventions/intervention_note_add.php')
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('returnProcess', true)
    ->displayLabel();

$table->addColumn('date', __('Date'))
    ->format(Format::using('date', ['date']));

$table->addColumn('title', __('Title'));

$table->addColumn('name', __('Added By'))
    ->sortable(['surname', 'preferredName'])
    ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));

// Add actions column
$table->addActionColumn()
    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
    ->addParam('gibbonINInterventionNoteID')
    ->format(function ($note, $actions) use ($guid, $isAdmin, $gibbonPersonID) {
        // View action
        $actions->addAction('view', __('View'))
            ->setURL('/modules/Interventions/intervention_note_view.php');

        // Edit action - only for the note creator or admin
        if ($isAdmin || $note['gibbonPersonID'] == $gibbonPersonID) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Interventions/intervention_note_edit.php');
        }
        
        // Delete action - only for admin
        if ($isAdmin) {
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Interventions/intervention_note_delete.php')
                ->modalWindow(650, 400);
        }
    });

echo $table->render($notes);

// Create the form to move to evaluation phase
$form = Form::create('interventionPhase4', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_process_phase4Process.php');
$form->setClass('w-full');

$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);

// Only show the complete implementation button if user is admin or responsible staff
if ($isAdmin || $intervention['gibbonPersonIDStaff'] == $gibbonPersonID) {
    $row = $form->addRow();
    $row->addContent('<div class="message emphasis">');
    $row->addContent('<p><strong>'.__('Workflow Information').':</strong></p>');
    $row->addContent('<p>'.__('When the implementation period is complete, click the button below to move to the evaluation phase.').'</p>');
    $row->addContent('</div>');
    
    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Complete Implementation & Move to Evaluation'));
    
    // Display the form
    echo $form->getOutput();
}
