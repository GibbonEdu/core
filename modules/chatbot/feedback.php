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

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

// Module includes
require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Basic initialization
$page = $container->get('page');
$session = $container->get('session');

// Log all incoming requests
error_log("=== Feedback Request ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION[$guid], true));
error_log("Headers: " . print_r(getallheaders(), true));

// Handle POST request for feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get CSRF token from POST data
    $token = $_POST['gibbonCSRFToken'] ?? '';
    $storedToken = $_SESSION[$guid]['gibbonCSRFToken'] ?? '';
    
    error_log("Received token: " . $token);
    error_log("Stored token: " . $storedToken);
    
    // Verify CSRF token
    if (empty($token) || empty($storedToken) || $token !== $storedToken) {
        error_log("CSRF token validation failed");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit();
    }

    // Get POST data
    $messageID = $_POST['messageID'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'] ?? null;

    error_log("Processing feedback - MessageID: $messageID, Feedback: $feedback, PersonID: $gibbonPersonID");

    // Validate input
    if (empty($messageID) || empty($feedback) || !in_array($feedback, ['like', 'dislike'])) {
        error_log("Invalid input parameters");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
        exit();
    }

    if (empty($gibbonPersonID)) {
        error_log("No gibbonPersonID found in session");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID not found in session']);
        exit();
    }

    try {
        $connection2 = $container->get('db');
        
        // Log database connection status
        error_log("Database connection established");
        
        // Check if feedback already exists
        $sql = "SELECT gibbonChatBotFeedbackID, feedback FROM gibbonChatBotFeedback WHERE messageID=? AND gibbonPersonID=?";
        error_log("Executing query: $sql with params: [$messageID, $gibbonPersonID]");
        
        $existingFeedback = $connection2->selectOne($sql, [$messageID, $gibbonPersonID]);
        error_log("Existing feedback check result: " . print_r($existingFeedback, true));

        if ($existingFeedback) {
            if ($existingFeedback['feedback'] === $feedback) {
                // Remove existing feedback if same type
                error_log("Removing existing feedback with ID: " . $existingFeedback['gibbonChatBotFeedbackID']);
                $connection2->delete('gibbonChatBotFeedback', ['gibbonChatBotFeedbackID' => $existingFeedback['gibbonChatBotFeedbackID']]);
                error_log("Removed existing feedback");
                echo json_encode([
                    'success' => true,
                    'action' => 'removed',
                    'db_operation' => ['type' => 'delete', 'id' => $existingFeedback['gibbonChatBotFeedbackID'], 'affected_rows' => 1]
                ]);
            } else {
                // Update existing feedback if different type
                error_log("Updating existing feedback with ID: " . $existingFeedback['gibbonChatBotFeedbackID']);
                $connection2->update('gibbonChatBotFeedback', [
                    'feedback' => $feedback,
                    'timestamp' => date('Y-m-d H:i:s')
                ], ['gibbonChatBotFeedbackID' => $existingFeedback['gibbonChatBotFeedbackID']]);
                error_log("Updated existing feedback");
                echo json_encode([
                    'success' => true,
                    'action' => 'updated',
                    'db_operation' => ['type' => 'update', 'id' => $existingFeedback['gibbonChatBotFeedbackID'], 'affected_rows' => 1]
                ]);
            }
        } else {
            // Insert new feedback
            $data = [
                'messageID' => $messageID,
                'gibbonPersonID' => $gibbonPersonID,
                'feedback' => $feedback,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            error_log("Inserting new feedback with data: " . print_r($data, true));
            $id = $connection2->insert('gibbonChatBotFeedback', $data);
            error_log("New feedback inserted with ID: $id");
            echo json_encode([
                'success' => true,
                'action' => 'saved',
                'db_operation' => ['type' => 'insert', 'id' => $id, 'affected_rows' => 1]
            ]);
        }
        exit();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// For GET requests, check access and show feedback analytics
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/feedback.php')) {
    error_log("Access denied to feedback.php");
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Log debug information
$debugInfo = array(
    'Time' => date('Y-m-d H:i:s'),
    'Script' => __FILE__,
    'Session' => isset($_SESSION[$guid]) ? 'Set' : 'Not Set',
    'GUID' => $guid ?? 'Not Set',
    'User ID' => $_SESSION[$guid]['gibbonPersonID'] ?? 'Not Set',
    'Username' => $_SESSION[$guid]['username'] ?? 'Not Set',
    'Role' => $_SESSION[$guid]['gibbonRoleIDPrimary'] ?? 'Not Set',
    'Action Path' => 'modules/ChatBot/feedback.php'
);

// Log the debug information
error_log("=== Access Check Debug Info ===\n" . print_r($debugInfo, true));

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Feedback Analytics'));

// Get database connection
$connection2 = $container->get('db');

// Set page title
echo "<h2>" . __('Feedback Analytics') . "</h2>";

// Add sidebar menu
$page->addSidebarExtra('
    <div class="column-no-break">
        <h2>' . __('ChatBot Menu') . '</h2>
        <ul class="moduleMenu">
            <li><a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/ChatBot/chatbot.php">' . __('AI Teaching Assistant') . '</a></li>
            <li><a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/ChatBot/assessment_integration.php">' . __('Assessment Integration') . '</a></li>
            <li><a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/ChatBot/learning_management.php">' . __('Learning Management') . '</a></li>
            <li><a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/ChatBot/settings.php">' . __('Settings') . '</a></li>
            <li class="selected"><a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/ChatBot/feedback.php">' . __('Feedback Analytics') . '</a></li>
        </ul>
    </div>
');

// Add CSS and JS
echo "<link rel='stylesheet' type='text/css' href='" . $_SESSION[$guid]['absoluteURL'] . "/modules/ChatBot/css/chatbot.css'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";

// Get feedback statistics
try {
    // Check if table exists
    $tableCheck = $connection2->selectOne("SHOW TABLES LIKE 'gibbonChatBotFeedback'");
    if (!$tableCheck) {
        $page->addAlert('error', 'The feedback table does not exist. Please install it first.');
        $page->addContent('<div class="standardForm">
            <p>The feedback system requires the database table to be installed first.</p>
            <p><a href="' . $session->get('absoluteURL') . '/modules/ChatBot/install_feedback_table.php" class="button">Install Feedback Table</a></p>
        </div>');
        return;
    }
    
    // Table exists, proceed with queries
    
    // Total feedback count
    $totalFeedback = $connection2->selectOne("SELECT COUNT(*) as total FROM gibbonChatBotFeedback");
    $totalFeedback = $totalFeedback['total'] ?? 0;
    
    // Feedback by type
    $feedbackByType = $connection2->select("SELECT feedback_type, COUNT(*) as count FROM gibbonChatBotFeedback GROUP BY feedback_type");
    
    // Format data
    $likesCount = 0;
    $dislikesCount = 0;
    
    foreach ($feedbackByType->fetchAll() as $item) {
        if ($item['feedback_type'] === 'like') {
            $likesCount = $item['count'];
        } else if ($item['feedback_type'] === 'dislike') {
            $dislikesCount = $item['count'];
        }
    }
    
    // Get feedback by time
    $feedbackByTime = $connection2->select("SELECT 
        DATE(timestamp) as date, 
        feedback_type, 
        COUNT(*) as count 
      FROM gibbonChatBotFeedback 
      WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      GROUP BY DATE(timestamp), feedback_type
      ORDER BY date");
    
    // Format data for time series chart
    $timeLabels = [];
    $likesData = [];
    $dislikesData = [];
    
    $dateMap = [];
    foreach ($feedbackByTime->fetchAll() as $item) {
        if (!isset($dateMap[$item['date']])) {
            $dateMap[$item['date']] = [
                'like' => 0,
                'dislike' => 0
            ];
        }
        
        $dateMap[$item['date']][$item['feedback_type']] = $item['count'];
    }
    
    // Sort dates and fill in the data arrays
    ksort($dateMap);
    foreach ($dateMap as $date => $counts) {
        $timeLabels[] = $date;
        $likesData[] = $counts['like'];
        $dislikesData[] = $counts['dislike'];
    }
    
} catch (PDOException $e) {
    $page->addError('Database query error: ' . $e->getMessage());
    return;
}
?>

<div class="dashboard-container">
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-title">Total Feedback</div>
            <div class="stat-value"><?php echo $totalFeedback; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Likes</div>
            <div class="stat-value"><?php echo $likesCount; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Dislikes</div>
            <div class="stat-value"><?php echo $dislikesCount; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Satisfaction Rate</div>
            <div class="stat-value">
                <?php 
                if ($totalFeedback > 0) {
                    echo round(($likesCount / $totalFeedback) * 100) . '%';
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="dashboard-charts">
        <div class="chart-container">
            <h3>Feedback Distribution</h3>
            <canvas id="feedbackPieChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Feedback Over Time (Last 30 Days)</h3>
            <canvas id="feedbackLineChart"></canvas>
        </div>
    </div>
    
    <div class="feedback-table-container">
        <h3>Recent Feedback</h3>
        
        <?php
        // Create a filterable table
        $table = DataTable::create('feedbackTable');
        $table->setTitle('Recent Feedback');
        
        $table->addHeaderAction('export', __('Export'))
            ->setURL($_SESSION[$guid]['absoluteURL'] . '/modules/ChatBot/api/export_feedback.php')
            ->setIcon('download')
            ->directLink()
            ->displayLabel();
        
        $table->addColumn('id', __('ID'))
            ->format(function($row) {
                return $row['id'];
            });
        
        $table->addColumn('feedback_type', __('Type'))
            ->format(function($row) {
                if ($row['feedback_type'] == 'like') {
                    return '<span class="text-success"><i class="fas fa-thumbs-up"></i> Like</span>';
                } else {
                    return '<span class="text-danger"><i class="fas fa-thumbs-down"></i> Dislike</span>';
                }
            });
            
        $table->addColumn('user_message', __('User Message'))
            ->format(function($row) {
                return Format::truncate($row['user_message'], 50);
            });
            
        $table->addColumn('ai_response', __('AI Response'))
            ->format(function($row) {
                return Format::truncate($row['ai_response'], 50);
            });
            
        $table->addColumn('feedback_text', __('Feedback'))
            ->format(function($row) {
                return Format::truncate($row['feedback_text'] ?? '', 50);
            });
        
        $table->addColumn('timestamp', __('Date & Time'))
            ->format(Format::using('dateTime', 'timestamp'));
        
        // Add data to the table
        try {
            $recentFeedback = $connection2->select("SELECT 
                id,
                user_message,
                ai_response,
                feedback_type,
                feedback_text,
                timestamp
              FROM gibbonChatBotFeedback
              ORDER BY timestamp DESC
              LIMIT 10");
            
            $feedbackData = $recentFeedback->fetchAll();
            $table->withData(new DataSet($feedbackData));
            
            echo $table->render($feedbackData);
            
        } catch (PDOException $e) {
            $page->addError('Error fetching recent feedback: ' . $e->getMessage());
        }
        ?>
    </div>
</div>

<style>
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.dashboard-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.stat-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 15px;
    flex: 1;
    min-width: 150px;
}

.stat-title {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.dashboard-charts {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.chart-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 15px;
    flex: 1;
    min-width: 300px;
}

.chart-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
    color: #333;
}

.feedback-table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 15px;
}

.feedback-table-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
    color: #333;
}

.text-success {
    color: #4CAF50;
}

.text-danger {
    color: #F44336;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pie chart for feedback distribution
    const pieCtx = document.getElementById('feedbackPieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: ['Likes', 'Dislikes'],
            datasets: [{
                data: [<?php echo $likesCount; ?>, <?php echo $dislikesCount; ?>],
                backgroundColor: [
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(244, 67, 54, 0.8)'
                ],
                borderColor: [
                    'rgba(76, 175, 80, 1)',
                    'rgba(244, 67, 54, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = <?php echo $totalFeedback; ?>;
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Line chart for feedback over time
    const lineCtx = document.getElementById('feedbackLineChart').getContext('2d');
    const lineChart = new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($timeLabels); ?>,
            datasets: [
                {
                    label: 'Likes',
                    data: <?php echo json_encode($likesData); ?>,
                    borderColor: 'rgba(76, 175, 80, 1)',
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Dislikes',
                    data: <?php echo json_encode($dislikesData); ?>,
                    borderColor: 'rgba(244, 67, 54, 1)',
                    backgroundColor: 'rgba(244, 67, 54, 0.2)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            }
        }
    });
});
</script> 