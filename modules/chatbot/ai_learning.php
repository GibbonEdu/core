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

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/ai_learning.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('AI Learning System'));

// Add CSS
echo "<link rel='stylesheet' type='text/css' href='" . $_SESSION[$guid]['absoluteURL'] . "/modules/ChatBot/css/chatbot.css'>";

/*
 * AI Learning System for ChatBot
 * 
 * This script analyzes user feedback to identify patterns in liked vs disliked responses
 * and can be used to improve future AI responses.
 */

// Check if the feedback table exists
try {
    $sql = "SHOW TABLES LIKE 'gibbonChatBotFeedback'";
    $result = $connection2->query($sql);
    $tableExists = ($result->rowCount() > 0);
    
    if (!$tableExists) {
        echo "<div class='error'>";
        echo "<strong>Error:</strong> The feedback table does not exist.";
        echo "</div>";
        echo "<p>Please install the feedback table first.</p>";
        echo "<a href='fix_feedback_table.php' class='button'>Fix Feedback Table</a>";
        echo "<a href='chatbot.php' class='button'>Back to ChatBot</a>";
        return;
    }

    // Get feedback statistics
    $sql = "SELECT COUNT(*) as count FROM gibbonChatBotFeedback";
    $result = $connection2->query($sql);
    $totalFeedback = $result->fetch()['count'] ?? 0;
    
    $sql = "SELECT COUNT(*) as count FROM gibbonChatBotFeedback WHERE feedback_type = 'like'";
    $result = $connection2->query($sql);
    $likeCount = $result->fetch()['count'] ?? 0;
    
    $sql = "SELECT COUNT(*) as count FROM gibbonChatBotFeedback WHERE feedback_type = 'dislike'";
    $result = $connection2->query($sql);
    $dislikeCount = $result->fetch()['count'] ?? 0;
    
    $satisfactionRate = ($totalFeedback > 0) ? round(($likeCount / $totalFeedback) * 100) : 0;
    
    // Display statistics
    echo "<h2>" . __('Feedback Statistics') . "</h2>";
    echo "<div class='column-no-break'>";
    echo "<div class='stats'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-title'>" . __('Total Feedback') . "</div>";
    echo "<div class='stat-value'>$totalFeedback</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-title'>" . __('Likes') . "</div>";
    echo "<div class='stat-value like'>$likeCount</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-title'>" . __('Dislikes') . "</div>";
    echo "<div class='stat-value dislike'>$dislikeCount</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-title'>" . __('Satisfaction Rate') . "</div>";
    echo "<div class='stat-value'>$satisfactionRate%</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Check if we have enough data for learning
    if ($totalFeedback < 5) {
        echo "<div class='warning'>";
        echo "<strong>" . __('Not enough data') . ":</strong> ";
        echo __('You need at least 5 feedback entries to generate meaningful insights.');
        echo sprintf(__('You currently have %d.'), $totalFeedback);
        echo "</div>";
        return;
    }

    // Get recent feedback for analysis
    $sql = "SELECT * FROM gibbonChatBotFeedback ORDER BY timestamp DESC LIMIT 10";
    $result = $connection2->query($sql);
    $recentFeedback = $result->fetchAll();

    // Create a table to display recent feedback
    $table = DataTable::create('recentFeedback');
    $table->setTitle(__('Recent Feedback'));

    $table->addColumn('timestamp', __('Date'))
        ->format(Format::using('date', 'timestamp'));
    
    $table->addColumn('feedback_type', __('Type'))
        ->format(function($row) {
            return ucfirst($row['feedback_type']);
        });

    $table->addColumn('user_message', __('Message'))
        ->format(function($row) {
            return Format::truncate($row['user_message'], 50);
        });

    echo $table->render($recentFeedback);

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>" . __('Error') . ":</strong> ";
    echo $e->getMessage();
    echo "</div>";
}

// Add back button
echo "<div class='linkTop'>";
echo "<a href='" . $_SESSION[$guid]['absoluteURL'] . "/index.php?q=/modules/ChatBot/chatbot.php' class='button'>" . __('Back to ChatBot') . "</a>";
echo "</div>"; 