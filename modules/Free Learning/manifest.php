<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

//This file describes the module, including database tables

//Basic variables
$name = 'Free Learning';
$description = "Free Learning is a module which enables a student-focused and student-driven pedagogy that goes by the same name as the module (see <a href='http://rossparker.org/free-learning'>http://rossparker.org/free-learning</a> for more).";
$entryURL = 'units_browse.php';
$type = 'Additional';
$category = 'Learn';
$version = '5.25.06';
$author = "Gibbon Foundation";
$url = "https://gibbonedu.org";

//Module tables
$moduleTables[] = "CREATE TABLE `freeLearningUnit` (
`freeLearningUnitID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonDepartmentIDList` text,
  `course` VARCHAR(50) NULL DEFAULT NULL,
  `name` varchar(40) NOT NULL,
  `logo` text,
  `active` enum('Y','N') DEFAULT 'Y',
  `editLock` enum('Y','N') DEFAULT 'N',
  `grouping` VARCHAR(255) NOT NULL,
  `gibbonYearGroupIDMinimum` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `difficulty` varchar(255) NOT NULL,
  `blurb` text NOT NULL,
  `outline` text NOT NULL,
  `license` varchar(50) DEFAULT NULL,
  `assessable` enum('Y','N') NULL DEFAULT NULL,
  `availableStudents` enum('Y','N') NOT NULL DEFAULT 'Y',
  `availableStaff` enum('Y','N') NOT NULL DEFAULT 'Y',
  `availableParents` enum('Y','N') NOT NULL DEFAULT 'Y',
  `availableOther` enum('Y','N') NOT NULL DEFAULT 'N',
  `sharedPublic` enum('Y','N') DEFAULT NULL,
  `schoolMentorCompletors` enum('N','Y') DEFAULT NULL,
  `schoolMentorCustom` text,
  `schoolMentorCustomRole` int(3) unsigned zerofill NULL DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
  `studentReflectionText` TEXT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`freeLearningUnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = 'CREATE TABLE `freeLearningUnitPrerequisite` (
`freeLearningUnitPrerequisiteID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `freeLearningUnitIDPrerequisite` int(10) unsigned zerofill NOT NULL,
  PRIMARY KEY (`freeLearningUnitPrerequisiteID`),
  KEY `prerequisite` (`freeLearningUnitID`, `freeLearningUnitIDPrerequisite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

$moduleTables[] = 'CREATE TABLE `freeLearningUnitBlock` (
`freeLearningUnitBlockID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` INT(3) UNSIGNED NULL DEFAULT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  PRIMARY KEY (`freeLearningUnitBlockID`),
  KEY `length` (`length`),
  KEY `freeLearningUnitID` (`freeLearningUnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

$moduleTables[] = 'CREATE TABLE `freeLearningUnitOutcome` (
`freeLearningUnitOutcomeID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonOutcomeID` int(8) unsigned zerofill NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`freeLearningUnitOutcomeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

$moduleTables[] = 'CREATE TABLE `freeLearningUnitAuthor` (
`freeLearningUnitAuthorID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(8) unsigned zerofill DEFAULT NULL,
  `surname` varchar(30) NOT NULL,
  `preferredName` varchar(30) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`freeLearningUnitAuthorID`),
  KEY `gibbonPersonID` (`gibbonPersonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

$moduleTables[] = "CREATE TABLE `freeLearningUnitStudent` (
`freeLearningUnitStudentID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonIDStudent` int(10) unsigned zerofill DEFAULT NULL,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `enrolmentMethod` enum('class','schoolMentor','externalMentor') NOT NULL DEFAULT 'class',
  `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `gibbonPersonIDSchoolMentor` int(10) unsigned zerofill DEFAULT NULL,
  `emailExternalMentor` varchar(255) DEFAULT NULL,
  `nameExternalMentor` varchar(255) DEFAULT NULL,
  `grouping` ENUM('Individual','Pairs','Threes','Fours','Fives') NOT NULL,
  `collaborationKey` VARCHAR(20) NULL DEFAULT NULL,
  `confirmationKey` varchar(20) DEFAULT NULL,
  `status` enum('Current','Current - Pending','Complete - Pending','Complete - Approved','Exempt','Evidence Not Yet Approved') NOT NULL DEFAULT 'Current',
  `timestampJoined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampCompletePending` timestamp NULL DEFAULT NULL,
  `timestampCompleteApproved` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDApproval` int(10) unsigned zerofill DEFAULT NULL,
  `evidenceType` enum('File','Link') NULL DEFAULT NULL,
  `evidenceLocation` text NULL DEFAULT NULL,
  `commentStudent` text NULL DEFAULT NULL,
  `commentApproval` text NULL DEFAULT NULL,
  `exemplarWork` enum('N','Y') NOT NULL DEFAULT 'N',
  `exemplarWorkThumb` text NULL DEFAULT NULL,
  `exemplarWorkLicense` varchar(255) NULL DEFAULT NULL,
  `exemplarWorkEmbed` text NULL DEFAULT NULL,
  PRIMARY KEY (`freeLearningUnitStudentID`),
  KEY `gibbonPersonIDStudent` (`gibbonPersonIDStudent`),
  KEY `freeLearningUnitID` (`freeLearningUnitID`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `freeLearningBadge` (
  `freeLearningBadgeID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `badgesBadgeID` int(8) unsigned zerofill NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `unitsCompleteTotal` int(3) DEFAULT NULL,
  `unitsCompleteThisYear` int(3) DEFAULT NULL,
  `unitsCompleteDepartmentCount` int(3) DEFAULT NULL,
  `unitsCompleteIndividual` int(3) DEFAULT NULL,
  `unitsCompleteGroup` int(3) DEFAULT NULL,
  `difficultyLevelMaxAchieved` varchar(255) DEFAULT NULL,
  `specificUnitsComplete` text DEFAULT NULL,
  PRIMARY KEY (`freeLearningBadgeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `freeLearningMentorGroup` (
    `freeLearningMentorGroupID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR(100) NOT NULL ,
    `assignment` ENUM('Manual','Automatic') NOT NULL DEFAULT 'Manual',
    `gibbonCustomFieldID` INT(4) UNSIGNED ZEROFILL NULL,
    `fieldValue` VARCHAR(100) NULL,
    PRIMARY KEY (`freeLearningMentorGroupID`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `freeLearningMentorGroupPerson` (
    `freeLearningMentorGroupPersonID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
    `freeLearningMentorGroupID` INT(10) UNSIGNED ZEROFILL NOT NULL ,
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL ,
    `role` ENUM('Student','Mentor') NOT NULL DEFAULT 'Student',
    PRIMARY KEY (`freeLearningMentorGroupPersonID`),
    UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`,`freeLearningMentorGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

//Settings
//gibbonSettings entries
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'difficultyOptions', 'Difficulty Options', 'The range of difficulty options available when creating units, from lowest to highest, as a comma-separated list.', 'Low,Medium,High');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'publicUnits', 'Public Units', 'Should selected units be made available to members of the public, via the home page?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'unitOutlineTemplate', 'Unit Outline Template', 'An HTML template to be used as the default for all new units.', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'mapLink', 'Map Link', 'A URL pointing to a map of the available units.', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'learningAreaRestriction', 'Learning Area Restriction', 'Should unit creation be limited to own Learning Areas?', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'enableClassEnrolment', 'Enable Class Enrolment', 'Should class enrolment be an option for learners?', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'enableSchoolMentorEnrolment', 'Enable School Mentor Enrolment', 'Should school mentor enrolment be an option for learners?', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'enableExternalMentorEnrolment', 'Enable External Mentor Enrolment', 'Should external mentor enrolment be an option for learners?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'customField', 'Custom Field', 'A custom field with context Person, to display under student names in Manage Enrolment.', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'collaborativeAssessment', 'Collaborative Assessment', 'Should students be working together submit and assess together?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'maxMapSize', 'Maximum Map Size', 'How large should the biggest map be, before maps are disabled?', '99');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'certificatesAvailable', 'Certificates Available', 'Should certificates be made available on unit completion?', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'certificateTemplate', 'Certificate Template', 'HTML and Twig template for PDF certificates.', '<div style=\"text-align: center;\">\r\n <img style=\"height: 100px; width: 400px; background-color: #ffffff; padding: 4px;\" src=\"{{ absoluteURL }}/{{ organisationLogo }}\" />\r\n</div>\r\n\r\n<div style=\"padding-top: 30mm; font-style: italic; font-size: 150%; text-align: center;\">\r\n <p style=\"\">This document certifies that</p>\r\n \r\n <h1 style=\"font-size: 220%;\">{{ officialName }}</h1>\r\n \r\n <p>has successfully completed</p>\r\n \r\n <h1 style=\"font-size: 220%;\">{{ unitName }}</h1>\r\n \r\n <p>{% if length > 0 %}</p>\r\n <p>undertaking an estimated {{ length }} minutes work on</p>\r\n <p>{% endif %}</p>\r\n \r\n <h1 style=\"font-size: 220%;\">{{ organisationName }} Free Learning</h1>\r\n \r\n <p>Approved on {{ dateComplete }}</p>\r\n</div>');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'certificateOrientation', 'Certificate Orientation', 'Page orientation for PDF certificates.', 'P');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'autoAcceptMentorGroups', 'Enable Mentor Group Auto Accept', 'Should mentorship requests that are part of a mentor group be automatically accepted?', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableParentEvidence', 'Disable Parent Evidence', 'Hide student evidence from parents?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'enableManualBadges', 'Enable Manual Badges', 'Allow badges to be granted by hand during unit approval?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableLearningAreas', 'Disable Learning Areas', 'Remove Learning Areas from Browse Units filters?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableMyClasses', 'Disable My Classes', 'Remove My Classes from Class menu in Enrolment tab?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableLearningAreaMentors', 'Disable Learning Area Mentors', 'Remove Learning Area-based mentors from School Mentor menu in Enrolment tab?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableOutcomes', 'Disable Outcomes', 'Remove all Outcomes-related functionality?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'disableExemplarWork', 'Disable Exemplar Work', 'Remove all Exemplar Work-related functionality?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'showContentOnEnrol', 'Show Content On Enrol', 'Prevent users from seeing unit content until enrolled?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'studentEvidencePrompt', 'Student Evidence Prompt', 'The time since enrolment, in days, after which to prompt for evidence.', '31');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'mentorshipAcceptancePrompt', 'Mentorship Acceptance Prompt', 'The time since mentorship request, in days, after which to prompt for approval.', '31');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'evidenceOutstandingPrompt', 'Evidence Outstanding Prompt', 'The time since evidence submission, in days, after which to prompt for mentor action.', '31');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'genderOnFeedback', 'Gender on Feedback', 'Show student gender when giving unit completion feedback?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'smartBlocksTemplate', 'Smart Blocks Template', 'Uses Smart Blocks from the selected unit as a template for new units.', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'availableSubmissionTypes', 'Available Submission Types', 'Determines which types of submissions a learner can make.', 'Link/File');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'bigDataSchool', 'Big Data School', 'Enables various defaults and filters for schools producing lots of data.', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'defaultBrowseView', 'Default View in Browse Units', 'Which view should the browse units page default to?', 'map');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'defaultBrowseCourse', 'Default Course in Browse Units', 'If set, the Browse Units page will show a certain course by default.', '');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'collapsedSmartBlocks', 'Collapsed Smart Blocks', 'Should Smart Blocks be collapsed when viewing a unit?', 'N');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'unitHistoryChart', 'Unit History Chart', 'Which chart type should be used in the Unit History table?', 'Doughnut');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'outcomesIntroduction', 'Outcomes Introduction', 'Introductory HTML content to display before the data table on the unit details Outcomes tab.', '')";
$gibbonSetting[] = "INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`) VALUES ('Evidence Submitted', 'Free Learning', 'Browse Units_all', 'Additional', 'All', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`) VALUES ('Unit Comment', 'Free Learning', 'Browse Units_all', 'Additional', 'All', 'Y');";
$gibbonSetting[] = "INSERT INTO `gibbonNotificationEvent` (`event`, `moduleName`, `actionName`, `type`, `scopes`, `active`) VALUES ('Unit Feedback', 'Free Learning', 'Browse Units_all', 'Additional', 'All', 'Y');";

//Action rows
$actionRows[0]['name'] = 'Manage Units_all';
$actionRows[0]['precedence'] = '1';
$actionRows[0]['category'] = 'Admin';
$actionRows[0]['description'] = 'Allows privileged users to manage all Free Learning units.';
$actionRows[0]['URLList'] = 'units_manage.php, units_manage_add.php, units_manage_edit.php, units_manage_delete.php';
$actionRows[0]['entryURL'] = 'units_manage.php';
$actionRows[0]['defaultPermissionAdmin'] = 'Y';
$actionRows[0]['defaultPermissionTeacher'] = 'N';
$actionRows[0]['defaultPermissionStudent'] = 'N';
$actionRows[0]['defaultPermissionParent'] = 'N';
$actionRows[0]['defaultPermissionSupport'] = 'N';
$actionRows[0]['categoryPermissionStaff'] = 'Y';
$actionRows[0]['categoryPermissionStudent'] = 'N';
$actionRows[0]['categoryPermissionParent'] = 'N';
$actionRows[0]['categoryPermissionOther'] = 'N';

$actionRows[1]['name'] = 'Manage Units_learningAreas';
$actionRows[1]['precedence'] = '0';
$actionRows[1]['category'] = 'Admin';
$actionRows[1]['description'] = 'Allows a privileged user within a learning area to manage all Free Learning units with their learning area.';
$actionRows[1]['URLList'] = 'units_manage.php, units_manage_add.php, units_manage_edit.php, units_manage_delete.php';
$actionRows[1]['entryURL'] = 'units_manage.php';
$actionRows[1]['defaultPermissionAdmin'] = 'N';
$actionRows[1]['defaultPermissionTeacher'] = 'Y';
$actionRows[1]['defaultPermissionStudent'] = 'N';
$actionRows[1]['defaultPermissionParent'] = 'N';
$actionRows[1]['defaultPermissionSupport'] = 'N';
$actionRows[1]['categoryPermissionStaff'] = 'Y';
$actionRows[1]['categoryPermissionStudent'] = 'N';
$actionRows[1]['categoryPermissionParent'] = 'N';
$actionRows[1]['categoryPermissionOther'] = 'N';

$actionRows[2]['name'] = 'Manage Settings';
$actionRows[2]['precedence'] = '0';
$actionRows[2]['category'] = 'Admin';
$actionRows[2]['description'] = 'Allows a privileged user to manage Free Learning settings.';
$actionRows[2]['URLList'] = 'settings_manage.php';
$actionRows[2]['entryURL'] = 'settings_manage.php';
$actionRows[2]['defaultPermissionAdmin'] = 'Y';
$actionRows[2]['defaultPermissionTeacher'] = 'N';
$actionRows[2]['defaultPermissionStudent'] = 'N';
$actionRows[2]['defaultPermissionParent'] = 'N';
$actionRows[2]['defaultPermissionSupport'] = 'N';
$actionRows[2]['categoryPermissionStaff'] = 'Y';
$actionRows[2]['categoryPermissionStudent'] = 'N';
$actionRows[2]['categoryPermissionParent'] = 'N';
$actionRows[2]['categoryPermissionOther'] = 'N';

$actionRows[3]['name'] = 'Browse Units_all';
$actionRows[3]['precedence'] = '1';
$actionRows[3]['category'] = 'Learning';
$actionRows[3]['description'] = 'Allows a user to browse all active units.';
$actionRows[3]['URLList'] = 'units_browse.php, units_browse_details.php, units_browse_details_approval.php, units_browse_details_export.php';
$actionRows[3]['entryURL'] = 'units_browse.php';
$actionRows[3]['entrySidebar'] = 'N';
$actionRows[3]['defaultPermissionAdmin'] = 'Y';
$actionRows[3]['defaultPermissionTeacher'] = 'Y';
$actionRows[3]['defaultPermissionStudent'] = 'N';
$actionRows[3]['defaultPermissionParent'] = 'N';
$actionRows[3]['defaultPermissionSupport'] = 'Y';
$actionRows[3]['categoryPermissionStaff'] = 'Y';
$actionRows[3]['categoryPermissionStudent'] = 'Y';
$actionRows[3]['categoryPermissionParent'] = 'Y';
$actionRows[3]['categoryPermissionOther'] = 'Y';

$actionRows[4]['name'] = 'Browse Units_prerequisites';
$actionRows[4]['precedence'] = '0';
$actionRows[4]['category'] = 'Learning';
$actionRows[4]['description'] = 'Allows a user to browse all active units, with enforcement of prerequisite units.';
$actionRows[4]['URLList'] = 'units_browse.php, units_browse_details.php';
$actionRows[4]['entryURL'] = 'units_browse.php';
$actionRows[4]['entrySidebar'] = 'N';
$actionRows[4]['defaultPermissionAdmin'] = 'N';
$actionRows[4]['defaultPermissionTeacher'] = 'N';
$actionRows[4]['defaultPermissionStudent'] = 'Y';
$actionRows[4]['defaultPermissionParent'] = 'N';
$actionRows[4]['defaultPermissionSupport'] = 'N';
$actionRows[4]['categoryPermissionStaff'] = 'Y';
$actionRows[4]['categoryPermissionStudent'] = 'Y';
$actionRows[4]['categoryPermissionParent'] = 'Y';
$actionRows[4]['categoryPermissionOther'] = 'Y';

$actionRows[5]['name'] = 'Current Unit By Class';
$actionRows[5]['precedence'] = '0';
$actionRows[5]['category'] = 'Reports';
$actionRows[5]['description'] = "Allows a user to see all classes in the school, with each student\'s current unit choice.";
$actionRows[5]['URLList'] = 'report_currentUnitByClass.php';
$actionRows[5]['entryURL'] = 'report_currentUnitByClass.php';
$actionRows[5]['entrySidebar'] = 'N';
$actionRows[5]['defaultPermissionAdmin'] = 'Y';
$actionRows[5]['defaultPermissionTeacher'] = 'Y';
$actionRows[5]['defaultPermissionStudent'] = 'N';
$actionRows[5]['defaultPermissionParent'] = 'N';
$actionRows[5]['defaultPermissionSupport'] = 'N';
$actionRows[5]['categoryPermissionStaff'] = 'Y';
$actionRows[5]['categoryPermissionStudent'] = 'Y';
$actionRows[5]['categoryPermissionParent'] = 'N';
$actionRows[5]['categoryPermissionOther'] = 'N';

$actionRows[6]['name'] = 'Unit History By Student_all';
$actionRows[6]['precedence'] = '1';
$actionRows[6]['category'] = 'Reports';
$actionRows[6]['description'] = 'Allows a user to see all units undertaken by any student.';
$actionRows[6]['URLList'] = 'report_unitHistory_byStudent.php';
$actionRows[6]['entryURL'] = 'report_unitHistory_byStudent.php';
$actionRows[6]['entrySidebar'] = 'Y';
$actionRows[6]['defaultPermissionAdmin'] = 'Y';
$actionRows[6]['defaultPermissionTeacher'] = 'Y';
$actionRows[6]['defaultPermissionStudent'] = 'N';
$actionRows[6]['defaultPermissionParent'] = 'N';
$actionRows[6]['defaultPermissionSupport'] = 'N';
$actionRows[6]['categoryPermissionStaff'] = 'Y';
$actionRows[6]['categoryPermissionStudent'] = 'Y';
$actionRows[6]['categoryPermissionParent'] = 'N';
$actionRows[6]['categoryPermissionOther'] = 'N';

$actionRows[7]['name'] = 'Unit History By Student_myChildren';
$actionRows[7]['precedence'] = '0';
$actionRows[7]['category'] = 'Reports';
$actionRows[7]['description'] = 'Allows a user to see all units undertaken by their own children.';
$actionRows[7]['URLList'] = 'report_unitHistory_byStudent.php';
$actionRows[7]['entryURL'] = 'report_unitHistory_byStudent.php';
$actionRows[7]['entrySidebar'] = 'Y';
$actionRows[7]['defaultPermissionAdmin'] = 'N';
$actionRows[7]['defaultPermissionTeacher'] = 'N';
$actionRows[7]['defaultPermissionStudent'] = 'N';
$actionRows[7]['defaultPermissionParent'] = 'Y';
$actionRows[7]['defaultPermissionSupport'] = 'N';
$actionRows[7]['categoryPermissionStaff'] = 'N';
$actionRows[7]['categoryPermissionStudent'] = 'N';
$actionRows[7]['categoryPermissionParent'] = 'Y';
$actionRows[7]['categoryPermissionOther'] = 'N';

$actionRows[8]['name'] = 'Outcomes By Student';
$actionRows[8]['precedence'] = '0';
$actionRows[8]['category'] = 'Reports';
$actionRows[8]['description'] = 'Allows a user to see all outcomes met by a given student.';
$actionRows[8]['URLList'] = 'report_outcomes_byStudent.php';
$actionRows[8]['entryURL'] = 'report_outcomes_byStudent.php';
$actionRows[8]['entrySidebar'] = 'Y';
$actionRows[8]['defaultPermissionAdmin'] = 'Y';
$actionRows[8]['defaultPermissionTeacher'] = 'N';
$actionRows[8]['defaultPermissionStudent'] = 'N';
$actionRows[8]['defaultPermissionParent'] = 'N';
$actionRows[8]['defaultPermissionSupport'] = 'N';
$actionRows[8]['categoryPermissionStaff'] = 'Y';
$actionRows[8]['categoryPermissionStudent'] = 'N';
$actionRows[8]['categoryPermissionParent'] = 'Y';
$actionRows[8]['categoryPermissionOther'] = 'N';

$actionRows[9]['name'] = 'My Unit History';
$actionRows[9]['precedence'] = '0';
$actionRows[9]['category'] = 'Learning';
$actionRows[9]['description'] = 'Allows a student to see all the units they have studied and are studying.';
$actionRows[9]['URLList'] = 'report_unitHistory_my.php';
$actionRows[9]['entryURL'] = 'report_unitHistory_my.php';
$actionRows[9]['entrySidebar'] = 'Y';
$actionRows[9]['defaultPermissionAdmin'] = 'N';
$actionRows[9]['defaultPermissionTeacher'] = 'N';
$actionRows[9]['defaultPermissionStudent'] = 'Y';
$actionRows[9]['defaultPermissionParent'] = 'N';
$actionRows[9]['defaultPermissionSupport'] = 'N';
$actionRows[9]['categoryPermissionStaff'] = 'Y';
$actionRows[9]['categoryPermissionStudent'] = 'Y';
$actionRows[9]['categoryPermissionParent'] = 'Y';
$actionRows[9]['categoryPermissionOther'] = 'Y';

$actionRows[10]['name'] = 'Free Learning Showcase';
$actionRows[10]['precedence'] = '0';
$actionRows[10]['category'] = 'Learning';
$actionRows[10]['description'] = 'Allows users to view Exemplar Work from across the system, in one place.';
$actionRows[10]['URLList'] = 'showcase.php';
$actionRows[10]['entryURL'] = 'showcase.php';
$actionRows[10]['entrySidebar'] = 'N';
$actionRows[10]['defaultPermissionAdmin'] = 'Y';
$actionRows[10]['defaultPermissionTeacher'] = 'Y';
$actionRows[10]['defaultPermissionStudent'] = 'Y';
$actionRows[10]['defaultPermissionParent'] = 'Y';
$actionRows[10]['defaultPermissionSupport'] = 'Y';
$actionRows[10]['categoryPermissionStaff'] = 'Y';
$actionRows[10]['categoryPermissionStudent'] = 'Y';
$actionRows[10]['categoryPermissionParent'] = 'Y';
$actionRows[10]['categoryPermissionOther'] = 'Y';

$actionRows[11]['name'] = 'Work Pending Approval_all';
$actionRows[11]['precedence'] = '1';
$actionRows[11]['category'] = 'Reports';
$actionRows[11]['description'] = 'Allows a user to see all work for which approval has been requested, and is still pending.';
$actionRows[11]['URLList'] = 'report_workPendingApproval.php';
$actionRows[11]['entryURL'] = 'report_workPendingApproval.php';
$actionRows[11]['entrySidebar'] = 'N';
$actionRows[11]['defaultPermissionAdmin'] = 'Y';
$actionRows[11]['defaultPermissionTeacher'] = 'N';
$actionRows[11]['defaultPermissionStudent'] = 'N';
$actionRows[11]['defaultPermissionParent'] = 'N';
$actionRows[11]['defaultPermissionSupport'] = 'N';
$actionRows[11]['categoryPermissionStaff'] = 'Y';
$actionRows[11]['categoryPermissionStudent'] = 'N';
$actionRows[11]['categoryPermissionParent'] = 'N';
$actionRows[11]['categoryPermissionOther'] = 'N';

$actionRows[12]['name'] = 'Manage Badges';
$actionRows[12]['precedence'] = '0';
$actionRows[12]['category'] = 'Gamification';
$actionRows[12]['description'] = 'Allows a user set how badges (from the Badges unit) are awarded.';
$actionRows[12]['URLList'] = 'badges_manage.php, badges_manage_add.php, badges_manage_edit.php';
$actionRows[12]['entryURL'] = 'badges_manage.php';
$actionRows[12]['entrySidebar'] = 'Y';
$actionRows[12]['defaultPermissionAdmin'] = 'Y';
$actionRows[12]['defaultPermissionTeacher'] = 'N';
$actionRows[12]['defaultPermissionStudent'] = 'N';
$actionRows[12]['defaultPermissionParent'] = 'N';
$actionRows[12]['defaultPermissionSupport'] = 'N';
$actionRows[12]['categoryPermissionStaff'] = 'Y';
$actionRows[12]['categoryPermissionStudent'] = 'N';
$actionRows[12]['categoryPermissionParent'] = 'N';
$actionRows[12]['categoryPermissionOther'] = 'N';

$actionRows[13]['name'] = 'View Badges';
$actionRows[13]['precedence'] = '0';
$actionRows[13]['category'] = 'Gamification';
$actionRows[13]['description'] = 'Allows a user to view badges that are available via Free Learning.';
$actionRows[13]['URLList'] = 'badges_view.php';
$actionRows[13]['entryURL'] = 'badges_view.php';
$actionRows[13]['entrySidebar'] = 'Y';
$actionRows[13]['defaultPermissionAdmin'] = 'Y';
$actionRows[13]['defaultPermissionTeacher'] = 'Y';
$actionRows[13]['defaultPermissionStudent'] = 'Y';
$actionRows[13]['defaultPermissionParent'] = 'Y';
$actionRows[13]['defaultPermissionSupport'] = 'Y';
$actionRows[13]['categoryPermissionStaff'] = 'Y';
$actionRows[13]['categoryPermissionStudent'] = 'Y';
$actionRows[13]['categoryPermissionParent'] = 'Y';
$actionRows[13]['categoryPermissionOther'] = 'Y';

$actionRows[14]['name'] = 'Learning Activity';
$actionRows[14]['precedence'] = '0';
$actionRows[14]['category'] = 'Reports';
$actionRows[14]['description'] = 'Allows a user to generate graphs of learning activity.';
$actionRows[14]['URLList'] = 'report_learningActivity.php';
$actionRows[14]['entryURL'] = 'report_learningActivity.php';
$actionRows[14]['entrySidebar'] = 'Y';
$actionRows[14]['defaultPermissionAdmin'] = 'Y';
$actionRows[14]['defaultPermissionTeacher'] = 'N';
$actionRows[14]['defaultPermissionStudent'] = 'N';
$actionRows[14]['defaultPermissionParent'] = 'N';
$actionRows[14]['defaultPermissionSupport'] = 'N';
$actionRows[14]['categoryPermissionStaff'] = 'Y';
$actionRows[14]['categoryPermissionStudent'] = 'Y';
$actionRows[14]['categoryPermissionParent'] = 'Y';
$actionRows[14]['categoryPermissionOther'] = 'Y';

$actionRows[15]['name'] = 'Work Pending Approval_my';
$actionRows[15]['precedence'] = '0';
$actionRows[15]['category'] = 'Reports';
$actionRows[15]['description'] = 'Allows a user to see all work for which approval has been requested, and is still pending, for their own students.';
$actionRows[15]['URLList'] = 'report_workPendingApproval.php';
$actionRows[15]['entryURL'] = 'report_workPendingApproval.php';
$actionRows[15]['entrySidebar'] = 'N';
$actionRows[15]['defaultPermissionAdmin'] = 'N';
$actionRows[15]['defaultPermissionTeacher'] = 'Y';
$actionRows[15]['defaultPermissionStudent'] = 'N';
$actionRows[15]['defaultPermissionParent'] = 'N';
$actionRows[15]['defaultPermissionSupport'] = 'N';
$actionRows[15]['categoryPermissionStaff'] = 'Y';
$actionRows[15]['categoryPermissionStudent'] = 'N';
$actionRows[15]['categoryPermissionParent'] = 'N';
$actionRows[15]['categoryPermissionOther'] = 'N';

$actionRows[16]['name'] = 'Manage Enrolment_all';
$actionRows[16]['precedence'] = '0';
$actionRows[16]['category'] = 'Admin';
$actionRows[16]['description'] = 'Allows a user to manage all enrolments within Browse Units. Does not have an interface of its own.';
$actionRows[16]['URLList'] = 'enrolment_manage.php';
$actionRows[16]['entryURL'] = 'enrolment_manage.php';
$actionRows[16]['entrySidebar'] = 'Y';
$actionRows[16]['menuShow'] = 'N';
$actionRows[16]['defaultPermissionAdmin'] = 'Y';
$actionRows[16]['defaultPermissionTeacher'] = 'N';
$actionRows[16]['defaultPermissionStudent'] = 'N';
$actionRows[16]['defaultPermissionParent'] = 'N';
$actionRows[16]['defaultPermissionSupport'] = 'N';
$actionRows[16]['categoryPermissionStaff'] = 'Y';
$actionRows[16]['categoryPermissionStudent'] = 'N';
$actionRows[16]['categoryPermissionParent'] = 'N';
$actionRows[16]['categoryPermissionOther'] = 'N';

$actionRows[17]['name'] = 'Mentorship Overview_all';
$actionRows[17]['precedence'] = '1';
$actionRows[17]['category'] = 'Reports';
$actionRows[17]['description'] = 'Allows a user to see mentorship for any units.';
$actionRows[17]['URLList'] = 'report_mentorshipOverview.php';
$actionRows[17]['entryURL'] = 'report_mentorshipOverview.php';
$actionRows[17]['defaultPermissionAdmin'] = 'Y';
$actionRows[17]['defaultPermissionTeacher'] = 'N';
$actionRows[17]['defaultPermissionStudent'] = 'N';
$actionRows[17]['defaultPermissionParent'] = 'N';
$actionRows[17]['defaultPermissionSupport'] = 'N';
$actionRows[17]['categoryPermissionStaff'] = 'Y';
$actionRows[17]['categoryPermissionStudent'] = 'N';
$actionRows[17]['categoryPermissionParent'] = 'N';
$actionRows[17]['categoryPermissionOther'] = 'N';

$actionRows[18]['name'] = 'Mentorship Overview_my';
$actionRows[18]['precedence'] = '1';
$actionRows[18]['category'] = 'Reports';
$actionRows[18]['description'] = 'Allows a user to see units for which they are a mentor.';
$actionRows[18]['URLList'] = 'report_mentorshipOverview.php';
$actionRows[18]['entryURL'] = 'report_mentorshipOverview.php';
$actionRows[18]['defaultPermissionAdmin'] = 'N';
$actionRows[18]['defaultPermissionTeacher'] = 'Y';
$actionRows[18]['defaultPermissionStudent'] = 'N';
$actionRows[18]['defaultPermissionParent'] = 'N';
$actionRows[18]['defaultPermissionSupport'] = 'N';
$actionRows[18]['categoryPermissionStaff'] = 'Y';
$actionRows[18]['categoryPermissionStudent'] = 'N';
$actionRows[18]['categoryPermissionParent'] = 'N';
$actionRows[18]['categoryPermissionOther'] = 'N';

$actionRows[19]['name'] = 'Manage Mentor Groups';
$actionRows[19]['precedence'] = '0';
$actionRows[19]['category'] = 'Admin';
$actionRows[19]['description'] = 'Allows a user to assign students to mentors automatically or manually.';
$actionRows[19]['URLList'] = 'mentorGroups_manage.php,mentorGroups_manage_add.php,mentorGroups_manage.php,mentorGroups_manage_edit.php,mentorGroups_manage_delete.php';
$actionRows[19]['entryURL'] = 'mentorGroups_manage.php';
$actionRows[19]['defaultPermissionAdmin'] = 'Y';
$actionRows[19]['defaultPermissionTeacher'] = 'N';
$actionRows[19]['defaultPermissionStudent'] = 'N';
$actionRows[19]['defaultPermissionParent'] = 'N';
$actionRows[19]['defaultPermissionSupport'] = 'N';
$actionRows[19]['categoryPermissionStaff'] = 'Y';
$actionRows[19]['categoryPermissionStudent'] = 'N';
$actionRows[19]['categoryPermissionParent'] = 'N';
$actionRows[19]['categoryPermissionOther'] = 'N';

$actionRows[20]['name'] = 'Student Progress By Class';
$actionRows[20]['precedence'] = '0';
$actionRows[20]['category'] = 'Reports';
$actionRows[20]['description'] = "Allows a user to see all classes in the school, with each student\'s progress for the year.";
$actionRows[20]['URLList'] = 'report_studentProgressByClass.php';
$actionRows[20]['entryURL'] = 'report_studentProgressByClass.php';
$actionRows[20]['entrySidebar'] = 'N';
$actionRows[20]['defaultPermissionAdmin'] = 'Y';
$actionRows[20]['defaultPermissionTeacher'] = 'Y';
$actionRows[20]['defaultPermissionStudent'] = 'N';
$actionRows[20]['defaultPermissionParent'] = 'N';
$actionRows[20]['defaultPermissionSupport'] = 'N';
$actionRows[20]['categoryPermissionStaff'] = 'Y';
$actionRows[20]['categoryPermissionStudent'] = 'N';
$actionRows[20]['categoryPermissionParent'] = 'N';
$actionRows[20]['categoryPermissionOther'] = 'N';

$actionRows[21]['name'] = 'Current Unit by Custom Field';
$actionRows[21]['precedence'] = '0';
$actionRows[21]['category'] = 'Reports';
$actionRows[21]['description'] = "Allows a user to see current unit choice for students by custom field.";
$actionRows[21]['URLList'] = 'report_currentUnitByCustomField.php';
$actionRows[21]['entryURL'] = 'report_currentUnitByCustomField.php';
$actionRows[21]['entrySidebar'] = 'Y';
$actionRows[21]['defaultPermissionAdmin'] = 'N';
$actionRows[21]['defaultPermissionTeacher'] = 'N';
$actionRows[21]['defaultPermissionStudent'] = 'N';
$actionRows[21]['defaultPermissionParent'] = 'N';
$actionRows[21]['defaultPermissionSupport'] = 'N';
$actionRows[21]['categoryPermissionStaff'] = 'Y';
$actionRows[21]['categoryPermissionStudent'] = 'N';
$actionRows[21]['categoryPermissionParent'] = 'N';
$actionRows[21]['categoryPermissionOther'] = 'N';

$actionRows[22]['name'] = 'Unread Comment Notifications';
$actionRows[22]['precedence'] = '0';
$actionRows[22]['category'] = 'Reports';
$actionRows[22]['description'] = "Allows a user to see unread notifications relating to comments left on units.";
$actionRows[22]['URLList'] = 'report_unreadComments.php';
$actionRows[22]['entryURL'] = 'report_unreadComments.php';
$actionRows[22]['entrySidebar'] = 'Y';
$actionRows[22]['defaultPermissionAdmin'] = 'Y';
$actionRows[22]['defaultPermissionTeacher'] = 'Y';
$actionRows[22]['defaultPermissionStudent'] = 'N';
$actionRows[22]['defaultPermissionParent'] = 'N';
$actionRows[22]['defaultPermissionSupport'] = 'N';
$actionRows[22]['categoryPermissionStaff'] = 'Y';
$actionRows[22]['categoryPermissionStudent'] = 'N';
$actionRows[22]['categoryPermissionParent'] = 'N';
$actionRows[22]['categoryPermissionOther'] = 'N';

$array = array();
$array['toggleSettingName'] = 'publicUnits';
$array['toggleSettingScope'] = 'Free Learning';
$array['toggleSettingValue'] = 'Y';
$array['title'] = 'Free Learning With Us';
$array['text'] = "Free Learning is a way to promote student independence and engagement, by encouraging students to find their own path through a set of content. As a member of the public, we invite you to <a href=\'./index.php?q=/modules/Free Learning/units_browse.php\'>browse a range of our units</a>.";
$hooks[0] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Public Home Page ', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = array();
$array['sourceModuleName'] = 'Free Learning';
$array['sourceModuleAction'] = 'Unit History By Student_all';
$array['sourceModuleInclude'] = 'hook_studentProfile_unitHistory.php';
$hooks[1] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Student Profile', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = array();
$array['sourceModuleName'] = 'Free Learning';
$array['sourceModuleAction'] = 'Unit History By Student_myChildren';
$array['sourceModuleInclude'] = 'hook_parentalDashboard_unitHistory.php';
$hooks[2] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Parental Dashboard', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = array();
$array['sourceModuleName'] = 'Free Learning';
$array['sourceModuleAction'] = 'My Unit History';
$array['sourceModuleInclude'] = 'hook_studentDashboard_unitHistory.php';
$hooks[3] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Student Dashboard', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

//Translatables
__('Free Learning');
__("Free Learning is a module which enables a student-focused and student-driven pedagogy that goes by the same name as the module (see <a href='http://rossparker.org/free-learning'>http://rossparker.org/free-learning</a> for more).");
__('Individual');
__('Pairs');
__('Threes');
__('Fours');
__('Fives');
__('Current');
__('Current - Pending');
__('Complete - Pending');
__('Complete - Approved');
__('Exempt');
__('Evidence Not Yet Approved');
__('Difficulty Options');
__('The range of difficulty options available when creating units, from lowest to highest, as a comma-separated list.');
__('Low');
__('Medium');
__('High');
__('Public Units');
__('Should selected units be made available to members of the public, via the home page?');
__('Unit Outline Template');
__('An HTML template to be used as the default for all new units.');
__('Map Link');
__('A URL pointing to a map of the available units.');
__('School Type', 'Determines how enrolment should function');
__('Learning Area Restriction');
__('Should unit creation be limited to own Learning Areas?');
__('Enable Class Enrolment');
__('Should class enrolment be an option for learners?');
__('Enable School Mentor Enrolment');
__('Should school mentor enrolment be an option for learners?');
__('Enable External Mentor Enrolment');
__('Should external mentor enrolment be an option for learners?');
__('Allows privileged users to manage all Free Learning units.');
__('Allows a privileged user within a learning area to manage all Free Learning units with their learning area.');
__('Allows a privileged user to manage Free Learning settings.');
__('Allows a user to browse all active units.');
__('Allows a user to browse all active units, with enforcement of prerequisite units.');
__("Allows a user to see all classes in the school, with each student\'s current unit choice.");
__('Allows a user to see all units undertaken by any student.');
__('Allows a user to see all units undertaken by their own children.');
__('Allows a user to see all outcomes met by a given student.');
__('Allows a student to see all the units they have studied and are studying.');
__('Allows users to view Exemplar Work from across the system, in one place.');
__('Allows a user to see all work for which approval has been requested, and is still pending.');
__('Allows a user set how badges (from the Badges unit) are awarded.');
__('Allows a user to view badges that are available via Free Learning.');
__('Free Learning With Us');
__("Free Learning is a way to promote student independence and engagement, by encouraging students to find their own path through a set of content. As a member of the public, we invite you to <a href=\'./index.php?q=/modules/Free Learning/units_browse.php\'>browse a range of our units</a>.");
__('Admin');
__('Gamification');
__('Learning');
__('Reports');
__('Certificates Available');
__('Should certificates be made available on unit completion?');
