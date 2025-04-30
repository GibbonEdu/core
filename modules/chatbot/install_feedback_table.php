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
use Gibbon\Domain\System\SettingGateway;

// Include Gibbon core
require_once __DIR__ . '/../../gibbon.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// Check access
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/install_feedback_table.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Set page breadcrumb
$page->breadcrumbs
    ->add(__('ChatBot'), 'chatbot.php')
    ->add(__('Install Feedback Table'));

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='" . $_SESSION[$guid]['absoluteURL'] . "'>" . __('Home') . "</a> > </div>";
echo "<div class='trailEnd'>" . __('Install Feedback Table') . "</div>";
echo "</div>";

try {
    // Check if table exists
    $tableExists = $connection2->query("SHOW TABLES LIKE 'gibbonChatBotFeedback'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div class='success'>";
        echo __('The feedback table already exists.');
        echo "</div>";
        
        // Verify table structure
        $result = $connection2->query("DESCRIBE gibbonChatBotFeedback");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'gibbonChatBotFeedbackID',
            'gibbonPersonID',
            'messageID',
            'feedback',
            'comment',
            'timestamp'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<div class='error'>";
            echo __('The table structure is incomplete. Missing columns: ') . implode(', ', $missingColumns);
            echo "</div>";
            
            // Add missing columns
            if (in_array('comment', $missingColumns)) {
                $connection2->query("ALTER TABLE gibbonChatBotFeedback ADD COLUMN comment TEXT DEFAULT NULL");
            }
            if (in_array('timestamp', $missingColumns)) {
                $connection2->query("ALTER TABLE gibbonChatBotFeedback ADD COLUMN timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            }
            
            echo "<div class='success'>";
            echo __('Missing columns have been added.');
            echo "</div>";
        }
    } else {
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS `gibbonChatBotFeedback` (
            `gibbonChatBotFeedbackID` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `gibbonPersonID` int(10) unsigned NOT NULL,
            `messageID` varchar(64) NOT NULL COMMENT 'Unique identifier for the message',
            `feedback` enum('like','dislike') NOT NULL,
            `comment` text DEFAULT NULL,
            `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`gibbonChatBotFeedbackID`),
            UNIQUE KEY `person_message` (`gibbonPersonID`,`messageID`) COMMENT 'Prevent duplicate feedback for the same message',
            KEY `message` (`messageID`),
            KEY `person` (`gibbonPersonID`),
            KEY `feedback` (`feedback`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $connection2->query($sql);
        
        echo "<div class='success'>";
        echo __('The feedback table has been created successfully.');
        echo "</div>";
    }
    
    // Add back button
    echo "<div class='linkTop'>";
    echo "<a href='" . $_SESSION[$guid]['absoluteURL'] . "/index.php?q=/modules/ChatBot/chatbot.php' class='button'>" . __('Back to ChatBot') . "</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo __('An error occurred while installing the feedback table:') . ' ' . $e->getMessage();
    echo "</div>";
    
    // Log the error
    error_log("Error installing feedback table: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} 