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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Assessments\InternalAssessmentGateway;

// Module includes - Use absolute path
require_once dirname(dirname(dirname(__FILE__))) . '/gibbon.php';

if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/learning_management.php')) {
    // Access denied
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=unauthorised.php';
    header("Location: {$URL}");
    exit;
}

// Setup page
$page = $container->get('page');
$session = $container->get('session');
$db = $container->get('db');
$connection2 = $db->getConnection();

// Set page properties
$page->breadcrumbs
    ->add(__('Modules'))
    ->add(__('ChatBot'))
    ->add(__('Learning Management'));

$absoluteURL = $session->get('absoluteURL');

// Add stylesheets
$page->stylesheets->add('chatbotStyles', 'modules/ChatBot/css/chatbot.css');
$page->stylesheets->add('learningStyles', 'modules/ChatBot/css/learning-styles.css');

// Add module menu
$page->addSidebarExtra('<div class="column-no-break">
    <h2>' . __('ChatBot Menu') . '</h2>
    <ul class="moduleMenu">
        <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/chatbot.php">' . __('AI Teaching Assistant') . '</a></li>
        <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/assessment_integration.php">' . __('Assessment Integration') . '</a></li>
        <li class="selected"><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/learning_management.php">' . __('Learning Management') . '</a></li>
        <li><a href="' . $absoluteURL . '/index.php?q=/modules/ChatBot/settings.php">' . __('Settings') . '</a></li>
    </ul>
</div>');

// Set breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Learning Management'));

// Set page title
$page->breadcrumbs->add(__('Learning Management'));

// Basic page structure
echo '<h2>';
echo __('Learning Management');
echo '</h2>';

try {
    // Check if tables exist
    $checkTable = $connection2->prepare("SHOW TABLES LIKE 'gibbonChatBotCourseMaterials'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('Required tables are not installed. Please install the module first.');
        echo '</div>';
        return;
    }

    // Learning Analytics Section
    echo '<div class="module-block">';
    echo '<h4>' . __('LEARNING ANALYTICS') . '</h4>';
    echo '<p>' . __('View detailed learning analytics and insights.') . '</p>';

    // Add learning analytics content
    echo '<div class="metrics-grid">';
    echo '<div class="metric-card">
            <h5>' . __('Average Progress') . '</h5>
            <div class="metric-value">78%</div>
            <div class="metric-trend positive">↑ 5%</div>
          </div>';
    echo '<div class="metric-card">
            <h5>' . __('Active Students') . '</h5>
            <div class="metric-value">245</div>
            <div class="metric-trend positive">↑ 12</div>
          </div>';
    echo '<div class="metric-card">
            <h5>' . __('Course Completion') . '</h5>
            <div class="metric-value">85%</div>
            <div class="metric-trend neutral">→</div>
          </div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="module-block">';
    echo '<h4>' . __('ENGAGEMENT DATA') . '</h4>';
    echo '<p>' . __('Monitor student engagement and participation.') . '</p>';

    // Add engagement metrics
    echo '<div class="metrics-grid">';
    echo '<div class="metric-card">
            <h5>' . __('Daily Active Users') . '</h5>
            <div class="metric-value">156</div>
            <div class="metric-trend positive">↑ 8%</div>
          </div>';
    echo '<div class="metric-card">
            <h5>' . __('Average Session Time') . '</h5>
            <div class="metric-value">45m</div>
            <div class="metric-trend positive">↑ 5m</div>
          </div>';
    echo '<div class="metric-card">
            <h5>' . __('Resource Usage') . '</h5>
            <div class="metric-value">92%</div>
            <div class="metric-trend positive">↑ 3%</div>
          </div>';
    echo '</div>';
    echo '</div>';

    // Student Assessment Section
    echo '<h3>' . __('STUDENT ASSESSMENTS') . '</h3>';

    echo '<div class="module-block">';
    echo '<h4>' . __('ASSESSMENT ANALYSIS') . '</h4>';
    echo '<p>' . __('View student assessment data with AI-powered analysis and recommendations.') . '</p>';

    // Add filter controls
    echo '<div class="filter-controls">';
    echo '<div class="filter-row">';
    echo '<div class="filter-item">';
    echo '<label for="studentFilter">' . __('Student') . ':</label>';
    echo '<select id="studentFilter" class="filter-select">';
    echo '<option value="">' . __('All Students') . '</option>';

    // Get unique students
    $studentSQL = "SELECT DISTINCT p.gibbonPersonID, CONCAT(p.preferredName, ' ', p.surname) as studentName 
                   FROM gibbonPerson p 
                   JOIN gibbonInternalAssessmentEntry iae ON p.gibbonPersonID=iae.gibbonPersonIDStudent 
                   WHERE p.status='Full' 
                   ORDER BY p.surname, p.preferredName";
    $studentResult = $connection2->query($studentSQL);
    while ($student = $studentResult->fetch()) {
        echo '<option value="' . $student['gibbonPersonID'] . '">' . $student['studentName'] . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '<div class="filter-item">';
    echo '<label for="courseFilter">' . __('Course') . ':</label>';
    echo '<select id="courseFilter" class="filter-select">';
    echo '<option value="">' . __('All Courses') . '</option>';

    // Get unique courses
    $courseSQL = "SELECT DISTINCT c.gibbonCourseID, c.name 
                  FROM gibbonCourse c 
                  JOIN gibbonCourseClass gcc ON c.gibbonCourseID=gcc.gibbonCourseID 
                  JOIN gibbonInternalAssessmentColumn iac ON gcc.gibbonCourseClassID=iac.gibbonCourseClassID 
                  WHERE c.gibbonSchoolYearID=:gibbonSchoolYearID 
                  ORDER BY c.name";
    $courseResult = $connection2->prepare($courseSQL);
    $courseResult->execute(['gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']]);
    while ($course = $courseResult->fetch()) {
        echo '<option value="' . $course['gibbonCourseID'] . '">' . $course['name'] . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '<div class="filter-item">';
    echo '<label><input type="checkbox" id="showLowPerformers" checked> ' . __('Show Only Below 60%') . '</label>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    try {
        // Check if analytics table exists
        $checkAnalyticsTable = $connection2->prepare("SHOW TABLES LIKE 'gibbonChatBotStudentAnalytics'");
        $checkAnalyticsTable->execute();
        
        $hasAnalytics = $checkAnalyticsTable->rowCount() > 0;
        
        // Modify query based on table existence
        $sql = "SELECT DISTINCT
            CONCAT(p.preferredName, ' ', p.surname) as studentName,
            c.name as course,
            iac.name as assessment,
            iae.attainmentValue as attainment,
            iae.comment,
            p.gibbonPersonID,
            c.gibbonCourseID";
        
        if ($hasAnalytics) {
            $sql .= ",
                sa.analyticsData";
        }
        
        $sql .= " FROM gibbonInternalAssessmentEntry iae 
            JOIN gibbonInternalAssessmentColumn iac ON iae.gibbonInternalAssessmentColumnID=iac.gibbonInternalAssessmentColumnID
            JOIN gibbonCourseClass gcc ON iac.gibbonCourseClassID=gcc.gibbonCourseClassID
            JOIN gibbonCourse c ON gcc.gibbonCourseID=c.gibbonCourseID
            JOIN gibbonPerson p ON iae.gibbonPersonIDStudent=p.gibbonPersonID";
        
        if ($hasAnalytics) {
            $sql .= " LEFT JOIN gibbonChatBotStudentAnalytics sa ON p.gibbonPersonID=sa.gibbonPersonID 
                     AND c.gibbonCourseID=sa.gibbonCourseID 
                     AND c.gibbonSchoolYearID=sa.gibbonSchoolYearID";
        }
        
        $sql .= " WHERE c.gibbonSchoolYearID=:gibbonSchoolYearID
            AND iae.attainmentValue < 60
            ORDER BY iae.attainmentValue ASC, p.surname, p.preferredName, c.name";

        $result = $connection2->prepare($sql);
        $result->execute(['gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']]);

        if ($result && $result->rowCount() > 0) {
            echo '<table class="fullWidth colorOddEven" cellspacing="0" id="assessmentTable">';
            echo '<tr class="head">';
            echo '<th>' . __('Student') . '</th>';
            echo '<th>' . __('Course') . '</th>';
            echo '<th>' . __('Assessment') . '</th>';
            echo '<th>' . __('Attainment') . '</th>';
            echo '<th>' . __('Teacher Comment') . '</th>';
            echo '<th>' . __('AI Analysis & Recommendations') . '</th>';
            echo '<th>' . __('Actions') . '</th>';
            echo '</tr>';
            
            while ($row = $result->fetch()) {
                $attainmentClass = $row['attainment'] < 60 ? 'low-performance' : '';
                
                echo '<tr class="' . $attainmentClass . '" data-student-id="' . $row['gibbonPersonID'] . '" data-course-id="' . $row['gibbonCourseID'] . '">';
                echo '<td>' . $row['studentName'] . '</td>';
                echo '<td>' . $row['course'] . '</td>';
                echo '<td>' . $row['assessment'] . '</td>';
                echo '<td class="attainment-cell ' . $attainmentClass . '">' . $row['attainment'] . '%</td>';
                echo '<td>' . $row['comment'] . '</td>';
                echo '<td class="ai-analysis">';
                echo '<div class="analysis-item">';
                echo '<strong>Performance Analysis:</strong><br>';
                if ($hasAnalytics && !empty($row['analyticsData'])) {
                    $analytics = json_decode($row['analyticsData'], true);
                    echo isset($analytics['performanceAnalysis']) ? $analytics['performanceAnalysis'] : 'No analysis available';
                } else if ($row['attainment'] < 60) {
                    echo 'Student requires immediate attention. Performance is below the required threshold.';
                }
                echo '</div>';
                echo '<div class="analysis-item">';
                echo '<strong>Recommended Interventions:</strong><br>';
                if ($hasAnalytics && !empty($row['analyticsData'])) {
                    $analytics = json_decode($row['analyticsData'], true);
                    if (isset($analytics['recommendations'])) {
                        foreach ($analytics['recommendations'] as $recommendation) {
                            echo '• ' . $recommendation . '<br>';
                        }
                    } else {
                        echo 'No recommendations available';
                    }
                } else if ($row['attainment'] < 50) {
                    echo '• Immediate remedial support required<br>';
                    echo '• One-on-one tutoring sessions recommended<br>';
                    echo '• Parent-teacher conference advised';
                } else if ($row['attainment'] < 60) {
                    echo '• Additional practice exercises needed<br>';
                    echo '• Small group study sessions recommended<br>';
                    echo '• Weekly progress monitoring';
                }
                echo '</div>';
                echo '</td>';
                echo '<td>';
                echo "<a href='plan_intervention.php?studentID=" . $row['gibbonPersonID'] . "&courseID=" . $row['gibbonCourseID'] . "' class='button remedial'>Plan Intervention</a>";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo "<div class='message'>";
            echo __('No students currently performing below 60%.');
            echo '</div>';
        }

    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __('An error has occurred.') . ' Error: ' . $e->getMessage();
        echo '</div>';
    }

    echo '</div>';

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo __('An error has occurred.') . ' Error: ' . $e->getMessage();
    echo '</div>';
}

// Add CSS styles
echo '<style>
.module-block {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.metric-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}

.metric-value {
    font-size: 2em;
    font-weight: bold;
    color: #3B7687;
    margin: 10px 0;
}

.metric-trend {
    font-size: 0.9em;
}

.metric-trend.positive {
    color: #28a745;
}

.metric-trend.neutral {
    color: #6c757d;
}

.filter-controls {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-item {
    flex: 1;
    min-width: 200px;
}

.filter-item label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.filter-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.filter-select:focus {
    border-color: #3B7687;
    outline: none;
}

.low-performance {
    background-color: #fff3f3 !important;
}

.attainment-cell.low-performance {
    color: #dc3545;
    font-weight: bold;
}

.analysis-item {
    margin: 10px 0;
    padding: 10px;
    background: #fff;
    border-left: 3px solid #dc3545;
    border-radius: 0 4px 4px 0;
}

.button.remedial {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    margin: 2px;
}

.button.remedial:hover {
    background: #c82333;
}

table.fullWidth {
    width: 100%;
    margin: 10px 0;
}

table.colorOddEven tr:nth-child(odd) {
    background: #f9f9f9;
}

table.colorOddEven td {
    padding: 8px;
}

table.colorOddEven th {
    padding: 8px;
    background: #444;
    color: white;
}

.message {
    background-color: #d9edf7;
    border: 1px solid #bce8f1;
    color: #31708f;
    padding: 15px;
    margin: 10px 0;
    border-radius: 4px;
}

.error {
    background-color: #f2dede;
    border: 1px solid #ebccd1;
    color: #a94442;
    padding: 15px;
    margin: 10px 0;
    border-radius: 4px;
}
</style>';

// Add JavaScript for filtering
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const studentFilter = document.getElementById("studentFilter");
    const courseFilter = document.getElementById("courseFilter");
    const showLowPerformers = document.getElementById("showLowPerformers");
    
    function filterTable() {
        const rows = document.querySelectorAll("#assessmentTable tr:not(.head)");
        const selectedStudent = studentFilter.value;
        const selectedCourse = courseFilter.value;
        const showOnlyLow = showLowPerformers.checked;
        
        rows.forEach(function(row) {
            let showRow = true;
            
            if (selectedStudent) {
                const studentId = row.getAttribute("data-student-id");
                showRow = showRow && (studentId === selectedStudent);
            }
            
            if (selectedCourse) {
                const courseId = row.getAttribute("data-course-id");
                showRow = showRow && (courseId === selectedCourse);
            }
            
            if (showOnlyLow) {
                const attainment = parseFloat(row.querySelector(".attainment-cell").textContent);
                showRow = showRow && (attainment < 60);
            }
            
            row.style.display = showRow ? "" : "none";
        });
    }
    
    studentFilter.addEventListener("change", filterTable);
    courseFilter.addEventListener("change", filterTable);
    showLowPerformers.addEventListener("change", filterTable);
});
</script>';
?> 