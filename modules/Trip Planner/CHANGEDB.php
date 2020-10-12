<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v0.0.01
$sql[$count][0]="0.0.01";
$sql[$count++][1]="-- First version, nothing to update";

//v0.0.02
$sql[$count][0]="0.0.02";
$sql[$count++][1]="
INSERT INTO gibbonSetting SET scope='Trip Planner', name='missedClassWarningThreshold', nameDisplay='Missed Class Warning Threshold', description='The threshold for displaying a warning that student has missed a class too many times. Set to 0 to disable warnings.', value='5';end
CREATE TABLE `tripPlannerRequestPerson` (`tripPlannerRequestPersonID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT, `tripPlannerRequestID` int(7) unsigned zerofill NOT NULL, `gibbonPersonID` int(10) unsigned zerofill NOT NULL, `role` ENUM('Student', 'Teacher') NOT NULL, PRIMARY KEY (`tripPlannerRequestPersonID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
";

$sql[$count][0]="0.0.03";
$sql[$count++][1]="
ALTER TABLE tripPlannerRequests DROP COLUMN totalCost;end
INSERT INTO gibbonAction SET name='Submit Request_all', precedence=1, category='', description='Submit a trip request.', URLList='trips_submitRequest.php', entryURL='trips_submitRequest.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='N', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='N', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Trip Planner');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Trip Planner' AND gibbonAction.name='Submit Request_all'));end
";

$sql[$count][0]="0.0.04";
$sql[$count++][1]="
ALTER TABLE tripPlannerRequests ADD COLUMN endDate date NULL;end
ALTER TABLE tripPlannerRequests CHANGE startTime startTime time NULL;end
ALTER TABLE tripPlannerRequests CHANGE endTime endTime time NULL;end
ALTER TABLE tripPlannerRequests CHANGE riskAssessment riskAssessment text NULL;end
ALTER TABLE tripPlannerRequests CHANGE status status ENUM('Requested', 'Approved', 'Rejected', 'Cancelled', 'Awaiting Final Approval') DEFAULT 'Requested' NOT NULL;end
INSERT INTO gibbonSetting SET scope='Trip Planner', name='riskAssessmentApproval', nameDisplay='Risk Assessment Approval', description='If this is enabled the Risk Assessment becomes an optional field until the trip has gone through approval. After this a Final Approval is required before the trip becomes approved.', value='1';end
ALTER TABLE tripPlannerApprovers ADD COLUMN finalApprover boolean DEFAULT 0 NULL;end
UPDATE gibbonAction SET category='Trips' WHERE (name='Manage Trips' OR name='Manage Trips_full' OR name='Submit Request' OR name='Submit Request_all') AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Trip Planner');end
UPDATE gibbonAction SET category='Settings' WHERE (name='Manage Approvers_view' OR name='Manage Approvers_add&edit' OR name='Manage Approvers_full' OR name='Manage Trip Planner Settings') AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Trip Planner');end
ALTER TABLE tripPlannerRequests ADD COLUMN letterToParents text NOT NULL;end
";

$sql[$count][0]="0.0.05";
$sql[$count++][1]="
ALTER TABLE tripPlannerRequests CHANGE endDate endDate date NULL DEFAULT NULL;end
UPDATE tripPlannerRequests SET endDate=NULL WHERE endDate='0000-00-00';end
";

$sql[$count][0]="0.0.06";
$sql[$count++][1]="
";

$sql[$count][0]="0.0.07";
$sql[$count++][1]="
INSERT INTO gibbonSetting SET scope='Trip Planner', name='requestEditing', nameDisplay='Allow Requests to be Edited', description='If enabled Trip Requests may be edited by the owner, if edited the approval process is reset.', value='0';end
ALTER TABLE tripPlannerRequestLog CHANGE `action` `action` ENUM('Request', 'Cancellation', 'Approval - Partial', 'Approval - Final', 'Rejection', 'Comment', 'Edit') NOT NULL;end
";

$sql[$count][0]="0.0.08";
$sql[$count++][1]="
";

$sql[$count][0]="0.0.09";
$sql[$count++][1]="
";

$sql[$count][0]="0.1.00";
$sql[$count++][1]="
CREATE TABLE tripPlannerRequestCover (`tripPlannerRequestCoverID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,`tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,`gibbonCourseClassID` int(8) unsigned zerofill NOT NULL,`requiresCover` boolean DEFAULT TRUE NOT NULL,PRIMARY KEY (`tripPlannerRequestCoverID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
";

$sql[$count][0]="0.1.01";
$sql[$count++][1]="
";

$sql[$count][0]="0.1.10";
$sql[$count++][1]="
CREATE TABLE `tripPlannerRiskTemplates` (`tripPlannerRiskTemplateID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT, `name` varchar(30) NOT NULL, `body` text NOT NULL, PRIMARY KEY (`tripPlannerRiskTemplateID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
INSERT INTO gibbonAction SET name='Risk Assessment Templates', precedence=0, category='Settings', description='Manage Risk Assessment Templates.', URLList='trips_manageRiskTemplates.php', entryURL='trips_manageRiskTemplates.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='N', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='N', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Trip Planner');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Trip Planner' AND gibbonAction.name='Risk Assessment Templates'));end
INSERT INTO gibbonSetting SET scope='Trip Planner', name='defaultRiskTemplate', nameDisplay='Default Risk Assessment Template', description='If selected then this template will be automatically applied to the form.', value='0';end
UPDATE gibbonSetting SET nameDisplay='Custom Risk Assessment Template', description='The custom template for the Risk Assessment.' WHERE scope='Trip Planner' AND name='riskAssessmentTemplate';end
";

$sql[$count][0]="0.2.00";
$sql[$count++][1]="
CREATE TABLE `tripPlannerRequestDays` (`tripPlannerRequestDaysID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,`tripPlannerRequestID` int(7) unsigned zerofill NOT NULL,`startDate` date NOT NULL,`endDate` date NOT NULL,`allDay` boolean NOT NULL,`startTime` time NOT NULL DEFAULT '00:00:00',`endTime` time NOT NULL DEFAULT '00:00:00',PRIMARY KEY (`tripPlannerRequestDaysID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
ALTER TABLE `gibbonCourseClassPerson` ADD INDEX `tripCourseClassPersonID` (`gibbonPersonID`);end
ALTER TABLE `tripPlannerRequestCover` ADD COLUMN `date` date NOT NULL;end";

$sql[$count][0]="0.2.01";
$sql[$count++][1]="";

$sql[$count][0]="0.2.10";
$sql[$count++][1]="
ALTER TABLE `tripPlannerRequests` ADD COLUMN `messengerGroupID` int(8) unsigned zerofill NULL;end
";

$sql[$count][0]="0.2.11";
$sql[$count++][1]="
INSERT INTO gibbonSetting SET scope='Trip Planner', name='expiredUnapprovedFilter', nameDisplay='Disable View of Exipired Unapproved Requests', description='If selected then any trip which has not been approved and has passed the initial start date will no longer be shown.', value='0';end
";

$sql[$count][0]="0.2.12";
$sql[$count++][1]="
INSERT INTO gibbonSetting SET scope='Trip Planner', name='letterToParentsTemplate', nameDisplay='Letter To Parents Template', description='Template text for Letter To Parents for new trips.', value='';end
";

$sql[$count][0]="0.2.13";
$sql[$count++][1]="";

$sql[$count][0]="0.3.00";
$sql[$count++][1]="";

$sql[$count][0]="1.0.00";
$sql[$count++][1]="
INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`)
VALUES ('Trip Request Approval', 'Trip Planner', 'Manage Trips_full', 'Additional', 'All', 'Y');end
";

$sql[$count][0]="1.0.01";
$sql[$count++][1]="";

$sql[$count][0]="1.1.00";
$sql[$count++][1]="";

$sql[$count][0]="1.1.01";
$sql[$count++][1]="";

$sql[$count][0]="1.2.00";
$sql[$count++][1]="
INSERT INTO gibbonAction SET name='Today\'s Trips', precedence=0, category='Reports', description='Displays trips scheduled for today with the status requested, approved or awaiting final approval.', URLList='trips_reportToday.php', entryURL='trips_reportToday.php', defaultPermissionAdmin='Y', defaultPermissionTeacher='N', defaultPermissionStudent='N', defaultPermissionParent='N', defaultPermissionSupport='N', categoryPermissionStaff='Y', categoryPermissionStudent='N', categoryPermissionParent='N', categoryPermissionOther='N', gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Trip Planner');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Trip Planner' AND gibbonAction.name='Today\'s Trips'));end

";
?>
