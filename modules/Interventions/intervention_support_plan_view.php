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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanNoteGateway;

// Get the database connection
$pdo = $container->get('db')->getConnection();

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('View Support Plan'));

// Add CSS for a more modern look
echo '<style>
    .support-plan-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #A0CFEC;
    }
    .support-plan-section {
        margin-bottom: 20px;
        border: 1px solid #eee;
        border-radius: 5px;
        overflow: hidden;
    }
    .support-plan-section-header {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        font-weight: bold;
    }
    .support-plan-section-content {
        padding: 15px;
    }
    .support-plan-action-buttons {
        margin-bottom: 20px;
    }
    .support-plan-action-buttons a {
        margin-right: 10px;
    }
</style>';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Get intervention ID and support plan ID
    $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
    $gibbonINSupportPlanID = $_GET['gibbonINSupportPlanID'] ?? '';

    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get intervention details
    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getByID($gibbonINInterventionID);

    if (empty($intervention)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get support plan details
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);

    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    if (!$isAdmin && !$isCoordinator) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // DISPLAY SUPPORT PLAN DETAILS
    echo '<div class="support-plan-header">';
    echo '<h2>'.__('Support Plan Details').'</h2>';
    echo '</div>';
    
    // Support Plan Info
    echo '<div class="support-plan-section">';
    echo '<div class="support-plan-section-header">';
    echo __('Support Plan Information');
    echo '</div>';
    echo '<div class="support-plan-section-content">';
    echo '<table class="smallIntBorder fullWidth colorOddEven" cellspacing="0">';
    
    echo '<tr><td class="w-1/4">';
    echo '<b>'.__('Name').'</b>';
    echo '</td><td>';
    echo $supportPlan['name'];
    echo '</td></tr>';
    
    if (!empty($supportPlan['description'])) {
        echo '<tr><td>';
        echo '<b>'.__('Description').'</b>';
        echo '</td><td>';
        echo $supportPlan['description'];
        echo '</td></tr>';
    }
    
    // Display plan type if it exists (for IEP functionality)
    if (isset($supportPlan['type'])) {
        echo '<tr><td>';
        echo '<b>'.__('Type').'</b>';
        echo '</td><td>';
        echo $supportPlan['type'];
        echo '</td></tr>';
    }
    
    echo '<tr><td>';
    echo '<b>'.__('Status').'</b>';
    echo '</td><td>';
    $statuses = [
        'Draft' => "<span class='tag dull'>".__('Draft')."</span>",
        'Active' => "<span class='tag success'>".__('Active')."</span>",
        'Completed' => "<span class='tag attention'>".__('Completed')."</span>",
        'Cancelled' => "<span class='tag error'>".__('Cancelled')."</span>"
    ];
    echo $statuses[$supportPlan['status']] ?? $supportPlan['status'];
    echo '</td></tr>';
    
    echo '<tr><td>';
    echo '<b>'.__('Goals').'</b>';
    echo '</td><td>';
    echo nl2br($supportPlan['goals']);
    echo '</td></tr>';
    
    echo '<tr><td>';
    echo '<b>'.__('Strategies').'</b>';
    echo '</td><td>';
    echo nl2br($supportPlan['strategies']);
    echo '</td></tr>';
    
    if (!empty($supportPlan['resources'])) {
        echo '<tr><td>';
        echo '<b>'.__('Resources').'</b>';
        echo '</td><td>';
        echo nl2br($supportPlan['resources']);
        echo '</td></tr>';
    }
    
    echo '<tr><td>';
    echo '<b>'.__('Target Date').'</b>';
    echo '</td><td>';
    echo Format::date($supportPlan['targetDate']);
    echo '</td></tr>';
    
    echo '<tr><td>';
    echo '<b>'.__('Staff Responsible').'</b>';
    echo '</td><td>';
    echo Format::name($supportPlan['staffTitle'], $supportPlan['staffPreferredName'], $supportPlan['staffSurname'], 'Staff');
    echo '</td></tr>';
    
    if (!empty($supportPlan['dateStart'])) {
        echo '<tr><td>';
        echo '<b>'.__('Date Started').'</b>';
        echo '</td><td>';
        echo Format::date($supportPlan['dateStart']);
        echo '</td></tr>';
    }
    
    if (!empty($supportPlan['dateEnd'])) {
        echo '<tr><td>';
        echo '<b>'.__('Date Ended').'</b>';
        echo '</td><td>';
        echo Format::date($supportPlan['dateEnd']);
        echo '</td></tr>';
    }
    
    if (!empty($supportPlan['outcome'])) {
        echo '<tr><td>';
        echo '<b>'.__('Outcome').'</b>';
        echo '</td><td>';
        echo $supportPlan['outcome'];
        echo '</td></tr>';
    }
    
    if (!empty($supportPlan['outcomeNotes'])) {
        echo '<tr><td>';
        echo '<b>'.__('Outcome Notes').'</b>';
        echo '</td><td>';
        echo nl2br($supportPlan['outcomeNotes']);
        echo '</td></tr>';
    }
    
    echo '<tr><td>';
    echo '<b>'.__('Created By').'</b>';
    echo '</td><td>';
    echo Format::name($supportPlan['creatorTitle'], $supportPlan['creatorPreferredName'], $supportPlan['creatorSurname'], 'Staff');
    echo '</td></tr>';
    
    echo '<tr><td>';
    echo '<b>'.__('Created On').'</b>';
    echo '</td><td>';
    echo Format::dateTime($supportPlan['timestampCreated']);
    echo '</td></tr>';
    
    if (!empty($supportPlan['timestampModified'])) {
        echo '<tr><td>';
        echo '<b>'.__('Last Modified').'</b>';
        echo '</td><td>';
        echo Format::dateTime($supportPlan['timestampModified']);
        echo '</td></tr>';
    }
    
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
    // DISPLAY CONTRIBUTORS
    echo '<div class="support-plan-section">';
    echo '<div class="support-plan-section-header">';
    echo __('Contributors');
    echo '</div>';
    echo '<div class="support-plan-section-content">';
    echo '<h2>'.__('Contributors').'</h2>';
    
    // Add contributor button
    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_contributor_add.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID'>".__('Add Contributor')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';
    
    // Get contributors
    $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
    $sql = "SELECT gibbonINSupportPlanContributor.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonINSupportPlanContributor 
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonINSupportPlanContributor.gibbonPersonID) 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
    $stmt = $connection2->prepare($sql);
    $stmt->execute($data);
    $resultContributors = $stmt;
    
    if ($resultContributors->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo "<table class='smallIntBorder fullWidth colorOddEven' cellspacing='0'>";
        echo "<tr class='head'>";
        echo "<th>".__('Name')."</th>";
        echo "<th>".__('Role')."</th>";
        echo "<th>".__('Can Edit')."</th>";
        echo "<th>".__('Actions')."</th>";
        echo "</tr>";
        
        while ($contributor = $resultContributors->fetch()) {
            echo "<tr>";
            echo "<td>".Format::name($contributor['title'], $contributor['preferredName'], $contributor['surname'], 'Staff')."</td>";
            echo "<td>".$contributor['role']."</td>";
            echo "<td>".__($contributor['canEdit'])."</td>";
            echo "<td>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_contributor_delete.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID&gibbonINSupportPlanContributorID=".$contributor['gibbonINSupportPlanContributorID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo '</div>';
    echo '</div>';
    
    // DISPLAY PROGRESS REPORTS
    echo '<div class="support-plan-section">';
    echo '<div class="support-plan-section-header">';
    echo __('Progress Reports');
    echo '</div>';
    echo '<div class="support-plan-section-content">';
    echo '<h2>'.__('Progress Reports').'</h2>';
    
    // Add progress report button
    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_progress_add.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID'>".__('Add Progress Report')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';
    
    // Get progress reports
    $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
    $sql = "SELECT gibbonINSupportPlanProgress.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonINSupportPlanProgress 
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonINSupportPlanProgress.gibbonPersonIDCreator) 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            ORDER BY gibbonINSupportPlanProgress.progressDate";
    $stmt = $connection2->prepare($sql);
    $stmt->execute($data);
    $resultProgress = $stmt;
    
    if ($resultProgress->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        while ($progress = $resultProgress->fetch()) {
            echo "<div class='border mt-2 p-2'>";
            echo "<h3>".__('Progress Status').': '.__($progress['status'])."</h3>";
            echo "<div class='flex'>";
            echo "<div class='w-1/2'><b>".__('Date').":</b> ".Format::date($progress['progressDate'])."</div>";
            echo "<div class='w-1/2'><b>".__('Added By').":</b> ".Format::name($progress['title'], $progress['preferredName'], $progress['surname'], 'Staff')."</div>";
            echo "</div>";
            
            echo "<div class='mt-2'>";
            echo "<h4>".__('Progress')."</h4>";
            echo "<p>".$progress['progress']."</p>";
            echo "</div>";
            
            echo "<div class='mt-2'>";
            echo "<h4>".__('Next Steps')."</h4>";
            echo "<p>".$progress['nextSteps']."</p>";
            echo "</div>";
            
            echo "<div class='flex justify-end mt-2'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_progress_edit.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID&gibbonINSupportPlanProgressID=".$progress['gibbonINSupportPlanProgressID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
            echo "&nbsp;&nbsp;";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_progress_delete.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID&gibbonINSupportPlanProgressID=".$progress['gibbonINSupportPlanProgressID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo "</div>";
            echo "</div>";
        }
    }
    echo '</div>';
    echo '</div>';
    
    // ACTIONS
    echo '<div class="support-plan-action-buttons">';
    echo '<h3>'.__('Actions').'</h3>';
    
    echo '<div class="flex">';
    
    // Back to intervention button
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID=$gibbonINInterventionID&step=3' class='button'>".__('Back to Intervention')."</a>&nbsp;";
    
    // Edit button - only for admin or creator
    if ($isAdmin || $supportPlan['gibbonPersonIDCreator'] == $gibbonPersonID) {
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_edit.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID' class='button'>".__('Edit')."</a>&nbsp;";
    }
    
    // Activate/Deactivate button - only for admin or creator
    if ($isAdmin || $supportPlan['gibbonPersonIDCreator'] == $gibbonPersonID) {
        if ($supportPlan['status'] == 'Active') {
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_status.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID&status=Draft' class='button'>".__('Deactivate')."</a>&nbsp;";
        } elseif ($supportPlan['status'] == 'Draft') {
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_status.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID&status=Active' class='button'>".__('Activate')."</a>&nbsp;";
        }
    }
    
    // Delete button - only for admin and if not active
    if ($isAdmin && $supportPlan['status'] != 'Active') {
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_delete.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID' class='button'>".__('Delete')."</a>&nbsp;";
    }
    
    echo '</div>';
    echo '</div>';
    
    // NOTES
    echo '<div class="support-plan-section">';
    echo '<div class="support-plan-section-header">';
    echo __('Notes');
    echo '</div>';
    echo '<div class="support-plan-section-content">';
    echo '<h3>'.__('Notes').'</h3>';
    
    $supportPlanNoteGateway = $container->get(INSupportPlanNoteGateway::class);
    $criteria = $supportPlanNoteGateway->newQueryCriteria()
        ->sortBy('date')
        ->fromPOST();
    
    $notes = $supportPlanNoteGateway->queryNotes($criteria, $gibbonINSupportPlanID);
    
    // Add note button
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Interventions/intervention_support_plan_note_add.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID' class='button'>".__('Add Note')."</a>&nbsp;";
    
    if ($notes->getResultCount() == 0) {
        echo '<div class="message">';
        echo __('There are no notes for this support plan.');
        echo '</div>';
    } else {
        $table = DataTable::createPaginated('notes', $criteria);
        $table->setTitle(__('Notes'));
        
        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', 'date'));
        
        $table->addColumn('title', __('Title'));
        
        $table->addColumn('person', __('Added By'))
            ->format(function($values) {
                return Format::name($values['title'], $values['preferredName'], $values['surname'], 'Staff');
            });
        
        $table->addColumn('note', __('Note'))
            ->format(function($values) {
                return nl2br($values['note']);
            });
        
        $table->addActionColumn()
            ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
            ->addParam('gibbonINSupportPlanID', $gibbonINSupportPlanID)
            ->addParam('gibbonINSupportPlanNoteID')
            ->format(function ($note, $actions) use ($guid, $isAdmin, $gibbonPersonID) {
                // Edit note - only for admin or creator
                if ($isAdmin || $note['gibbonPersonID'] == $gibbonPersonID) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Interventions/intervention_support_plan_note_edit.php');
                }
                
                // Delete note - only for admin or creator
                if ($isAdmin || $note['gibbonPersonID'] == $gibbonPersonID) {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Interventions/intervention_support_plan_note_delete.php')
                        ->modalWindow(650, 400);
                }
            });
        
        echo $table->render($notes);
    }
    echo '</div>';
    echo '</div>';
}
