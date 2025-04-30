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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;

// Module includes
require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Setup global variables
global $container, $gibbon, $session;

$session = $container->get('session');
$page = $container->get('page');

// Set up translations
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Common variables
$session->set('address', $_SERVER['REQUEST_URI']);

// Check access to this action
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/db_check_feedback.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Check Feedback Database'));

if (isset($_GET['return'])) {
    $page->returnProcess($_GET['return'], null);
}

// Get database connection
try {
    $pdo = $connection2;
} catch (Exception $e) {
    $page->addError('Database connection error: ' . $e->getMessage());
    return;
}

// Check if feedback table exists
try {
    $sql = "SHOW COLUMNS FROM gibbonChatBotFeedback";
    $result = $pdo->query($sql);
    $columnNames = array_column($result->fetchAll(), 'Field');
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo __('Error: The feedback table does not exist.');
    echo "</div>";
    exit;
}

// Get feedback statistics
try {
    $sql = "SELECT COUNT(*) as total FROM gibbonChatBotFeedback";
    $result = $pdo->query($sql);
    $totalFeedback = $result->fetch();
    
    $sql = "SELECT COUNT(*) as likes FROM gibbonChatBotFeedback WHERE feedback_type = 'like'";
    $result = $pdo->query($sql);
    $totalLikes = $result->fetch();
    
    $sql = "SELECT COUNT(*) as dislikes FROM gibbonChatBotFeedback WHERE feedback_type = 'dislike'";
    $result = $pdo->query($sql);
    $totalDislikes = $result->fetch();
    
    $total = $totalFeedback['total'] ?? 0;
    $likes = $totalLikes['likes'] ?? 0;
    $dislikes = $totalDislikes['dislikes'] ?? 0;
    $satisfactionRate = $total > 0 ? round(($likes / $total) * 100, 2) : 0;
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo __('Error: Could not fetch feedback statistics.');
    echo "</div>";
    exit;
}

// Create the summary table
$summaryTable = DataTable::create('feedbackSummary');
$summaryTable->setTitle(__('Feedback Summary'));

$summaryTable->addColumn('metric', __('Metric'))
    ->setClass('col-4');
$summaryTable->addColumn('value', __('Value'))
    ->setClass('col-8');

$summaryData = [
    ['metric' => __('Total Feedback'), 'value' => $total],
    ['metric' => __('Likes'), 'value' => $likes],
    ['metric' => __('Dislikes'), 'value' => $dislikes],
    ['metric' => __('Satisfaction Rate'), 'value' => $satisfactionRate . '%']
];

echo $summaryTable->render($summaryData);

// Get recent feedback
try {
    $sql = "SELECT id, user_message, ai_response, feedback_type, feedback_text, timestamp 
            FROM gibbonChatBotFeedback 
            ORDER BY timestamp DESC 
            LIMIT 10";
    $result = $pdo->query($sql);
    
    if ($result && $result->rowCount() > 0) {
        // Create the feedback table
        $feedbackTable = DataTable::create('recentFeedback');
        $feedbackTable->setTitle(__('Recent Feedback'));

        $feedbackTable->addColumn('timestamp', __('Time'))
            ->format(Format::using('dateTime'));
        $feedbackTable->addColumn('feedback_type', __('Type'))
            ->format(function($row) {
                return ucfirst($row['feedback_type']);
            });
        $feedbackTable->addColumn('user_message', __('User Message'))
            ->format(function($row) {
                return Format::truncate($row['user_message'], 50);
            });
        $feedbackTable->addColumn('ai_response', __('AI Response'))
            ->format(function($row) {
                return Format::truncate($row['ai_response'], 50);
            });
        $feedbackTable->addColumn('feedback_text', __('Feedback'))
            ->format(function($row) {
                return Format::truncate($row['feedback_text'], 50);
            });

        $feedbackTable->addActionColumn()
            ->addParam('feedbackID')
            ->format(function($row) {
                global $session;
                $actions = '';
                $actions .= "<a class='thickbox' href='#' onclick='viewDetails(".$row['id'].")'><img title='".__('View')."' src='".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a> ";
                return $actions;
            });

        echo $feedbackTable->render($result->fetchAll());

        // Add modal for viewing details
        ?>
        <div id="feedbackModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modalContent"></div>
            </div>
        </div>

        <script>
        function viewDetails(id) {
            // Fetch feedback details via AJAX
            fetch('getFeedbackDetails.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = `
                        <h3>Feedback Details</h3>
                        <p><strong>Time:</strong> ${data.timestamp}</p>
                        <p><strong>Type:</strong> ${data.feedback_type}</p>
                        <p><strong>User Message:</strong> ${data.user_message}</p>
                        <p><strong>AI Response:</strong> ${data.ai_response}</p>
                        <p><strong>Feedback:</strong> ${data.feedback_text}</p>
                    `;
                    document.getElementById('feedbackModal').style.display = 'block';
                });
        }

        // Close modal when clicking the close button or outside the modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('feedbackModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('feedbackModal')) {
                document.getElementById('feedbackModal').style.display = 'none';
            }
        }
        </script>

        <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        </style>
        <?php
    } else {
        echo "<div class='warning'>";
        echo __('No feedback entries found.');
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo __('Error: Could not fetch recent feedback.');
    echo "</div>";
}
?>

<style>
    .stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }
    .stat-card {
        flex: 1;
        background-color: white;
        border-left: 4px solid #2a7fff;
        padding: 15px;
        border-radius: 4px;
        min-width: 150px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    }
    .stat-title {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
    }
    .like {
        color: #4CAF50;
    }
    .dislike {
        color: #F44336;
    }
    .feedback-item {
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
        overflow: hidden;
    }
    .feedback-header {
        background-color: #f5f5f5;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .feedback-body {
        padding: 15px;
    }
    .feedback-message {
        margin-bottom: 15px;
    }
    .feedback-meta {
        font-size: 12px;
        color: #666;
        margin-top: 10px;
    }
    .feedback-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }
    .feedback-button {
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
    }
    .view-button {
        background-color: #2a7fff;
        color: white !important;
    }
    .delete-button {
        background-color: #F44336;
        color: white !important;
    }
</style>

<script>
    function viewFeedback(messageID) {
        // In a real implementation, this would fetch the full feedback data via AJAX
        alert('View feedback: ' + messageID);
    }
    
    function deleteFeedback(messageID) {
        if (confirm(<?php echo "'".__('Are you sure you want to delete this feedback entry?')."'"; ?>)) {
            // In a real implementation, this would send a delete request via AJAX
            alert('Delete feedback: ' + messageID);
        }
    }
</script>

<link rel='stylesheet' type='text/css' href='" . $_SESSION[$guid]['absoluteURL'] . "/modules/ChatBot/css/chatbot.css'>"; 