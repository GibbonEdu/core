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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Basic variables
$name = 'ChatBot';
$description = 'AI-powered teaching assistant for teachers';
$entryURL = 'chatbot.php';
$type = 'Additional';
$category = 'Learn';
$version = '1.0.00';
$author = 'Asley Smith';
$url = '';

// Module tables
$moduleTables = array();

$moduleTables[] = "
CREATE TABLE `gibbonChatBotFeedback` (
    `id` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `user_message` text NOT NULL,
    `ai_response` text NOT NULL,
    `feedback_type` enum('like','dislike') NOT NULL,
    `feedback_text` text,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "
CREATE TABLE `gibbonChatBotTraining` (
    `gibbonChatBotTrainingID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `question` text NOT NULL,
    `answer` text NOT NULL,
    `approved` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonChatBotTrainingID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "
CREATE TABLE `gibbonChatBotCourseMaterials` (
    `gibbonChatBotCourseMaterialsID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `type` varchar(50) NOT NULL,
    `description` text NULL,
    `filePath` varchar(255) NULL,
    `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
    `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
    `dateAdded` date NOT NULL,
    `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
    PRIMARY KEY (`gibbonChatBotCourseMaterialsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "
CREATE TABLE `gibbonChatBotStudentProgress` (
    `gibbonChatBotStudentProgressID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
    `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
    `progress` decimal(5,2) NOT NULL DEFAULT '0.00',
    `lastActivity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonChatBotStudentProgressID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "
CREATE TABLE `gibbonChatBotStudentAnalytics` (
    `gibbonChatBotStudentAnalyticsID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
    `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
    `analyticsData` text NOT NULL,
    `dateGenerated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonChatBotStudentAnalyticsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "
CREATE TABLE `gibbonChatBotInterventions` (
    `gibbonChatBotInterventionsID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
    `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
    `interventionType` varchar(50) NOT NULL,
    `description` text NOT NULL,
    `status` enum('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
    `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `dateModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`gibbonChatBotInterventionsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// Module settings
$gibbonSetting = array();
$gibbonSetting[] = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) VALUES ('ChatBot', 'deepseek_api_key', 'DeepSeek API Key', 'API key for DeepSeek AI service', '');";
$gibbonSetting[] = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) VALUES ('ChatBot', 'model_name', 'Model Name', 'DeepSeek model name (e.g., deepseek-chat)', 'deepseek-chat');";
$gibbonSetting[] = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value) VALUES ('ChatBot', 'max_tokens', 'Max Tokens', 'Maximum number of tokens to generate in responses', '4000');";

// Action rows
$actionRows = array();

$actionRows[] = [
    'name' => 'AI Teaching Assistant',
    'precedence' => '0',
    'category' => 'Learning',
    'description' => 'Access the AI teaching assistant',
    'URLList' => 'chatbot.php',
    'entryURL' => 'chatbot.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'Y',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'View Student Assessments',
    'precedence' => '1',
    'category' => 'Learning',
    'description' => 'View and analyze student assessment data',
    'URLList' => 'assessment_integration.php',
    'entryURL' => 'assessment_integration.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage Training',
    'precedence' => '2',
    'category' => 'Admin',
    'description' => 'Manage ChatBot training data',
    'URLList' => 'training.php,upload.php',
    'entryURL' => 'training.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Manage ChatBot',
    'precedence' => '3',
    'category' => 'Admin',
    'description' => 'Configure ChatBot settings',
    'URLList' => 'settings.php',
    'entryURL' => 'settings.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'N',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'N',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Learning Management',
    'precedence' => '4',
    'category' => 'Learning',
    'description' => 'Manage learning resources and content',
    'URLList' => 'learning_management.php,learning_management_addProcess.php,learning_management_edit.php,learning_management_delete.php',
    'entryURL' => 'learning_management.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'AI Learning & Feedback Analytics',
    'precedence' => '5',
    'category' => 'Admin',
    'description' => 'View and analyze ChatBot feedback and AI learning data',
    'URLList' => 'ai_learning.php',
    'entryURL' => 'ai_learning.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Feedback Analytics',
    'precedence' => '6',
    'category' => 'Admin',
    'description' => 'View and analyze ChatBot feedback data',
    'URLList' => 'feedback.php,api/feedback.php',
    'entryURL' => 'feedback.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

$actionRows[] = [
    'name' => 'Check Feedback Database',
    'precedence' => '7',
    'category' => 'Admin',
    'description' => 'Check and verify ChatBot feedback database status',
    'URLList' => 'db_check_feedback.php',
    'entryURL' => 'db_check_feedback.php',
    'defaultPermissionAdmin' => 'Y',
    'defaultPermissionTeacher' => 'Y',
    'defaultPermissionStudent' => 'N',
    'defaultPermissionParent' => 'N',
    'defaultPermissionSupport' => 'Y',
    'categoryPermissionStaff' => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent' => 'N',
    'categoryPermissionOther' => 'N'
];

// Return module configuration
return [
    'name' => $name,
    'version' => $version,
    'description' => $description,
    'author' => $author,
    'url' => $url,
    'entryURL' => $entryURL,
    'type' => $type,
    'category' => $category,
    'tables' => $moduleTables,
    'gibbonSettings' => $gibbonSetting,
    'actionRows' => $actionRows
];
