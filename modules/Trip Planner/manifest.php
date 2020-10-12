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

//Basic variables
$name = "Trip Planner";
$description = "A trip planner module for Gibbon.";
$entryURL = "trips_manage.php";
$type = "Additional";
$category = "Learn";
$version = "1.2.00";

$author = "Ray Clark";
$url = "https://github.com/raynichc/Trip-Planner";

//Tables
$tables = 0;
$moduleTables[$tables++] = "CREATE TABLE `tripPlannerApprovers` (
    `tripPlannerApproverID` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `sequenceNumber` int(4) NULL,
    `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
    `timestampCreator` timestamp NULL,
    `gibbonPersonIDUpdate` int(10) unsigned zerofill NULL,
    `timestampUpdate` timestamp NULL,
    `finalApprover` boolean DEFAULT 0 NULL,
    PRIMARY KEY (`tripPlannerApproverID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRequests` (
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `creatorPersonID` int(10) unsigned zerofill NOT NULL,
    `timestampCreation` timestamp,
    `title` varchar(60) NOT NULL,
    `description` text NOT NULL,
    `teacherPersonIDs` text NOT NULL,
    `studentPersonIDs` text NOT NULL,
    `location` text NOT NULL,
    `date` date NOT NULL,
    `startTime` time NULL,
    `endTime` time NULL,
    `riskAssessment` text NULL,
    `status` ENUM('Requested', 'Approved', 'Rejected', 'Cancelled', 'Awaiting Final Approval') DEFAULT 'Requested' NOT NULL,
    `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
    `gibbonPersonIDUpdate` int(10) unsigned zerofill NULL,
    `timestampUpdate` timestamp NULL,
    `endDate` date NULL DEFAULT NULL,
    `letterToParents` text NOT NULL,
    `messengerGroupID` int(8) unsigned zerofill NULL,
    PRIMARY KEY (`tripPlannerRequestID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerCostBreakdown` (
    `tripPlannerCostBreakdownID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,
    `title` varchar(60) NOT NULL,
    `description` text NOT NULL,
    `cost` decimal(12, 2) NOT NULL,
    PRIMARY KEY (`tripPlannerCostBreakdownID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRequestLog` (
    `tripPlannerRequestLogID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `action` ENUM('Request', 'Cancellation', 'Approval - Partial', 'Approval - Final', 'Rejection', 'Comment', 'Edit') NOT NULL,
    `comment` text NULL,
    `timestamp` timestamp NULL,
    PRIMARY KEY (`tripPlannerRequestLogID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "INSERT INTO `gibbonSetting` (`gibbonSettingID`, `scope`, `name`, `nameDisplay`, `description`, `value`)
VALUES
(NULL, 'Trip Planner', 'requestApprovalType', 'Request Approval Type', 'The type of approval that a trip request has to go through.', 'One Of'),
(NULL, 'Trip Planner', 'riskAssessmentTemplate', 'Risk Assessment Template', 'The template for the Risk Assessment.', ''),
(NULL, 'Trip Planner', 'missedClassWarningThreshold', 'Missed Class Warning Threshold', 'The threshold for displaying a warning that student has missed a class too many times. Set to 0 to disable warnings.', '5'),
(NULL, 'Trip Planner', 'riskAssessmentApproval', 'Risk Assessment Approval', 'If this is enabled the Risk Assessment becomes an optional field until the trip has gone through approval. After this a Final Approval is required before the trip becomes approved.', '1'),
(NULL, 'Trip Planner', 'requestEditing', 'Allow Requests to be Edited', 'If enabled Trip Requests may be edited by the owner, if edited the approval process is reset.', '0'),
(NULL, 'Trip Planner', 'defaultRiskTemplate', 'Default Risk Assessment Template', 'If selected then this template will be automatically applied to the form.', '0'),
(NULL, 'Trip Planner', 'expiredUnapprovedFilter', 'Disable View of Exipired Unapproved Requests', 'If selected then any trip which has not been approved and has passed the initial start date will no longer be shown.', '0'),
(NULL, 'Trip Planner', 'letterToParentsTemplate', 'Letter To Parents Template', 'Template text for Letter To Parents for new trips.', '')";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRequestPerson` (
    `tripPlannerRequestPersonID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,
    `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
    `role` ENUM('Student', 'Teacher') NOT NULL,
    PRIMARY KEY (`tripPlannerRequestPersonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRequestCover` (
    `tripPlannerRequestCoverID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,
    `gibbonCourseClassID` int(8) unsigned zerofill NOT NULL,
    `requiresCover` boolean DEFAULT TRUE NOT NULL,
    `date` date NOT NULL,
    PRIMARY KEY (`tripPlannerRequestCoverID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRiskTemplates` (
    `tripPlannerRiskTemplateID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `name` varchar(30) NOT NULL,
    `body` text NOT NULL,
    PRIMARY KEY (`tripPlannerRiskTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "CREATE TABLE `tripPlannerRequestDays` (
    `tripPlannerRequestDaysID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,
    `startDate` date NOT NULL,
    `endDate` date NOT NULL,
    `allDay` boolean NOT NULL,
    `startTime` time NOT NULL DEFAULT '00:00:00',
    `endTime` time NOT NULL DEFAULT '00:00:00',
    PRIMARY KEY (`tripPlannerRequestDaysID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[$tables++] = "INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`)
VALUES ('Trip Request Approval', 'Trip Planner', 'Manage Trips_full', 'Additional', 'All', 'Y');";


//Actions
$actionCount = 0;

$actionRows[$actionCount]["name"] = "Manage Trips";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Trips";
$actionRows[$actionCount]["description"] = "Manage trips.";
$actionRows[$actionCount]["URLList"] = "trips_manage.php";
$actionRows[$actionCount]["entryURL"] = "trips_manage.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "Y";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Manage Trips_full";
$actionRows[$actionCount]["precedence"] = "1";
$actionRows[$actionCount]["category"] = "Trips";
$actionRows[$actionCount]["description"] = "Manage trips.";
$actionRows[$actionCount]["URLList"] = "trips_manage.php";
$actionRows[$actionCount]["entryURL"] = "trips_manage.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Submit Request";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Trips";
$actionRows[$actionCount]["description"] = "Submit a trip request.";
$actionRows[$actionCount]["URLList"] = "trips_submitRequest.php";
$actionRows[$actionCount]["entryURL"] = "trips_submitRequest.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "Y";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Submit Request_all";
$actionRows[$actionCount]["precedence"] = "1";
$actionRows[$actionCount]["category"] = "Trips";
$actionRows[$actionCount]["description"] = "Submit a trip request.";
$actionRows[$actionCount]["URLList"] = "trips_submitRequest.php";
$actionRows[$actionCount]["entryURL"] = "trips_submitRequest.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Manage Approvers_view";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Settings";
$actionRows[$actionCount]["description"] = "Manage trip approvers.";
$actionRows[$actionCount]["URLList"] = "trips_manageApprovers.php";
$actionRows[$actionCount]["entryURL"] = "trips_manageApprovers.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Manage Approvers_add&edit";
$actionRows[$actionCount]["precedence"] = "1";
$actionRows[$actionCount]["category"] = "Settings";
$actionRows[$actionCount]["description"] = "Manage trip approvers.";
$actionRows[$actionCount]["URLList"] = "trips_manageApprovers.php,trips_addApprover.php,trips_editApprover.php";
$actionRows[$actionCount]["entryURL"] = "trips_manageApprovers.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Manage Approvers_full";
$actionRows[$actionCount]["precedence"] = "2";
$actionRows[$actionCount]["category"] = "Settings";
$actionRows[$actionCount]["description"] = "Manage trip approvers.";
$actionRows[$actionCount]["URLList"] = "trips_manageApprovers.php,trips_addApprover.php,trips_editApprover.php,trips_deleteApproverProcess.php";
$actionRows[$actionCount]["entryURL"] = "trips_manageApprovers.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Manage Trip Planner Settings";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Settings";
$actionRows[$actionCount]["description"] = "Manage Trip Planner Settings.";
$actionRows[$actionCount]["URLList"] = "trips_manageSettings.php";
$actionRows[$actionCount]["entryURL"] = "trips_manageSettings.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Risk Assessment Templates";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Settings";
$actionRows[$actionCount]["description"] = "Manage Risk Assessment Templates.";
$actionRows[$actionCount]["URLList"] = "trips_manageRiskTemplates.php";
$actionRows[$actionCount]["entryURL"] = "trips_manageRiskTemplates.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

$actionRows[$actionCount]["name"] = "Today's Trips";
$actionRows[$actionCount]["precedence"] = "0";
$actionRows[$actionCount]["category"] = "Reports";
$actionRows[$actionCount]["description"] = "Displays trips scheduled for today with the status requested, approved or awaiting final approval.";
$actionRows[$actionCount]["URLList"] = "trips_reportToday.php";
$actionRows[$actionCount]["entryURL"] = "trips_reportToday.php";
$actionRows[$actionCount]["defaultPermissionAdmin"] = "Y";
$actionRows[$actionCount]["defaultPermissionTeacher"] = "N";
$actionRows[$actionCount]["defaultPermissionStudent"] = "N";
$actionRows[$actionCount]["defaultPermissionParent"] = "N";
$actionRows[$actionCount]["defaultPermissionSupport"] = "N";
$actionRows[$actionCount]["categoryPermissionStaff"] = "Y";
$actionRows[$actionCount]["categoryPermissionStudent"] = "N";
$actionRows[$actionCount]["categoryPermissionParent"] = "N";
$actionRows[$actionCount]["categoryPermissionOther"] = "N";
$actionCount++;

?>
