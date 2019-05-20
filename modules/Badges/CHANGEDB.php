<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v0.1.00
$sql[$count][0] = '0.1.00';
$sql[$count][1] = '-- First version, nothing to update';

//v0.2.00
++$count;
$sql[$count][0] = '0.2.00';
$sql[$count][1] = "CREATE TABLE `awardsAwardStudent` (  `awardsAwardStudentID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,  `awardsAwardID` int(8) unsigned zerofill NOT NULL,  `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,  `date` date NOT NULL,  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,  `comment` text CHARACTER SET utf8 NOT NULL,  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`awardsAwardStudentID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Awards'), 'Grant Awards', 0, 'Manage Awards', 'Allows a user to give out awards to students.', 'awards_grant.php, awards_grant_add.php, awards_grant_delete.php', 'awards_grant.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Grant Awards'));end
";

//v0.5.00
++$count;
$sql[$count][0] = '0.5.00';
$sql[$count][1] = "INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Awards'), 'View Awards_my', 0, 'View Awards', 'Allows a user to view awards that they have been granted.', 'awards_view.php', 'awards_view.php', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='View Awards_my'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Awards'), 'View Awards_myChildren', 1, 'View Awards', 'Allows parents to view awards that have have been granted to their children.', 'awards_view.php', 'awards_view.php', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '4', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='View Awards_myChildren'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Awards'), 'View Awards_all', 2, 'View Awards', 'Allows a user to view awards that have been granted to any student.', 'awards_view.php', 'awards_view.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'Y') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='View Awards_all'));end
";

//v1.0.00
++$count;
$sql[$count][0] = '1.0.00';
$sql[$count][1] = "INSERT INTO `gibbonHook` (`name`, `type`, `options`, `gibbonModuleID`) VALUES ('Awards', 'Student Profile', 'a:3:{s:16:\"sourceModuleName\";s:6:\"Awards\";s:18:\"sourceModuleAction\";s:15:\"View Awards_all\";s:19:\"sourceModuleInclude\";s:34:\"hook_studentProfile_awardsView.php\";}', 0154),('Awards', 'Parental Dashboard', 'a:3:{s:16:\"sourceModuleName\";s:6:\"Awards\";s:18:\"sourceModuleAction\";s:22:\"View Awards_myChildren\";s:19:\"sourceModuleInclude\";s:37:\"hook_parentalDashboard_awardsView.php\";}', 0154);end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='View Awards_all'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Awards'), 'Credits & Licensing', 1, 'Credits', 'Allows a user to view image credits for license images.', 'awards_credits.php', 'awards_credits.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Credits & Licensing'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Credits & Licensing'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Credits & Licensing'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '4', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Credits & Licensing'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '6', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Awards' AND gibbonAction.name='Credits & Licensing'));end
";

//v1.0.01
++$count;
$sql[$count][0] = '1.0.01';
$sql[$count][1] = '';

//v1.0.02
++$count;
$sql[$count][0] = '1.0.02';
$sql[$count][1] = '';

//v1.0.03
++$count;
$sql[$count][0] = '1.0.03';
$sql[$count][1] = '';

//v1.0.04
++$count;
$sql[$count][0] = '1.0.04';
$sql[$count][1] = '';

//v2.0.00
++$count;
$sql[$count][0] = '2.0.00';
$sql[$count][1] = "
UPDATE gibbonModule SET name='Badges', description='The Badges module allows a school to define and assign a range of badges or awards to students. Badges recognise, for example, academic, social or athletic achievement or progress.', entryURL='badges_manage.php' WHERE name='Awards';end
RENAME TABLE awardsAward TO badgesBadge;end
ALTER TABLE `badgesBadge` CHANGE `awardsAwardID` `badgesBadgeID` INT(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;end
RENAME TABLE awardsAwardStudent TO badgesBadgeStudent;end
ALTER TABLE `badgesBadgeStudent` CHANGE `awardsAwardStudentID` `badgesBadgeStudentID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, CHANGE `awardsAwardID` `badgesBadgeID` INT(8) UNSIGNED ZEROFILL NOT NULL;end
UPDATE gibbonAction SET name='Manage Badges', category='Manage Badges', description='Allows a user to define and edit badges.', URLList='badges_manage.php, badges_manage_add.php, badges_manage_edit.php, badges_manage_delete.php', entryURL='badges_manage.php' WHERE name='Manage Awards' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET name='Badge Settings', category='Manage Badges', description='Allows a user to adjust badge settings.', URLList='badgeSettings.php', entryURL='badgeSettings.php' WHERE name='Awards Settings' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET name='Grant Badges', category='Manage Badges', description='Allows a user to give out badges to students.', URLList='badges_grant.php, badges_grant_add.php, badges_grant_delete.php', entryURL='badges_grant.php' WHERE name='Grant Awards' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET name='View Badges_my', category='View Badges', description='Allows a user to view badges that they have been granted.', URLList='badges_view.php', entryURL='badges_view.php' WHERE name='View Awards_my' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET name='View Badges_myChildren', category='View Badges', description='Allows parents to view badges that have have been granted to their children.', URLList='badges_view.php', entryURL='badges_view.php' WHERE name='View Awards_myChildren' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET name='View Badges_all', category='View Badges', description='Allows a user to view badges that have been granted to any student.', URLList='badges_view.php', entryURL='badges_view.php' WHERE name='View Awards_all' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonAction SET precedence=0, description='Allows a user to view image credits for licensed images.', URLList='badges_credits.php', entryURL='badges_credits.php' WHERE name='Credits & Licenses' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonHook SET name='Badges', options='a:3:{s:16:\"sourceModuleName\";s:6:\"Badges\";s:18:\"sourceModuleAction\";s:15:\"View Badges_all\";s:19:\"sourceModuleInclude\";s:34:\"hook_studentProfile_badgesView.php\";}' WHERE name='Awards' AND type='Student Profile' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE gibbonHook SET name='Badges', options='a:3:{s:16:\"sourceModuleName\";s:6:\"Badges\";s:18:\"sourceModuleAction\";s:22:\"View Badges_myChildren\";s:19:\"sourceModuleInclude\";s:37:\"hook_parentalDashboard_badgesView.php\";}' WHERE name='Awards' AND type='Parental Dashboard' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
UPDATE `gibbonSetting` SET scope='Badges', name='badgeCategories', nameDisplay='Badge Categories', description='Comma-separated list of available choices for badge category.' WHERE scope='Awards' AND name='awardCategories';end
";

//v2.1.00
++$count;
$sql[$count][0] = '2.1.00';
$sql[$count][1] = "
UPDATE gibbonModule SET entryURL='badges_view.php', description='The Badges module allows a school to define and assign a range of badges or awards to users. Badges recognise, for example, student progress, staff professional development or parent involvement in school life.' WHERE name='Badges';end
ALTER TABLE `badgesBadge` DROP `gibbonYearGroupIDList`;end
";

//v2.1.01
++$count;
$sql[$count][0] = '2.1.01';
$sql[$count][1] = "";

//v2.1.02
++$count;
$sql[$count][0] = '2.1.02';
$sql[$count][1] = "";

//v2.1.03
++$count;
$sql[$count][0] = '2.1.03';
$sql[$count][1] = "
ALTER TABLE `badgesBadgeStudent` CHANGE `gibbonPersonIDCreator` `gibbonPersonIDCreator` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL;end
";

//v2.1.04
++$count;
$sql[$count][0] = '2.1.04';
$sql[$count][1] = "
UPDATE gibbonAction SET categoryPermissionStaff='Y' WHERE name='View Badges_my' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end
";

//v2.2.00
++$count;
$sql[$count][0] = '2.2.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges'), 'View Available Badges', 0, 'View Badges', 'Allows a user to view all available badges.', 'badges_view_available.php', 'badges_view_available.php', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Badges' AND gibbonAction.name='View Available Badges'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Badges' AND gibbonAction.name='View Available Badges'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Badges' AND gibbonAction.name='View Available Badges'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '4', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Badges' AND gibbonAction.name='View Available Badges'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '6', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Badges' AND gibbonAction.name='View Available Badges'));end
";

//v2.3.00
++$count;
$sql[$count][0] = '2.3.00';
$sql[$count][1] = "";

//v2.3.01
++$count;
$sql[$count][0] = '2.3.01';
$sql[$count][1] = "";

//v2.4.00
++$count;
$sql[$count][0] = '2.4.00';
$sql[$count][1] = "";

//v2.5.00
++$count;
$sql[$count][0] = '2.5.00';
$sql[$count][1] = "";

//v2.5.01
++$count;
$sql[$count][0] = '2.5.01';
$sql[$count][1] = "";

//v2.5.02
++$count;
$sql[$count][0] = '2.5.02';
$sql[$count][1] = "";

//v2.5.03
++$count;
$sql[$count][0] = '2.5.03';
$sql[$count][1] = "UPDATE gibbonAction SET URLList='badges_credits.php', entryURL='badges_credits.php' WHERE name='Credits & Licensing' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges');end";
