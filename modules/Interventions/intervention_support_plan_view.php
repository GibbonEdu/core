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
    
    /* Progress visualization styles */
    .progress-dashboard {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }
    .progress-chart-container {
        flex: 1;
        min-width: 300px;
        height: 250px;
        background: white;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 15px;
    }
    .progress-timeline {
        flex: 2;
        min-width: 450px;
        background: white;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 15px;
    }
    .timeline-container {
        position: relative;
        padding: 20px 0;
    }
    .timeline-line {
        position: absolute;
        top: 30px;
        left: 0;
        right: 0;
        height: 4px;
        background: #eee;
        z-index: 1;
    }
    .timeline-points {
        position: relative;
        display: flex;
        justify-content: space-between;
        z-index: 2;
    }
    .timeline-point {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #A0CFEC;
        cursor: pointer;
        position: relative;
    }
    .timeline-point.status-achieved {
        background: #78C07E;
    }
    .timeline-point.status-concerns {
        background: #F8AC59;
    }
    .timeline-point:hover::after {
        content: attr(data-date);
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        white-space: nowrap;
    }
    .timeline-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 12px;
        color: #777;
    }
    .progress-period {
        margin-bottom: 15px;
        border: 1px solid #eee;
        border-radius: 5px;
        overflow: hidden;
    }
    .progress-period-header {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .progress-period-header:hover {
        background-color: #f0f0f0;
    }
    .progress-period-content {
        padding: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .progress-period.expanded .progress-period-content {
        max-height: 1000px;
        padding: 15px;
    }
    .progress-period-stats {
        display: flex;
        gap: 10px;
    }
    .progress-stat {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        white-space: nowrap;
    }
    .progress-stat.on-track {
        background-color: #A0CFEC;
        color: #fff;
    }
    .progress-stat.concerns {
        background-color: #F8AC59;
        color: #fff;
    }
    .progress-stat.achieved {
        background-color: #78C07E;
        color: #fff;
    }
    .progress-status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    .progress-status-badge.on-track {
        background-color: #A0CFEC;
        color: #fff;
    }
    .progress-status-badge.concerns {
        background-color: #F8AC59;
        color: #fff;
    }
    .progress-status-badge.achieved {
        background-color: #78C07E;
        color: #fff;
    }
    .progress-toggle {
        margin-bottom: 15px;
    }
    .progress-toggle button {
        background: #f8f9fa;
        border: 1px solid #ddd;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .progress-toggle button:hover {
        background: #f0f0f0;
    }
    .progress-toggle button.active {
        background: #A0CFEC;
        color: white;
        border-color: #A0CFEC;
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
    
    // Add progress report button
    if ($isAdmin || $isCoordinator || $isContributor) {
        echo "<div class='linkTop'>";
        echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Interventions/intervention_support_plan_progress_add.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINSupportPlanID=$gibbonINSupportPlanID'>".__('Add Progress Report')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";
        echo "</div>";
    }
    
    // Get progress reports
    $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
    $sql = "SELECT gibbonINSupportPlanProgress.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonINSupportPlanProgress 
            JOIN gibbonPerson ON (gibbonINSupportPlanProgress.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            ORDER BY progressDate DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $progressReports = $stmt->fetchAll();
    
    if (count($progressReports) < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo "</div>";
    } else {
        // Include Chart.js library
        echo '<script src="'.$session->get('absoluteURL').'/lib/Chart.js/3.0/chart.min.js"></script>';
        
        // Create progress dashboard
        echo '<div class="progress-dashboard">';
        
        // Status distribution chart
        echo '<div class="progress-chart-container">';
        echo '<h4>'.__('Status Distribution').'</h4>';
        echo '<canvas id="statusChart"></canvas>';
        echo '</div>';
        
        // Progress timeline
        echo '<div class="progress-timeline">';
        echo '<h4>'.__('Progress Timeline').'</h4>';
        echo '<div class="timeline-container">';
        echo '<div class="timeline-line"></div>';
        echo '<div class="timeline-points" id="timelinePoints"></div>';
        echo '<div class="timeline-labels" id="timelineLabels"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Toggle for view options
        echo '<div class="progress-toggle">';
        echo '<button id="viewRecent" class="active">'.__('View Recent').'</button>';
        echo '<button id="viewAll">'.__('View All').'</button>';
        echo '</div>';
        
        // Group progress reports by month/year
        $progressByPeriod = [];
        foreach ($progressReports as $report) {
            $period = date('F Y', strtotime($report['progressDate']));
            if (!isset($progressByPeriod[$period])) {
                $progressByPeriod[$period] = [
                    'reports' => [],
                    'stats' => [
                        'On Track' => 0,
                        'Concerns' => 0,
                        'Achieved' => 0
                    ]
                ];
            }
            $progressByPeriod[$period]['reports'][] = $report;
            $progressByPeriod[$period]['stats'][$report['status']]++;
        }
        
        // Display progress reports by period
        $isFirst = true;
        foreach ($progressByPeriod as $period => $data) {
            $totalReports = count($data['reports']);
            
            echo '<div class="progress-period '.($isFirst ? 'expanded' : '').'" data-period="'.$period.'">';
            echo '<div class="progress-period-header">';
            echo '<div>'.$period.' <span class="text-gray">('.$totalReports.' '.__('reports').')</span></div>';
            echo '<div class="progress-period-stats">';
            
            if ($data['stats']['On Track'] > 0) {
                echo '<span class="progress-stat on-track">'.__('On Track').': '.$data['stats']['On Track'].'</span>';
            }
            if ($data['stats']['Concerns'] > 0) {
                echo '<span class="progress-stat concerns">'.__('Concerns').': '.$data['stats']['Concerns'].'</span>';
            }
            if ($data['stats']['Achieved'] > 0) {
                echo '<span class="progress-stat achieved">'.__('Achieved').': '.$data['stats']['Achieved'].'</span>';
            }
            
            echo '</div>';
            echo '</div>';
            
            echo '<div class="progress-period-content">';
            foreach ($data['reports'] as $report) {
                $statusClass = strtolower(str_replace(' ', '-', $report['status']));
                
                echo '<div class="progress-report">';
                echo '<h5>'.__('Progress Report').' - '.Format::date($report['progressDate']).'</h5>';
                echo '<div class="progress-report-meta">';
                echo '<div>'.__('Status').': <span class="progress-status-badge '.$statusClass.'">'.$report['status'].'</span></div>';
                echo '<div>'.__('Added By').': '.Format::name($report['title'], $report['preferredName'], $report['surname'], 'Staff').'</div>';
                echo '</div>';
                
                echo '<div class="progress-report-content">';
                echo '<p><strong>'.__('Progress').':</strong> '.nl2br($report['progress']).'</p>';
                
                if (!empty($report['nextSteps'])) {
                    echo '<p><strong>'.__('Next Steps').':</strong> '.nl2br($report['nextSteps']).'</p>';
                }
                
                echo '</div>';
                
                echo '<div class="progress-report-actions">';
                if ($isAdmin || $isCoordinator) {
                    echo '<a href="#"><img title="'.__('Edit').'" src="./themes/'.$session->get('gibbonThemeName').'/img/config.png"/></a> ';
                    echo '<a class="thickbox" href="'.$session->get('absoluteURL').'/fullscreen.php?q=/modules/Interventions/intervention_support_plan_progress_delete.php&gibbonINSupportPlanProgressID='.$report['gibbonINSupportPlanProgressID'].'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&width=650&height=135"><img title="'.__('Delete').'" src="./themes/'.$session->get('gibbonThemeName').'/img/garbage.png"/></a>';
                }
                echo '</div>';
                
                echo '</div>';
                
                if ($report !== end($data['reports'])) {
                    echo '<hr>';
                }
            }
            echo '</div>';
            echo '</div>';
            
            $isFirst = false;
        }
        
        // JavaScript for interactive elements
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Status distribution chart
            const statusCounts = {
                "On Track": 0,
                "Concerns": 0,
                "Achieved": 0
            };
            
            // Count statuses
            '.json_encode($progressReports).'.forEach(report => {
                statusCounts[report.status]++;
            });
            
            // Create chart
            const ctx = document.getElementById("statusChart").getContext("2d");
            const statusChart = new Chart(ctx, {
                type: "doughnut",
                data: {
                    labels: ["On Track", "Concerns", "Achieved"],
                    datasets: [{
                        data: [statusCounts["On Track"], statusCounts["Concerns"], statusCounts["Achieved"]],
                        backgroundColor: ["#A0CFEC", "#F8AC59", "#78C07E"],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom"
                        }
                    }
                }
            });
            
            // Timeline visualization
            const timelinePoints = document.getElementById("timelinePoints");
            const timelineLabels = document.getElementById("timelineLabels");
            const reports = '.json_encode($progressReports).';
            
            // Limit to 10 most recent reports for timeline
            const timelineReports = reports.slice(0, 10);
            
            // Create timeline points
            timelineReports.forEach((report, index) => {
                const point = document.createElement("div");
                point.className = "timeline-point";
                point.classList.add("status-" + report.status.toLowerCase().replace(" ", "-"));
                point.setAttribute("data-date", report.progressDate);
                point.setAttribute("data-report-id", report.gibbonINSupportPlanProgressID);
                point.style.left = ((index / (timelineReports.length - 1)) * 100) + "%";
                timelinePoints.appendChild(point);
                
                // Add click event to scroll to the report
                point.addEventListener("click", function() {
                    const reportId = this.getAttribute("data-report-id");
                    const reportElement = document.querySelector(`.progress-report[data-id="${reportId}"]`);
                    if (reportElement) {
                        reportElement.scrollIntoView({ behavior: "smooth" });
                        reportElement.style.backgroundColor = "#fffde7";
                        setTimeout(() => {
                            reportElement.style.backgroundColor = "";
                        }, 2000);
                    }
                });
                
                // Add date label
                if (index === 0 || index === timelineReports.length - 1 || index === Math.floor(timelineReports.length / 2)) {
                    const label = document.createElement("div");
                    label.className = "timeline-label";
                    label.textContent = report.progressDate;
                    label.style.left = ((index / (timelineReports.length - 1)) * 100) + "%";
                    timelineLabels.appendChild(label);
                }
            });
            
            // Toggle between view modes
            const viewRecent = document.getElementById("viewRecent");
            const viewAll = document.getElementById("viewAll");
            const periods = document.querySelectorAll(".progress-period");
            
            viewRecent.addEventListener("click", function() {
                this.classList.add("active");
                viewAll.classList.remove("active");
                
                // Show only the first period, hide others
                periods.forEach((period, index) => {
                    if (index === 0) {
                        period.classList.add("expanded");
                    } else {
                        period.classList.remove("expanded");
                    }
                });
            });
            
            viewAll.addEventListener("click", function() {
                this.classList.add("active");
                viewRecent.classList.remove("active");
                
                // Show all periods
                periods.forEach(period => {
                    period.classList.add("expanded");
                });
            });
            
            // Toggle period expansion
            document.querySelectorAll(".progress-period-header").forEach(header => {
                header.addEventListener("click", function() {
                    const period = this.parentElement;
                    period.classList.toggle("expanded");
                });
            });
        });
        </script>';
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
