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

//This file describes the module, including database tables

//Basic variables
$name = 'Badges';
$description = 'The Badges module allows a school to define and assign a range of badges or awards to users. Badges recognise, for example, student progress, staff professional development or parent involvement in school life.';
$entryURL = 'badges_view.php';
$type = 'Additional';
$category = 'Assess';
$version = '2.5.03';
$author = 'Ross Parker';
$url = 'http://rossparker.org';

//Module tables
$moduleTables[0] = "CREATE TABLE `badgesBadge` (
  `badgesBadgeID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL,
  `logo` varchar(255) NULL,
  `logoLicense` text NOT NULL,
  `gibbonPersonIDCreator` int(8) unsigned zerofill NOT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`badgesBadgeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;";

$moduleTables[1] = 'CREATE TABLE `badgesBadgeStudent` (
  `badgesBadgeStudentID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `badgesBadgeID` int(8) unsigned zerofill NOT NULL,
  `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
  `date` date NOT NULL,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NULL DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`badgesBadgeStudentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

//Settings
$moduleTables[2] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Badges', 'badgeCategories', 'Badge Categories', 'Comma-separated list of available choices for badge category.', 'Academic,Athletic,Social,Other');";

//Action rows
$actionRows[0]['name'] = 'Manage Badges';
$actionRows[0]['precedence'] = '0';
$actionRows[0]['category'] = 'Manage Badges';
$actionRows[0]['description'] = 'Allows a user to define and edit badges.';
$actionRows[0]['URLList'] = 'badges_manage.php, badges_manage_add.php, badges_manage_edit.php, badges_manage_delete.php';
$actionRows[0]['entryURL'] = 'badges_manage.php';
$actionRows[0]['defaultPermissionAdmin'] = 'Y';
$actionRows[0]['defaultPermissionTeacher'] = 'N';
$actionRows[0]['defaultPermissionStudent'] = 'N';
$actionRows[0]['defaultPermissionParent'] = 'N';
$actionRows[0]['defaultPermissionSupport'] = 'N';
$actionRows[0]['categoryPermissionStaff'] = 'Y';
$actionRows[0]['categoryPermissionStudent'] = 'Y';
$actionRows[0]['categoryPermissionParent'] = 'Y';
$actionRows[0]['categoryPermissionOther'] = 'Y';

$actionRows[1]['name'] = 'Badge Settings';
$actionRows[1]['precedence'] = '0';
$actionRows[1]['category'] = 'Manage Badges';
$actionRows[1]['description'] = 'Allows a user to adjust badge settings.';
$actionRows[1]['URLList'] = 'badgeSettings.php';
$actionRows[1]['entryURL'] = 'badgeSettings.php';
$actionRows[1]['defaultPermissionAdmin'] = 'Y';
$actionRows[1]['defaultPermissionTeacher'] = 'N';
$actionRows[1]['defaultPermissionStudent'] = 'N';
$actionRows[1]['defaultPermissionParent'] = 'N';
$actionRows[1]['defaultPermissionSupport'] = 'N';
$actionRows[1]['categoryPermissionStaff'] = 'Y';
$actionRows[1]['categoryPermissionStudent'] = 'Y';
$actionRows[1]['categoryPermissionParent'] = 'Y';
$actionRows[1]['categoryPermissionOther'] = 'Y';

$actionRows[2]['name'] = 'Grant Badges';
$actionRows[2]['precedence'] = '0';
$actionRows[2]['category'] = 'Manage Badges';
$actionRows[2]['description'] = 'Allows a user to give out badges to students.';
$actionRows[2]['URLList'] = 'badges_grant.php, badges_grant_add.php, badges_grant_delete.php';
$actionRows[2]['entryURL'] = 'badges_grant.php';
$actionRows[2]['defaultPermissionAdmin'] = 'Y';
$actionRows[2]['defaultPermissionTeacher'] = 'N';
$actionRows[2]['defaultPermissionStudent'] = 'N';
$actionRows[2]['defaultPermissionParent'] = 'N';
$actionRows[2]['defaultPermissionSupport'] = 'N';
$actionRows[2]['categoryPermissionStaff'] = 'Y';
$actionRows[2]['categoryPermissionStudent'] = 'Y';
$actionRows[2]['categoryPermissionParent'] = 'Y';
$actionRows[2]['categoryPermissionOther'] = 'Y';

$actionRows[3]['name'] = 'View Badges_my';
$actionRows[3]['precedence'] = '0';
$actionRows[3]['category'] = 'View Badges';
$actionRows[3]['description'] = 'Allows a user to view badges that they have been granted.';
$actionRows[3]['URLList'] = 'badges_view.php';
$actionRows[3]['entryURL'] = 'badges_view.php';
$actionRows[3]['defaultPermissionAdmin'] = 'N';
$actionRows[3]['defaultPermissionTeacher'] = 'N';
$actionRows[3]['defaultPermissionStudent'] = 'Y';
$actionRows[3]['defaultPermissionParent'] = 'N';
$actionRows[3]['defaultPermissionSupport'] = 'N';
$actionRows[3]['categoryPermissionStaff'] = 'Y';
$actionRows[3]['categoryPermissionStudent'] = 'Y';
$actionRows[3]['categoryPermissionParent'] = 'N';
$actionRows[3]['categoryPermissionOther'] = 'N';

$actionRows[4]['name'] = 'View Badges_myChildren';
$actionRows[4]['precedence'] = '1';
$actionRows[4]['category'] = 'View Badges';
$actionRows[4]['description'] = 'Allows parents to view badges that have have been granted to their children.';
$actionRows[4]['URLList'] = 'badges_view.php';
$actionRows[4]['entryURL'] = 'badges_view.php';
$actionRows[4]['defaultPermissionAdmin'] = 'N';
$actionRows[4]['defaultPermissionTeacher'] = 'N';
$actionRows[4]['defaultPermissionStudent'] = 'N';
$actionRows[4]['defaultPermissionParent'] = 'Y';
$actionRows[4]['defaultPermissionSupport'] = 'N';
$actionRows[4]['categoryPermissionStaff'] = 'N';
$actionRows[4]['categoryPermissionStudent'] = 'N';
$actionRows[4]['categoryPermissionParent'] = 'Y';
$actionRows[4]['categoryPermissionOther'] = 'N';

$actionRows[5]['name'] = 'View Badges_all';
$actionRows[5]['precedence'] = '2';
$actionRows[5]['category'] = 'View Badges';
$actionRows[5]['description'] = 'Allows a user to view badges that have been granted to any student.';
$actionRows[5]['URLList'] = 'badges_view.php';
$actionRows[5]['entryURL'] = 'badges_view.php';
$actionRows[5]['defaultPermissionAdmin'] = 'Y';
$actionRows[5]['defaultPermissionTeacher'] = 'Y';
$actionRows[5]['defaultPermissionStudent'] = 'N';
$actionRows[5]['defaultPermissionParent'] = 'N';
$actionRows[5]['defaultPermissionSupport'] = 'N';
$actionRows[5]['categoryPermissionStaff'] = 'Y';
$actionRows[5]['categoryPermissionStudent'] = 'Y';
$actionRows[5]['categoryPermissionParent'] = 'Y';
$actionRows[5]['categoryPermissionOther'] = 'Y';

$actionRows[6]['name'] = 'Credits & Licenses';
$actionRows[6]['precedence'] = '0';
$actionRows[6]['category'] = 'Credits';
$actionRows[6]['description'] = 'Allows a user to view image credits for licensed images.';
$actionRows[6]['URLList'] = 'badges_credits.php';
$actionRows[6]['entryURL'] = 'badges_credits.php';
$actionRows[6]['defaultPermissionAdmin'] = 'Y';
$actionRows[6]['defaultPermissionTeacher'] = 'Y';
$actionRows[6]['defaultPermissionStudent'] = 'Y';
$actionRows[6]['defaultPermissionParent'] = 'Y';
$actionRows[6]['defaultPermissionSupport'] = 'Y';
$actionRows[6]['categoryPermissionStaff'] = 'Y';
$actionRows[6]['categoryPermissionStudent'] = 'Y';
$actionRows[6]['categoryPermissionParent'] = 'Y';
$actionRows[6]['categoryPermissionOther'] = 'Y';

$actionRows[7]['name'] = 'View Available Badges';
$actionRows[7]['precedence'] = '0';
$actionRows[7]['category'] = 'View Badges';
$actionRows[7]['description'] = 'Allows a user to view all available badges.';
$actionRows[7]['URLList'] = 'badges_view_available.php';
$actionRows[7]['entryURL'] = 'badges_view_available.php';
$actionRows[7]['defaultPermissionAdmin'] = 'Y';
$actionRows[7]['defaultPermissionTeacher'] = 'Y';
$actionRows[7]['defaultPermissionStudent'] = 'Y';
$actionRows[7]['defaultPermissionParent'] = 'Y';
$actionRows[7]['defaultPermissionSupport'] = 'Y';
$actionRows[7]['categoryPermissionStaff'] = 'Y';
$actionRows[7]['categoryPermissionStudent'] = 'Y';
$actionRows[7]['categoryPermissionParent'] = 'Y';
$actionRows[7]['categoryPermissionOther'] = 'Y';

//HOOKS
$array = array();
$array['sourceModuleName'] = 'Badges';
$array['sourceModuleAction'] = 'View Badges_all';
$array['sourceModuleInclude'] = 'hook_studentProfile_badgesView.php';
$hooks[0] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Badges', 'Student Profile', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";

$array = array();
$array['sourceModuleName'] = 'Badges';
$array['sourceModuleAction'] = 'View Badges_myChildren';
$array['sourceModuleInclude'] = 'hook_parentalDashboard_badgesView.php';
$hooks[1] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Badges', 'Parental Dashboard', '".serialize($array)."', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));";
